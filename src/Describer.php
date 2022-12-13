<?php

namespace ryunosuke\DbDescriber;

use Alom\Graphviz\AttributeSet;
use Alom\Graphviz\Digraph;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Event;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Routine;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Trigger;

class Describer
{
    /** @var Connection */
    private $connection;

    /** @var Schema */
    private $schema;

    /** @var Table[] */
    private $tables = [];

    /** @var Table[] */
    private $views = [];

    /** @var array */
    private $includes, $excludes, $relation;

    /** @var string */
    private $delimiter;

    /** @var callable */
    private $schemaCallback, $tableCallback, $viewCallback;

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
        $this->template = $config['template'];
        $this->vars = $config['vars'];
        $this->graphAttrs = $config['graph'];
        $this->nodeAttrs = $config['node'];
        $this->edgehAttrs = $config['edge'];
        $this->columns = $config['columns'];

        call_user_func($config['connectionCallback'], $this->connection);
    }

    private function _detectSchema()
    {
        if (!$this->schema) {
            $this->schema = $this->connection->createSchemaManager()->introspectSchema();
            call_user_func($this->schemaCallback, $this->schema);
        }
        return $this->schema;
    }

    private function _detectTable()
    {
        if (!$this->tables) {
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

            $triggers = $this->_detectSchema()->getTriggers();

            foreach ($this->_detectSchema()->getTables() as $table) {
                $tableName = $table->getName();
                if ($skip($tableName)) {
                    continue;
                }

                if (call_user_func($this->tableCallback, $table) === false) {
                    continue;
                }

                $selftriggers = [];
                foreach ($triggers as $trigger) {
                    if ($trigger->getTableName() === $tableName) {
                        $selftriggers[] = $trigger;
                    }
                }
                $table->addOption('triggers', $selftriggers);

                if (!$table->hasOption('comment')) {
                    $table->addOption('comment', '');
                }
                $empty = [];
                $table->addOption('foreignKeys', $empty);
                $table->addOption('referenceKeys', $empty);

                if (isset($this->relation[$tableName])) {
                    foreach ($this->relation[$tableName] as $ftable => $misc) {
                        foreach ($misc as $fkname => $columns) {
                            $table->addForeignKeyConstraint($ftable, array_keys($columns), array_values($columns), [], $fkname);
                        }
                    }
                }

                $this->tables[$tableName] = $table;
            }

            // ランク算出クロージャ
            $rank = function (Table $table, array $parents) use (&$rank) {
                $ranks = [];
                foreach ($table->getForeignKeys() as $fkey) {
                    if (isset($this->tables[$fkey->getForeignTableName()])) {
                        $ftable = $this->tables[$fkey->getForeignTableName()];
                        if (!isset($parents[$ftable->getName()])) {
                            $ranks[$fkey->getName()] = min($rank($ftable, $parents + [$ftable->getName() => []]) ?: [0]) + 1;
                        }
                    }
                }
                return $ranks;
            };
            // 外部キーのための2パス目
            foreach ($this->tables as $table) {
                foreach ($table->getForeignKeys() as $fkey) {
                    // exclude されていて相方がいないことがあるのでここで一元担保
                    if (isset($this->tables[$fkey->getForeignTableName()])) {
                        $lkeys = $table->getOption('foreignKeys') + [$fkey->getName() => $fkey];
                        $table->addOption('foreignKeys', $lkeys);

                        $ftable = $this->tables[$fkey->getForeignTableName()];
                        $fkeys = $ftable->getOption('referenceKeys') + [$fkey->getName() => $fkey];
                        $ftable->addOption('referenceKeys', $fkeys);
                    }
                }
                $table->addOption('ranks', $rank($table, [$table->getName() => []]));
            }
        }
        return $this->tables;
    }

    private function _detectView()
    {
        if (!$this->views) {
            foreach ($this->_detectSchema()->getViews() as $view) {
                $viewName = $view->getName();
                if (call_user_func($this->viewCallback, $view) === false) {
                    continue;
                }

                $table = $this->connection->createSchemaManager()->introspectViewAsTable($viewName);
                $table->addOption('sql', $view->getSql());
                $this->views[$viewName] = $table;
            }
        }
        return $this->views;
    }

    private function _delimitComment($comment)
    {
        return explode($this->delimiter, $comment, 2) + [1 => ''];
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

        $tables = $this->_detectTable();

        return [
            'Schema'   => $dbname,
            'Tables'   => $arrays($tables, function (Table $table, $k, $n) use ($tables, $arrays) {
                [$logicalName, $summary] = $this->_delimitComment($table->getOption('comment'));

                $indexes = array_filter($table->getIndexes(), fn(Index $index) => !$index->hasFlag('implicit'));
                usort($indexes, function ($a, $b) {
                    if ($a->isPrimary()) {
                        return -1;
                    }
                    if ($b->isPrimary()) {
                        return +1;
                    }
                    return $a->getName() <=> $b->getName();
                });
                return [
                    'No'                => $n + 1,
                    'Name'              => $table->getName(),
                    'LogicalName'       => $logicalName,
                    'Summary'           => $summary,
                    'Collation'         => $table->getOption('collation'),
                    'Format'            => $table->getOption('row_format'),
                    'Engine'            => $table->getOption('engine'),
                    'ColumnCount'       => count($table->getColumns()),
                    'IndexCount'        => count($table->getIndexes()),
                    'ForeignKeyCount'   => count($table->getOption('foreignKeys')),
                    'ReferenceKeyCount' => count($table->getOption('referenceKeys')),
                    'TriggerCount'      => count($table->getOption('triggers')),
                    'Columns'           => $arrays($table->getColumns(), function (Column $column, $k, $n) use ($table) {
                        $pkcols = $table->getPrimaryKey() ? $table->getPrimaryKey()->getColumns() : [];
                        $uniqueable = [];
                        foreach ($table->getIndexes() as $iname => $index) {
                            if ($index->isUnique()) {
                                if (($m = array_search($column->getName(), $index->getColumns())) !== false) {
                                    $uniqueable[] = "$iname-" . ($m + 1);
                                }
                            }
                        }
                        [$logicalName, $summary] = $this->_delimitComment($column->getComment());
                        $platformOptions = $column->getPlatformOptions() + ['generation' => ['type' => '', 'expression' => '']];
                        return [
                            'No'          => $n + 1,
                            'Name'        => $column->getName(),
                            'LogicalName' => $logicalName,
                            'Summary'     => $summary,
                            'Type'        => $column->getType(),
                            'Default'     => $column->getDefault() === null && $column->getNotnull() ? false : $column->getDefault(),
                            'Length'      => $column->getLength(),
                            'Unsigned'    => $column->getUnsigned(),
                            'Precision'   => $column->getPrecision(),
                            'Scale'       => $column->getScale(),
                            'Collation'   => @$column->getPlatformOption('collation'),
                            'NotNull'     => !in_array($column->getName(), $pkcols) && $column->getNotnull(),
                            'Unique'      => implode(',', $uniqueable),
                            'Generated'   => $platformOptions['generation'],
                        ];
                    }),
                    'Indexes'           => $arrays($indexes, function (Index $index, $k, $n) {
                        return [
                            'No'      => $n + 1,
                            'Name'    => $index->getName(),
                            'Columns' => $index->getColumns(),
                            'Unique'  => $index->isUnique(),
                            'Type'    => $index->getFlags(),
                            'Options' => $index->getOptions(),
                        ];
                    }),
                    'ForeignKeys'       => $arrays($table->getOption('foreignKeys'), function (ForeignKeyConstraint $foreignKey, $k, $n) use ($tables) {
                        return [
                            'No'                    => $n + 1,
                            'Name'                  => $foreignKey->getName(),
                            'Columns'               => $foreignKey->getLocalColumns(),
                            'ReferenceTable'        => $foreignKey->getForeignTableName(),
                            'ReferenceTableComment' => $tables[$foreignKey->getForeignTableName()]->getOption('comment'),
                            'ReferenceColumns'      => $foreignKey->getForeignColumns(),
                            'OnUpdate'              => $foreignKey->hasOption('onUpdate') ? $foreignKey->getOption('onUpdate') : '',
                            'OnDelete'              => $foreignKey->hasOption('onDelete') ? $foreignKey->getOption('onDelete') : '',
                        ];
                    }),
                    'ReferenceKeys'     => $arrays($table->getOption('referenceKeys'), function (ForeignKeyConstraint $foreignKey, $k, $n) use ($tables) {
                        return [
                            'No'                    => $n + 1,
                            'Name'                  => $foreignKey->getName(),
                            'Columns'               => $foreignKey->getForeignColumns(),
                            'ReferenceTable'        => $foreignKey->getLocalTableName(),
                            'ReferenceTableComment' => $tables[$foreignKey->getLocalTableName()]->getOption('comment'),
                            'ReferenceColumns'      => $foreignKey->getLocalColumns(),
                            'OnUpdate'              => $foreignKey->hasOption('onUpdate') ? $foreignKey->getOption('onUpdate') : '',
                            'OnDelete'              => $foreignKey->hasOption('onDelete') ? $foreignKey->getOption('onDelete') : '',
                        ];
                    }),
                    'Triggers'          => $arrays($table->getOption('triggers'), function (Trigger $trigger, $k, $n) {
                        return [
                            'No'        => $n + 1,
                            'Name'      => $trigger->getName(),
                            'Statement' => $trigger->getStatement(),
                            'Event'     => $trigger->getOption('event'),
                            'Timing'    => $trigger->getOption('timing'),
                        ];
                    }),
                ];
            }),
            'Views'    => $arrays($this->_detectView(), function (Table $view, $k, $n) use ($arrays) {
                return [
                    'No'          => $n + 1,
                    'Name'        => $view->getName(),
                    'Sql'         => $view->getOption('sql'),
                    'CheckOption' => $view->getOption('view_options')['checkOption'],
                    'Updatable'   => $view->getOption('view_options')['updatable'],
                    'ColumnCount' => count($view->getColumns()),
                    'IndexCount'  => count($view->getIndexes()),
                    'Columns'     => $arrays($view->getColumns(), function (Column $column, $k, $n) use ($view) {
                        $uniqueable = [];
                        foreach ($view->getIndexes() as $iname => $index) {
                            if ($index->isUnique()) {
                                if (($m = array_search($column->getName(), $index->getColumns())) !== false) {
                                    $uniqueable[] = "$iname-" . ($m + 1);
                                }
                            }
                        }
                        [$logicalName, $summary] = $this->_delimitComment($column->getComment());
                        return [
                            'No'          => $n + 1,
                            'Name'        => $column->getName(),
                            'LogicalName' => $logicalName,
                            'Summary'     => $summary,
                            'Type'        => $column->getType(),
                            'Default'     => $column->getDefault() === null && $column->getNotnull() ? false : $column->getDefault(),
                            'Length'      => $column->getLength(),
                            'Unsigned'    => $column->getUnsigned(),
                            'Precision'   => $column->getPrecision(),
                            'Scale'       => $column->getScale(),
                            'Collation'   => @$column->getPlatformOption('collation'),
                            'NotNull'     => $column->getNotnull(),
                            'Unique'      => implode(',', $uniqueable),
                        ];
                    }),
                    'Indexes'     => $arrays($view->getIndexes(), function (Index $index, $k, $n) {
                        return [
                            'No'      => $n + 1,
                            'Name'    => $index->getName(),
                            'Columns' => $index->getColumns(),
                            'Unique'  => $index->isUnique(),
                            'Type'    => $index->getFlags(),
                            'Options' => $index->getOptions(),
                        ];
                    }),
                ];
            }),
            'Routines' => $arrays($this->_detectSchema()->getRoutines(), function (Routine $routine, $k, $n) {
                [$logicalName, $summary] = $this->_delimitComment($routine->getOption('comment'));
                return [
                    'No'          => $n + 1,
                    'Name'        => $routine->getName(),
                    'LogicalName' => $logicalName,
                    'Summary'     => $summary,
                    'Statement'   => $routine->getStatement(),
                    'Type'        => $routine->getOption('type'),
                    'Parameters'  => (function ($parameters) {
                        $list = [];
                        foreach ($parameters as $name => $param) {
                            $list[] = trim($param['mode'] . ' ' . $name . ' ' . $param['typeDeclaration']);
                        }
                        return implode(', ', $list);
                    })($routine->getOption('parameters')),
                    'Return'      => $routine->getOption('returnTypeDeclaration'),
                ];
            }),
            'Events'   => $arrays($this->_detectSchema()->getEvents(), function (Event $event, $k, $n) {
                [$logicalName, $summary] = $this->_delimitComment($event->getOption('comment'));
                return [
                    'No'        => $n + 1,
                    'Name'      => $event->getName(),
                    'LogicalName' => $logicalName,
                    'Summary'     => $summary,
                    'Statement' => $event->getStatement(),
                    'Since'     => $event->getOption('since'),
                    'Until'     => $event->getOption('until'),
                    'Interval'  => (function ($interval) {
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
                    })($event->getInterval()),
                ];
            }),
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
        $tables = $this->_detectTable();
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
            $widths[$rank][] = mb_strwidth($tableName . $this->_delimitComment($table->getOption('comment'))[0]);
            foreach ($columns[$tableName] as $column) {
                $widths[$rank][] = mb_strwidth($column . $this->_delimitComment($table->getColumn($column)->getComment())[0]);
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
            $tableComment = $table->getOption('comment');
            $tableColumns = array_unique($columns[$tableName]);
            $ranks = $table->getOption('ranks');

            // サブグラフ
            $subgraphs[$tableName] = $tableGraph = $graph->subgraph("cluster_{$tableName}");
            $tableGraph->attr('graph', [
                'id'        => "relationship:table-$tableName",
                'class'     => "table-$tableName " . implode(' ', array_map(fn($c) => "column-$tableName-$c", $tableColumns)),
                'labelloc'  => 't',
                'labeljust' => 'l',
                'margin'    => 1,
                'bgcolor'   => '#eeeeee',
                'color'     => '#606060',
                'label'     => $tableName . ': ' . $this->_delimitComment($tableComment)[0],
                'style'     => 'bold',
            ]);

            // ノード
            foreach ($tableColumns as $c) {
                $column = $table->getColumn($c);
                $columnName = $column->getName();
                $columnComment = $this->_delimitComment($column->getComment())[0];

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
