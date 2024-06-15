<?php

namespace ryunosuke\DbDescriber;

use Alom\Graphviz\AttributeSet;
use Alom\Graphviz\Digraph;
use Alom\Graphviz\Graph;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Event;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Routine;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Trigger;

class Describer
{
    /** @var Connection */
    private $connection;

    /** @var AbstractAsset[] */
    private $objects;

    /** @var array */
    private $includes, $excludes, $relation;

    /** @var string */
    private $delimiter;

    /** @var callable */
    private $schemaCallback, $tableCallback, $viewCallback, $routineCallback, $eventCallback;

    /** @var string */
    private $template;

    /** @var array */
    private $vars;

    /** @var array */
    private $graphAttrs, $nodeAttrs, $edgehAttrs;

    /** @var string */
    private $columns;

    public function __construct($dsn, $config)
    {
        // for omit hostname
        $dsn = str_replace(':///', '://127.0.0.1/', $dsn);

        // parse DSN url
        $parseDatabaseUrl = new \ReflectionMethod('Doctrine\\DBAL\\DriverManager', 'parseDatabaseUrl');
        $parseDatabaseUrl->setAccessible(true);
        $params = $parseDatabaseUrl->invoke(null, ['url' => $dsn]);

        // for mysql. .my.cnf
        if (isset($_SERVER['HOME']) && stripos($params['driver'], 'mysql') !== false) {
            $mycnf = $_SERVER['HOME'] . '/.my.cnf';
            if (is_readable($mycnf)) {
                $mycnfini = parse_ini_file($mycnf, true);
                if (!isset($params['user']) && $mycnfini['client']['user']) {
                    $params['user'] = $mycnfini['client']['user'];
                }
                if (!isset($params['password']) && $mycnfini['client']['password']) {
                    $params['password'] = $mycnfini['client']['password'];
                }
            }
        }

        // add current user
        if (function_exists('posix_geteuid')) {
            $params += [
                'user' => (posix_getpwuid(posix_geteuid())['name']),
            ];
        }
        unset($params['url']);

        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($params);
        $this->includes = $config['include'];
        $this->excludes = $config['exclude'];
        $this->relation = $config['relation'];
        $this->delimiter = $config['delimiter'];
        $this->schemaCallback = $config['schemaCallback'];
        $this->tableCallback = $config['tableCallback'];
        $this->viewCallback = $config['viewCallback'];
        $this->routineCallback = $config['routineCallback'];
        $this->eventCallback = $config['eventCallback'];
        $this->template = $config['template'];
        $this->vars = $config['vars'];
        $this->graphAttrs = $config['graph'];
        $this->nodeAttrs = $config['node'];
        $this->edgehAttrs = $config['edge'];
        $this->columns = $config['columns'];

        call_user_func($config['connectionCallback'], $this->connection);

        // このライブラリはデータの変換等は伴わず、データ型の識別さえできればそれでいいので未知の型を自動登録する
        $this->connection->getDatabasePlatform()->enableAutoType(true);
    }

