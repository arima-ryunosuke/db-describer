<?php

namespace ryunosuke\Test\DbDescriber;

use Doctrine\DBAL\Schema\Event;
use Doctrine\DBAL\Schema\Routine;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ryunosuke\DbDescriber\Describer;

class DescriberTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    private $outdir = __DIR__ . '/../../output';

    function getConfig($override = [])
    {
        return $override + [
                'include'            => [],
                'exclude'            => [],
                'relation'           => [],
                'delimiter'          => "\n",
                'connectionCallback' => function () { },
                'schemaCallback'     => function () { },
                'tableCallback'      => function () { },
                'viewCallback'       => function () { },
                'routineCallback'    => function () { },
                'eventCallback'      => function () { },
                'template'           => __DIR__ . '/../../../template/html.php',
                'vars'               => [],
                'columns'            => 'all',
                'graph'              => [],
                'node'               => [],
                'edge'               => [],
            ];
    }

    function test___construct()
    {
        /** @var \Doctrine\DBAL\Connection $connection */

        $parts = parse_url(TEST_DSN);

        $_SERVER['HOME'] = sys_get_temp_dir();
        @unlink($_SERVER['HOME'] . '/.my.cnf');
        file_put_contents($_SERVER['HOME'] . '/.my.cnf', "[client]
user = {$parts['user']}
password = {$parts['pass']}
");

        // my.cnf
        $describer = new Describer("{$parts['scheme']}://{$parts['host']}", $this->getConfig());
        $connection = (fn() => $this->connection)->bindTo($describer, Describer::class)();
        $expected = [
            'user'     => $parts['user'],
            'password' => $parts['pass'],
        ];
        $this->assertEquals($expected, array_intersect_key($connection->getParams(), $expected));

        // posix_geteuid
        $describer = new Describer('pdo-sqlite://localhost:1234/:memory:', $this->getConfig());
        $connection = (fn() => $this->connection)->bindTo($describer, Describer::class)();
        $expected = [
            'user' => (posix_getpwuid(posix_geteuid())['name']),
        ];
        $this->assertEquals($expected, array_intersect_key($connection->getParams(), $expected));
    }

    function test_all()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig());

        $html = file_get_contents($describer->generate($this->outdir)[0]);
        $this->assertStringContainsString('table-t_article', $html);
        $this->assertStringContainsString('table-t_comment', $html);
        $this->assertStringContainsString('view-v_blog', $html);
        $this->assertStringContainsString('routine-function1', $html);
        $this->assertStringContainsString('routine-procedure1', $html);
        $this->assertStringContainsString('event-event1', $html);

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('cluster_t_article', $dot);
        $this->assertStringContainsString('cluster_t_comment', $dot);
    }

    function test_include()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'include' => ['t_article'],
        ]));

        $html = file_get_contents($describer->generate($this->outdir)[0]);
        $this->assertStringContainsString('table-t_article', $html);
        $this->assertStringNotContainsString('table-t_comment', $html);
        $this->assertStringNotContainsString('view-v_blog', $html);
        $this->assertStringNotContainsString('routine-function1', $html);
        $this->assertStringNotContainsString('routine-procedure1', $html);
        $this->assertStringNotContainsString('event-event1', $html);

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('cluster_t_article', $dot);
        $this->assertStringNotContainsString('cluster_t_comment', $dot);

        $describer = new Describer(TEST_DSN, $this->getConfig([
            'include' => ['t_article', 'v_blog', 'function1', 'event1'],
        ]));

        $html = file_get_contents($describer->generate($this->outdir)[0]);
        $this->assertStringContainsString('table-t_article', $html);
        $this->assertStringNotContainsString('table-t_comment', $html);
        $this->assertStringContainsString('view-v_blog', $html);
        $this->assertStringContainsString('routine-function1', $html);
        $this->assertStringNotContainsString('routine-procedure1', $html);
        $this->assertStringContainsString('event-event1', $html);

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('cluster_t_article', $dot);
        $this->assertStringNotContainsString('cluster_t_comment', $dot);
    }

    function test_exclude()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'exclude' => ['t_comment'],
        ]));

        $html = file_get_contents($describer->generate($this->outdir)[0]);
        $this->assertStringContainsString('table-t_article', $html);
        $this->assertStringNotContainsString('table-t_comment', $html);
        $this->assertStringContainsString('view-v_blog', $html);
        $this->assertStringContainsString('routine-function1', $html);
        $this->assertStringContainsString('routine-procedure1', $html);
        $this->assertStringContainsString('event-event1', $html);

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('cluster_t_article', $dot);
        $this->assertStringNotContainsString('cluster_t_comment', $dot);

        $describer = new Describer(TEST_DSN, $this->getConfig([
            'exclude' => ['t_comment', 'v_blog', 'procedure1', 'event1'],
        ]));

        $html = file_get_contents($describer->generate($this->outdir)[0]);
        $this->assertStringContainsString('table-t_article', $html);
        $this->assertStringNotContainsString('table-t_comment', $html);
        $this->assertStringNotContainsString('view-v_blog', $html);
        $this->assertStringContainsString('routine-function1', $html);
        $this->assertStringNotContainsString('routine-procedure1', $html);
        $this->assertStringNotContainsString('event-event1', $html);

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('cluster_t_article', $dot);
        $this->assertStringNotContainsString('cluster_t_comment', $dot);
    }

    function test_callback()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'schemaCallback'  => function (Schema $schema) {
                $table = $schema->createTable('tmptable');
                $table->addColumn('xxx', 'integer');
                $table->setPrimaryKey(['xxx']);
                $table->addOption('collation', 'utf8_bin');
                $table->addOption('engine', 'InnoDB');
                $table->addOption('row_format', 'Compact');
            },
            'tableCallback'   => function (Table $table) {
                if ($table->getName() === 't_article') {
                    $table->addOption('comment', 'changed-table-comment');
                }
                if ($table->getName() === 't_comment') {
                    return false;
                }
            },
            'viewCallback'    => function (View $view) {
                if ($view->getName() === 'v_blog') {
                    return false;
                }
            },
            'routineCallback' => function (Routine $routine) {
                $routine->addOption('comment', 'changed-routine-comment');
            },
            'eventCallback'   => function (Event $event) {
                $event->addOption('comment', 'changed-event-comment');
            },
            'columns'         => 'related',
        ]));

        $html = file_get_contents($describer->generate($this->outdir)[0]);
        $this->assertStringContainsString('table-tmptable', $html);
        $this->assertStringContainsString('changed-table-comment', $html);
        $this->assertStringNotContainsString('table-t_comment', $html);
        $this->assertStringNotContainsString('view-v_blog', $html);
        $this->assertStringContainsString('changed-routine-comment', $html);
        $this->assertStringContainsString('changed-event-comment', $html);

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('changed-table-comment', $dot);
        $this->assertStringContainsString('cluster_tmptable', $dot);
    }

    function test_xlsx()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'template' => __DIR__ . '/../../../template/xlsx.php',
        ]));
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = (fn() => $this->connection)->call($describer);
        $dbname = $connection->getDatabase();

        [$xlsx, $erd] = $describer->generate($this->outdir);

        $book = IOFactory::load($xlsx);
        $this->assertEquals([$dbname, 't_article', 't_comment', 't_datatype'], $book->getSheetNames());
        $this->assertEquals('t_article', $book->getSheetByName($dbname)->getCell('C3')->getValue());
        $this->assertEquals('t_article', $book->getSheetByName('t_article')->getCell('D3')->getValue());

        $html = file_get_contents($erd);
        $this->assertStringContainsString('graph', $html);
        $this->assertStringContainsString('subgraph', $html);
        $this->assertStringContainsString('node', $html);
        $this->assertStringContainsString('edge', $html);
    }

    function test_template()
    {
        $template = sys_get_temp_dir() . '/template.phtml';
        file_put_contents($template, <<<'PHP'
        <?php
        return function ($outdir, $dbname, $schemaObjects) {
            ob_start();
            echo "Tables:" . count($schemaObjects['Tables']) . "\n";
            echo "Views:" . count($schemaObjects['Views']) . "\n";
            echo "Routines:" . count($schemaObjects['Routines']) . "\n";
            echo "Events:" . count($schemaObjects['Events']) . "\n";
            return [ob_get_clean()];
        };
        PHP,);
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'template' => $template,
        ]));

        $html = $describer->generate($this->outdir)[0];
        $this->assertEquals(<<<EXPECTED
        Tables:3
        Views:1
        Routines:2
        Events:1
        
        EXPECTED, $html);
    }

    function test_column()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'columns' => 'all',
        ]));
        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString(':column-t_article.title"', $dot);
        $this->assertStringContainsString(':column-t_comment.comment"', $dot);

        $describer = new Describer(TEST_DSN, $this->getConfig([
            'columns' => 'related',
        ]));
        $dot = $describer->generateDot([], $tables);
        $this->assertStringNotContainsString(':column-t_article.title"', $dot);
        $this->assertStringNotContainsString(':column-t_comment.comment"', $dot);
    }

    function test_relation()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'relation' => [
                't_comment' => [
                    't_article' => [
                        'fk_appeneded' => [
                            'comment_id' => 'article_id',
                        ],
                    ],
                ],
            ],
            'columns'  => 'related',
        ]));

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('column_t_comment_article_id', $dot);
        $this->assertStringContainsString('column_t_comment_comment_id', $dot);
    }

    function test_delimiter()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'delimiter' => ':',
            'columns'   => 'related',
        ]));

        $dot = $describer->generateDot([], $tables);
        $this->assertStringContainsString('PrimaryKey', $dot);
        $this->assertStringNotContainsString('summary', $dot);
    }
}
