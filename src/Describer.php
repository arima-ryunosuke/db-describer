<?php

namespace ryunosuke\DbDescriber;

use Alom\Graphviz\Digraph;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Trigger;
use Doctrine\DBAL\Schema\View;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ryunosuke\Excelate\Renderer;
use ryunosuke\Excelate\Variable;

class Describer
{
    /** @var Connection */
    private $connection;

    /** @var Schema */
    private $schema;

    /** @var Table[] */
    private $tables = [];

    /** @var View[] */
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
    private $sheets;

    /** @var string */
    private $dot;

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
        $this->sheets = $config['sheets'];
        $this->dot = $config['dot'];
        $this->graphAttrs = $config['graph'];
        $this->nodeAttrs = $config['node'];
        $this->edgehAttrs = $config['edge'];
        $this->columns = $config['columns'];

        call_user_func($config['connectionCallback'], $this->connection);
    }

    private function _detectSchema()
    {
        if (!$this->schema) {
            $this->schema = $this->connection->getSchemaManager()->createSchema();
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
            foreach ($this->_detectSchema()->getTables() as $table) {
                $tableName = $table->getName();
                if ($skip($tableName)) {
                    continue;
                }

                if (call_user_func($this->tableCallback, $table) === false) {
                    continue;
                }

                if (!$table->hasOption('comment')) {
                    $table->addOption('comment', '');
                }
                /** @var string $empty addOption の @param を騙すための特に意味のないアノテーション */
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

                $this->views[$viewName] = $view;
            }
        }
        return $this->views;
    }

    private function _delimitComment($comment)
    {
        return explode($this->delimiter, $comment, 2) + [1 => ''];
    }

    public function generateSpec($outdir)
    {
        $dbname = $this->connection->getDatabase();
        $tables = $this->_detectTable();

        $schemaObjects = [
            'Schema' => $dbname,
            'Tables' => Variable::arrayize($tables, function (Table $table, $n) use ($tables) {
                list($logicalName, $summary) = $this->_delimitComment($table->getOption('comment'));
                return [
                    'No'                => $n + 1,
                    'Name'              => $table->getName(),
                    'LogicalName'       => $logicalName,
                    'Summary'           => $summary,
                    'Collation'         => $table->getOption('collation'),
                    'Format'            => $table->getOption('create_options')['row_format'] ?? null,
                    'Engine'            => $table->getOption('engine'),
                    'ColumnCount'       => count($table->getColumns()),
                    'IndexCount'        => count($table->getIndexes()),
                    'ForeignKeyCount'   => count($table->getOption('foreignKeys')),
                    'ReferenceKeyCount' => count($table->getOption('referenceKeys')),
                    'TriggerCount'      => count($table->getTriggers()),
                    'Columns'           => Variable::arrayize($table->getColumns(), function (Column $column, $n) use ($table) {
                        $pkcols = $table->getPrimaryKey()->getColumns();
                        $uniqueable = [];
                        foreach ($table->getIndexes() as $iname => $index) {
                            if ($index->isUnique()) {
                                if (($m = array_search($column->getName(), $index->getColumns())) !== false) {
                                    $uniqueable[] = "$iname-" . ($m + 1);
                                }
                            }
                        }
                        list($logicalName, $summary) = $this->_delimitComment($column->getComment());
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
                            'NotNull'     => in_array($column->getName(), $pkcols) ? false : $column->getNotnull(),
                            'Unique'      => implode(',', $uniqueable),
                        ];
                    }),
                    'Indexes'           => Variable::arrayize($table->getIndexes(), function (Index $index, $n) {
                        return [
                            'No'      => $n + 1,
                            'Name'    => $index->getName(),
                            'Columns' => $index->getColumns(),
                            'Unique'  => $index->isUnique(),
                        ];
                    }),
                    'ForeignKeys'       => Variable::arrayize($table->getOption('foreignKeys'), function (ForeignKeyConstraint $foreignKey, $n) use ($tables) {
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
                    'ReferenceKeys'     => Variable::arrayize($table->getOption('referenceKeys'), function (ForeignKeyConstraint $foreignKey, $n) use ($tables) {
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
                    'Triggers'          => Variable::arrayize($table->getTriggers(), function (Trigger $trigger, $n) {
                        return [
                            'No'        => $n + 1,
                            'Name'      => $trigger->getName(),
                            'Statement' => $trigger->getStatement(),
                            'Event'     => $trigger->getOption('Event'),
                            'Timing'    => $trigger->getOption('Timing'),
                        ];
                    }),
                ];
            }),
            'Views'  => Variable::arrayize($this->_detectView(), function (View $view, $n) {
                return [
                    'No'   => $n + 1,
                    'Name' => $view->getName(),
                    'Sql'  => $view->getSql(),
                ];
            }),
        ];

        // PhpSpreadsheet が内部で ZipArchive を使用してるので phar の中身は読めない（のでコピーする）
        $template = sys_get_temp_dir() . '/standard.xlsx';
        copy($this->template, $template);
        $book = IOFactory::load($template);

        $renderer = new Renderer();
        $renderer->registerEffector('Height', function (Cell $cell, $height) {
            $cell->getWorksheet()->getRowDimension($cell->getRow())->setRowHeight($height);
        });

        $unsetget = function(&$array, $key){
            $value = $array[$key] ?? [];
            unset($array[$key]);
            return $value;
        };
        $sheets = $this->sheets;
        $indexVars = $unsetget($sheets, 'index');
        $tableVars = $unsetget($sheets, 'table');

        $tablelist = $book->getSheetByName('index');
        $tablelist->setTitle($dbname);
        $renderer->render($tablelist, $schemaObjects + $indexVars);

        $templateSheet = $book->getSheetByName('table');
        $book->removeSheetByIndex($book->getIndex($templateSheet));
        foreach ($schemaObjects['Tables'] as $table) {
            $sheet = $templateSheet->copy();
            $sheet->setTitle($table['LogicalName'] ?: $table['Name']);
            $book->addSheet($sheet);
            $renderer->render($sheet, ['Table' => $table] + $tableVars);
        }

        foreach ($sheets as $name => $vars) {
            $renderer->render($book->getSheetByName($name), $vars);
        }

        $book->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($book, 'Xlsx');
        $writer->save("$outdir/$dbname.xlsx");

        return "$outdir/$dbname.xlsx";
    }

    public function generateErd($outdir)
    {
        $dbname = $this->connection->getDatabase();

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

        // グラフ作成のために幅の最大値が必要
        $widths = [1];
        foreach ($tables as $tableName => $table) {
            if ($this->columns === 'all') {
                $columns[$tableName] = array_keys($table->getColumns());
            }
            else {
                $columns[$tableName] = array_merge($table->getPrimaryKey()->getColumns(), $columns[$tableName]);
                foreach ($table->getForeignKeys() as $fkey) {
                    $localTableName = $fkey->getLocalTableName();
                    $foreignTableName = $fkey->getForeignTableName();
                    if (!isset($tables[$foreignTableName])) {
                        continue;
                    }

                    $columns[$localTableName] = array_merge($columns[$localTableName], $fkey->getColumns());
                    $columns[$foreignTableName] = array_merge($columns[$foreignTableName], $fkey->getForeignColumns());
                }
            }

            $widths[] = mb_strwidth($tableName . $this->_delimitComment($table->getOption('comment'))[0]);
            foreach ($columns[$tableName] as $column) {
                $widths[] = mb_strwidth($column . $this->_delimitComment($table->getColumn($column)->getComment())[0]);
            }
        }

        // グラフ共通属性
        $graph_attrs = array_replace([
            'charset'  => 'UTF-8',
            'rankdir'  => 'LR',
            'ranksep'  => 3,
            'nodesep'  => 0,
            'splines'  => 'ortho',
            'fontname' => 'IPAGothic',
            'fontsize' => 15,
        ], $this->graphAttrs);

        // ノード共通属性
        $node_attrs = array_replace([
            'shape'     => 'box',
            'style'     => 'filled',
            'fillcolor' => 'white',
            'width'     => $graph_attrs['fontsize'] / 2 * (max($widths) + 4) / 72,
            'color'     => '#aaaaaa',
        ], $this->nodeAttrs);

        // エッジ共通属性
        $edge_attrs = array_replace([
            'arrowtail' => 'none',
            'arrowsize' => 1,
        ], $this->edgehAttrs);

        // メイングラフ
        $graph = new Digraph('erd');
        $graph->attr('graph', $graph_attrs);
        $graph->attr('node', $node_attrs);
        $graph->attr('edge', $edge_attrs);

        // サブグラフとノードとエッジを設定
        foreach ($tables as $table) {
            $tableName = $table->getName();

            // サブグラフ
            $tableGraph = $graph->subgraph("cluster_$tableName");
            $tableGraph->attr('graph', [
                'labelloc'  => 't',
                'labeljust' => 'l',
                'margin'    => 1,
                'bgcolor'   => '#eeeeee',
                'color'     => '#606060',
                'label'     => $tableName . ': ' . $this->_delimitComment($table->getOption('comment'))[0],
                'style'     => 'bold',
            ]);

            // ノード
            foreach (array_unique($columns[$tableName]) as $c) {
                $column = $table->getColumn($c);
                $columnName = $column->getName();
                $columnComment = $this->_delimitComment($column->getComment())[0];

                $tableGraph->node("column_{$tableName}_{$columnName}", [
                    'label'    => "\"{$columnName}: {$columnComment}\\l\"",
                    '_escaped' => false,
                ]);
            }

            // エッジ
            foreach ($table->getOption('foreignKeys') as $fkey) {
                $localTableName = $fkey->getLocalTableName();
                $foreignTableName = $fkey->getForeignTableName();

                $localColumns = $fkey->getLocalColumns();
                $foreignColumns = $fkey->getForeignColumns();

                foreach ($localColumns as $i => $lcolumn) {
                    $fcolumn = $foreignColumns[$i];
                    $from = "column_{$foreignTableName}_{$fcolumn}";
                    $to = "column_{$localTableName}_{$lcolumn}";

                    $graph->edge([$from, $to], [
                        'color' => $getRandomColor("{$foreignTableName}.{$fcolumn}"),
                    ]);
                }
            }
        }

        $dotfile = "$outdir/$dbname.dot";
        file_put_contents($dotfile, $graph->render());

        if ($this->dot) {
            $pdffile = "$outdir/$dbname.pdf";
            ob_start();
            passthru("{$this->dot} $dotfile -Tpdf", $return);
            $output = ob_get_clean();
            if (!$return) {
                file_put_contents($pdffile, $output);
                unlink($dotfile);
                return $pdffile;
            }
        }

        return $dotfile;
    }
}