    private function _detectSchema()
    {
        if ($this->objects === null) {
            $this->objects = [
                'table'   => [],
                'view'    => [],
                'routine' => [],
                'event'   => [],
            ];

            $skip = function ($tablename) {
                $flag = count($this->includes) > 0;
                foreach ($this->includes as $include) {
                    foreach (array_map('trim', explode(',', $include)) as $regex) {
                        if (preg_match("@$regex@i", $tablename)) {
                            $flag = false;
                            break;
                        }
                    }
                }
                if ($flag) {
                    return true;
                }

                foreach ($this->excludes as $exclude) {
                    foreach (array_map('trim', explode(',', $exclude)) as $regex) {
                        if (preg_match("@$regex@i", $tablename)) {
                            return true;
                        }
                    }
                }

                return false;
            };

            $reindex = function ($indexes) {
                $indexes = array_filter($indexes, fn(Index $index) => !$index->hasFlag('implicit'));
                array_multisort(array_map(fn(Index $v) => $v->isPrimary() ? '' : $v->getName(), $indexes), $indexes);
                return $indexes;
            };

            $sortAssets = function ($assets) {
                uasort($assets, fn(AbstractAsset $a, AbstractAsset $b) => $a->getName() <=> $b->getName());
                return $assets;
            };

            $colconstraint = function ($column, $indexes) {
                $constraints = [];
                if ($column->getNotnull()) {
                    $constraints['NotNull'] = 'NotNull';
                }
                foreach ($indexes as $iname => $index) {
                    if ($index->isPrimary()) {
                        if (in_array($column->getName(), $index->getColumns())) {
                            unset($constraints['NotNull']);
                        }
                    }
                    if ($index->isUnique()) {
                        if (($m = array_search($column->getName(), $index->getColumns())) !== false) {
                            $constraints[] = "$iname-" . ($m + 1);
                        }
                    }
                }
                return $constraints;
            };

            $delimit = function ($comment) {
                return explode($this->delimiter, (string) $comment, 2) + [1 => ''];
            };

            $rank = function (Table $table, array $parents) use (&$rank) {
                $ranks = [];
                foreach ($table->getForeignKeys() as $fkey) {
                    if (isset($this->objects['table'][$fkey->getForeignTableName()])) {
                        $ftable = $this->objects['table'][$fkey->getForeignTableName()];
                        if (!isset($parents[$ftable->getName()])) {
                            $ranks[$fkey->getName()] = min($rank($ftable, $parents + [$ftable->getName() => []]) ?: [0]) + 1;
                        }
                    }
                }
                return $ranks;
            };

            $schema = $this->connection->createSchemaManager()->introspectSchema();
            call_user_func($this->schemaCallback, $schema);

            $triggers = $sortAssets($schema->getTriggers());

            foreach ($sortAssets($schema->getTables()) as $table) {
                $tableName = $table->getName();
                if ($skip($tableName)) {
                    continue;
                }

                if (call_user_func($this->tableCallback, $table) === false) {
                    continue;
                }

                $table->addOption('indexes', $reindex($table->getIndexes()));

                foreach ($table->getColumns() as $column) {
                    [$logicalName, $summary] = $delimit($column->getComment());
                    $column->setPlatformOption('logicalName', $logicalName);
                    $column->setPlatformOption('summary', $summary);
                    $column->setPlatformOption('constraints', $colconstraint($column, $table->getOption('indexes')));
                }

                $tabletriggers = [];
                foreach ($triggers as $trigger) {
                    if ($trigger->getTableName() === $tableName) {
                        $tabletriggers[] = $trigger;
                    }
                }
                $table->addOption('triggers', $tabletriggers);

                $table->addOption('foreignKeys', []);
                $table->addOption('referenceKeys', []);

                if (isset($this->relation[$tableName])) {
                    foreach ($this->relation[$tableName] as $ftable => $misc) {
                        foreach ($misc as $fkname => $columns) {
                            $table->addForeignKeyConstraint($ftable, array_keys($columns), array_values($columns), [], $fkname);
                        }
                    }
                }

                [$logicalName, $summary] = $delimit($table->getComment());
                $table->addOption('logicalName', $logicalName);
                $table->addOption('summary', $summary);

                $this->objects['table'][$tableName] = $table;
            }

            // 外部キーのための2パス目
            foreach ($this->objects['table'] as $table) {
                foreach ($table->getForeignKeys() as $fkey) {
                    // exclude されていて相方がいないことがあるのでここで一元担保
                    if (isset($this->objects['table'][$fkey->getForeignTableName()])) {
                        $lkeys = $table->getOption('foreignKeys') + [$fkey->getName() => $fkey];
                        $table->addOption('foreignKeys', $lkeys);

                        $ftable = $this->objects['table'][$fkey->getForeignTableName()];
                        $fkeys = $ftable->getOption('referenceKeys') + [$fkey->getName() => $fkey];
                        $ftable->addOption('referenceKeys', $fkeys);
                    }
                }
                $table->addOption('ranks', $rank($table, [$table->getName() => []]));
            }

            foreach ($sortAssets($schema->getViews()) as $view) {
                $viewName = $view->getName();
                if ($skip($viewName)) {
                    continue;
                }
                if (call_user_func($this->viewCallback, $view) === false) {
                    continue;
                }

                // view はテーブルとして扱う
                $table = $this->connection->createSchemaManager()->introspectViewAsTable($viewName);
                $table->addOption('sql', $view->getSql());

                $table->addOption('indexes', $reindex($table->getIndexes()));

                foreach ($table->getColumns() as $column) {
                    [$logicalName, $summary] = $delimit($column->getComment());
                    $column->setPlatformOption('logicalName', $logicalName);
                    $column->setPlatformOption('summary', $summary);
                    $column->setPlatformOption('constraints', $colconstraint($column, $table->getOption('indexes')));
                }

                $this->objects['view'][$viewName] = $table;
            }

            foreach ($sortAssets($schema->getRoutines()) as $routine) {
                $routineName = $routine->getName();
                if ($skip($routineName)) {
                    continue;
                }
                if (call_user_func($this->routineCallback, $routine) === false) {
                    continue;
                }

                $routine->addOption('parameter', (function ($parameters) {
                    $list = [];
                    foreach ($parameters as $name => $param) {
                        $list[] = trim($param['mode'] . ' ' . $name . ' ' . $param['typeDeclaration']);
                    }
                    return implode(', ', $list);
                })($routine->getOption('parameters')));

                [$logicalName, $summary] = $delimit(@$routine->getOption('comment'));
                $routine->addOption('logicalName', $logicalName);
                $routine->addOption('summary', $summary);

                $this->objects['routine'][$routineName] = $routine;
            }

            foreach ($sortAssets($schema->getEvents()) as $event) {
                $eventName = $event->getName();
                if ($skip($eventName)) {
                    continue;
                }
                if (call_user_func($this->eventCallback, $event) === false) {
                    continue;
                }

                $event->addOption('interval', (function ($interval) {
                    $concat = fn($digit, $unit) => $digit ? "{$digit}{$unit}" : '';
                    $interval = new \DateInterval($interval);
                    return implode('', [
                        $concat($interval->y, '年'),
                        $concat($interval->m, 'ヶ月'),
                        $concat($interval->d, '日'),
                        $concat($interval->h, '時間'),
                        $concat($interval->i, '分'),
                        $concat($interval->s, '秒'),
                    ]);
                })($event->getOption('interval')));

                [$logicalName, $summary] = $delimit(@$event->getOption('comment'));
                $event->addOption('logicalName', $logicalName);
                $event->addOption('summary', $summary);

                $this->objects['event'][$eventName] = $event;
            }
        }
        return $this->objects;
    }

    private function _gatherSchemaObject($dbname)
    {
        $arrays = function (array $array, $callback) {
            $i = 0;
            foreach ($array as $k => $v) {
                $array[$k] = $callback($v, $k, $i++);
            }
            return $array;
        };

        $objects = $this->_detectSchema();

        return [
            'Schema'   => $dbname,
            'Tables'   => $arrays($objects['table'], fn(Table $table, $k, $n) => [
                'No'            => $n + 1,
                'Name'          => $table->getName(),
                'LogicalName'   => $table->getOption('logicalName'),
                'Summary'       => $table->getOption('summary'),
                'Collation'     => $table->getOption('collation'),
                'Format'        => $table->getOption('row_format'),
                'Engine'        => $table->getOption('engine'),
                'Columns'       => $arrays($table->getColumns(), fn(Column $column, $k, $n) => [
                    'No'              => $n + 1,
                    'Name'            => $column->getName(),
                    'LogicalName'     => $column->getPlatformOptions()['logicalName'],
                    'Summary'         => $column->getPlatformOptions()['summary'],
                    'Type'            => $column->getType(),
                    'Default'         => $column->getDefault() === null && $column->getNotnull() ? false : $column->getDefault(),
                    'Length'          => $column->getLength(),
                    'Unsigned'        => $column->getUnsigned(),
                    'Precision'       => $column->getPrecision(),
                    'Scale'           => $column->getScale(),
                    'TypeDeclaration' => $column->getPlatformOptions()['type-declaration'] ?? '',
                    'Collation'       => $column->getPlatformOptions()['collation'] ?? '',
                    'Constraint'      => $column->getPlatformOptions()['constraints'] ?? [],
                    'Generated'       => $column->getPlatformOptions()['generation'] ?? ['type' => '', 'expression' => ''],
                ]),
                'Indexes'       => $arrays($table->getOption('indexes'), fn(Index $index, $k, $n) => [
                    'No'         => $n + 1,
                    'Name'       => $index->getName(),
                    'Columns'    => $index->getColumns(),
                    'Unique'     => $index->isUnique(),
                    'Type'       => $index->getFlags(),
                    'Expression' => $index->getOptions()['expression'] ?? null,
                    'Options'    => array_diff_key($index->getOptions(), ['expression' => null]),
                ]),
                'ForeignKeys'   => $arrays($table->getOption('foreignKeys'), fn(ForeignKeyConstraint $foreignKey, $k, $n) => [
                    'No'               => $n + 1,
                    'Name'             => $foreignKey->getName(),
                    'ReferenceTable'   => $foreignKey->getForeignTableName(),
                    'Columns'          => $foreignKey->getLocalColumns(),
                    'ReferenceColumns' => $foreignKey->getForeignColumns(),
                    'OnUpdate'         => $foreignKey->hasOption('onUpdate') ? $foreignKey->getOption('onUpdate') : '',
                    'OnDelete'         => $foreignKey->hasOption('onDelete') ? $foreignKey->getOption('onDelete') : '',
                ]),
                'ReferenceKeys' => $arrays($table->getOption('referenceKeys'), fn(ForeignKeyConstraint $foreignKey, $k, $n) => [
                    'No'               => $n + 1,
                    'Name'             => $foreignKey->getName(),
                    'ReferenceTable'   => $foreignKey->getLocalTableName(),
                    'Columns'          => $foreignKey->getForeignColumns(),
                    'ReferenceColumns' => $foreignKey->getLocalColumns(),
                    'OnUpdate'         => $foreignKey->hasOption('onUpdate') ? $foreignKey->getOption('onUpdate') : '',
                    'OnDelete'         => $foreignKey->hasOption('onDelete') ? $foreignKey->getOption('onDelete') : '',
                ]),
                'Triggers'      => $arrays($table->getOption('triggers'), fn(Trigger $trigger, $k, $n) => [
                    'No'        => $n + 1,
                    'Name'      => $trigger->getName(),
                    'Statement' => $trigger->getStatement(),
                    'Event'     => $trigger->getOption('event'),
                    'Timing'    => $trigger->getOption('timing'),
                ]),
            ]),
            'Views'    => $arrays($objects['view'], fn(Table $view, $k, $n) => [
                'No'          => $n + 1,
                'Name'        => $view->getName(),
                'Sql'         => $view->getOption('sql'),
                'CheckOption' => $view->getOption('view_options')['checkOption'],
                'Updatable'   => $view->getOption('view_options')['updatable'],
                'Columns'     => $arrays($view->getColumns(), fn(Column $column, $k, $n) => [
                    'No'              => $n + 1,
                    'Name'            => $column->getName(),
                    'LogicalName'     => $column->getPlatformOptions()['logicalName'],
                    'Summary'         => $column->getPlatformOptions()['summary'],
                    'Type'            => $column->getType(),
                    'Default'         => $column->getDefault() === null && $column->getNotnull() ? false : $column->getDefault(),
                    'Length'          => $column->getLength(),
                    'Unsigned'        => $column->getUnsigned(),
                    'Precision'       => $column->getPrecision(),
                    'Scale'           => $column->getScale(),
                    'TypeDeclaration' => $column->getPlatformOptions()['type-declaration'] ?? '',
                    'Collation'       => $column->getPlatformOptions()['collation'] ?? '',
                    'Constraint'      => $column->getPlatformOptions()['constraints'] ?? [],
                ]),
                'Indexes'     => $arrays($view->getOption('indexes'), fn(Index $index, $k, $n) => [
                    'No'         => $n + 1,
                    'Name'       => $index->getName(),
                    'Columns'    => $index->getColumns(),
                    'Unique'     => $index->isUnique(),
                    'Type'       => $index->getFlags(),
                    'Expression' => $index->getOptions()['expression'] ?? null,
                    'Options'    => array_diff_key($index->getOptions(), ['expression' => null]),
                ]),
            ]),
            'Routines' => $arrays($objects['routine'], fn(Routine $routine, $k, $n) => [
                'No'          => $n + 1,
                'Name'        => $routine->getName(),
                'LogicalName' => $routine->getOption('logicalName'),
                'Summary'     => $routine->getOption('summary'),
                'Statement'   => $routine->getStatement(),
                'Type'        => $routine->getOption('type'),
                'Parameter'   => $routine->getOption('parameter'),
                'Return'      => $routine->getOption('returnTypeDeclaration'),
            ]),
            'Events'   => $arrays($objects['event'], fn(Event $event, $k, $n) => [
                'No'          => $n + 1,
                'Name'        => $event->getName(),
                'LogicalName' => $event->getOption('logicalName'),
                'Summary'     => $event->getOption('summary'),
                'Statement'   => $event->getStatement(),
                'Since'       => $event->getOption('since'),
                'Until'       => $event->getOption('until'),
                'Interval'    => $event->getOption('interval'),
            ]),
            'Vars'     => $this->vars,
        ];
    }

    public function generateHtml($outdir)
    {
        $dbname = $this->connection->getDatabase();

        $schemaObjects = $this->_gatherSchemaObject($dbname);
        $schemaObjects['Erddot'] = $this->generateDot([
            'skipNoRelation' => true,
        ], $generated);
        $schemaObjects['TableNames'] = array_keys($generated);

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $output = (static function () {
            extract(func_get_arg(1));
            ob_start();
            include func_get_arg(0);
            return ob_get_clean();
        })($this->template, $schemaObjects);

        file_put_contents("$outdir/$dbname.html", $output);
        return $output;
    }

    public function generateDot($options = [], &$generated_columns = [])
    {
        $options += [
            'skipNoRelation' => false,
        ];

        $getRandomColor = function ($source) {
            $hex = md5($source);
            $rgb = [
                hexdec(substr($hex, 0, 2)) % 160,
                hexdec(substr($hex, 2, 2)) % 160,
                hexdec(substr($hex, 4, 2)) % 160,
            ];

            return vsprintf("#%02x%02x%02x", $rgb);
        };

        // テーブル配列
        $tables = $this->_detectSchema()['table'];
        $columns = array_fill_keys(array_keys($tables), []);

        // グラフ作成のためにランクごとの幅の最大値が必要
        $widths = [];
        foreach ($tables as $tableName => $table) {
            if ($this->columns === 'all') {
                $columns[$tableName] = array_keys($table->getColumns());
            }
            else {
                $pkcols = $table->getPrimaryKey() ? $table->getPrimaryKey()->getColumns() : [];
                $columns[$tableName] = array_merge($pkcols, $columns[$tableName]);
                foreach ($table->getForeignKeys() as $fkey) {
                    $foreignTableName = $fkey->getForeignTableName();
                    if (!isset($tables[$foreignTableName])) {
                        continue;
                    }

                    $columns[$tableName] = array_merge($columns[$tableName], $fkey->getLocalColumns());
                    $columns[$foreignTableName] = array_merge($columns[$foreignTableName], $fkey->getForeignColumns());
                }
            }

            $rank = min($table->getOption('ranks') ?: [0]);
            $widths[$rank][] = mb_strwidth($tableName . $table->getOption('logicalName'));
            foreach ($columns[$tableName] as $column) {
                $widths[$rank][] = mb_strwidth($column . $table->getColumn($column)->getPlatformOption('logicalName'));
            }
        }

        // グラフ共通属性
        $graph_attrs = array_replace([
            'charset'  => 'UTF-8',
            'rankdir'  => 'LR',
            'ranksep'  => 1.5,
            'nodesep'  => 0,
            'splines'  => 'ortho',
            'fontname' => 'IPAGothic',
            'fontsize' => 15,
            'dpi'      => 72,
        ], $this->graphAttrs);

        // ノード共通属性
        $node_attrs = array_replace([
            'shape'     => 'box',
            'style'     => 'filled',
            'fillcolor' => 'white',
            'color'     => '#aaaaaa',
            'fontname'  => 'IPAGothic',
            'fontsize'  => '12',
        ], $this->nodeAttrs);

        // エッジ共通属性
        $edge_attrs = array_replace([
            'dir'       => 'back',
            'arrowtail' => 'vee',
            'arrowsize' => 1,
        ], $this->edgehAttrs);

        // メイングラフ
        $graph = new Digraph('erd');
        $graph->attr('graph', $graph_attrs);
        $graph->attr('node', $node_attrs);
        $graph->attr('edge', $edge_attrs);

        $generated_columns = [];
        $subgraphs = [];
        $edges = [];

        // サブグラフとノードとエッジを設定
        foreach ($tables as $table) {
            if ($options['skipNoRelation'] && !($table->getOption('foreignKeys') || $table->getOption('referenceKeys'))) {
                continue;
            }
            $tableName = $table->getName();
            $tableComment = $table->getOption('logicalName');
            $tableColumns = array_unique($columns[$tableName]);
            $ranks = $table->getOption('ranks');

            // サブグラフ
            // Alom\Graphviz\Graph に id が escape されない不具合があったので呼び元で呼んでおく
            $cluster_id = (fn($id) => $this->escape($id))->bindTo($graph, Graph::class)("cluster_{$tableName}");
            $subgraphs[$tableName] = $tableGraph = $graph->subgraph($cluster_id);
            $tableGraph->attr('graph', [
                'id'        => "relationship:table-$tableName",
                'class'     => "table-$tableName " . implode(' ', array_map(fn($c) => "column-$tableName-$c", $tableColumns)),
                'labelloc'  => 't',
                'labeljust' => 'l',
                'margin'    => 1,
                'bgcolor'   => '#eeeeee',
                'color'     => '#606060',
                'label'     => $tableName . ': ' . $tableComment,
                'style'     => 'bold',
            ]);

            // ノード
            foreach ($tableColumns as $c) {
                $column = $table->getColumn($c);
                $columnName = $column->getName();
                $columnComment = $column->getPlatformOption('logicalName');

                $generated_columns[$tableName][$c] = $column;
                $tableGraph->node("column_{$tableName}_{$columnName}", [
                    'id'        => "relationship:column-{$tableName}.{$columnName}",
                    'class'     => "table-{$tableName} column-{$tableName}-{$columnName}",
                    'label'     => "\"{$columnName}: {$columnComment}\\l\"",
                    'width'     => $graph_attrs['fontsize'] / 2 * (max($widths[min($ranks ?: [0])]) + 4) / 72,
                    'fixedsize' => true,
                    'height'    => 0.36,
                    '_escaped'  => false,
                ]);
            }

            // エッジ
            foreach ($table->getOption('foreignKeys') as $fkey) {
                $localTableName = $fkey->getLocalTableName();
                $foreignTableName = $fkey->getForeignTableName();

                $localColumns = $fkey->getLocalColumns();
                $foreignColumns = $fkey->getForeignColumns();

                $constraint = $ranks[$fkey->getName()] <= min($ranks ?: [0]);

                foreach ($localColumns as $i => $lcolumn) {
                    $fcolumn = $foreignColumns[$i];
                    $from = "column_{$foreignTableName}_{$fcolumn}";
                    $to = "column_{$localTableName}_{$lcolumn}";

                    $edges["$localTableName -> $foreignTableName"] = $graph->beginEdge([$from, $to], [
                        'id'         => "relationship:fkey-{$fkey->getName()}",
                        'class'      => "fkey-{$fkey->getName()} table-{$localTableName} table-{$foreignTableName} column-{$foreignTableName}-{$fcolumn} column-{$localTableName}-{$lcolumn}",
                        'color'      => $getRandomColor("{$foreignTableName}.{$fcolumn}"),
                        'constraint' => $constraint ? 'true' : 'false',
                    ]);
                }
            }
        }

        // コメントを打ちたいので自前でレンダリング
        $dot = "digraph {$graph->getId()} {\n";
        foreach ($graph->getInstructions() as $instruction) {
            if ($instruction instanceof AttributeSet) {
                $dot .= $instruction->render(1, '  ');
            }
        }
        foreach ($subgraphs as $id => $subgraph) {
            $dot .= "# subgraph-begin: $id \n";
            $dot .= $subgraph->render(1, '  ');
            $dot .= "# subgraph-end: $id \n";
        }
        foreach ($edges as $id => $edge) {
            $dot .= "# edge-begin: $id \n";
            $dot .= $edge->render(1, '  ');
            $dot .= "# edge-end: $id \n";
        }
        $dot .= "}\n";
        return $dot;
    }
}
