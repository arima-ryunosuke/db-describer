<?php

namespace ryunosuke\Test\DbDescriber;

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
                'template'           => __DIR__ . '/../../../template/standard.xlsx',
                'vars'               => [],
                'sheets'             => [],
                'dot'                => null,
                'columns'            => 'all',
                'graph'              => [],
                'node'               => [],
                'edge'               => [],
            ];
    }

    function test___construct()
    {
        /** @var \Doctrine\DBAL\Connection $connection */

        $_SERVER['HOME'] = sys_get_temp_dir();
        @unlink($_SERVER['HOME'] . '/.my.cnf');
        file_put_contents($_SERVER['HOME'] . '/.my.cnf', '[client]
user = hoge
password = fuga
');

        // my.cnf
        $describer = new Describer('mysql://localhost', $this->getConfig());
        $connection = (fn() => $this->connection)->call($describer);
        $expected = [
            'user'     => 'hoge',
            'password' => 'fuga',
        ];
        $this->assertEquals($expected, array_intersect_key($connection->getParams(), $expected));

        // posix_geteuid
        $describer = new Describer('sqlite://localhost:1234/:memory:', $this->getConfig());
        $connection = (fn() => $this->connection)->call($describer);
        $expected = [
            'user' => (posix_getpwuid(posix_geteuid())['name']),
        ];
        $this->assertEquals($expected, array_intersect_key($connection->getParams(), $expected));
    }

    function test_all()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig());

        $xls = $describer->generateSpec($this->outdir);
        $book = IOFactory::load($xls);
        $this->assertEquals(3, $book->getSheetCount());

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('t_article', $content);
        $this->assertStringContainsString('t_comment', $content);
    }

    function test_include()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'include' => ['t_article'],
        ]));

        $xls = $describer->generateSpec($this->outdir);
        $book = IOFactory::load($xls);
        $this->assertEquals(2, $book->getSheetCount());

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('t_article', $content);
        $this->assertStringNotContainsString('t_comment', $content);
    }

    function test_exclude()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'exclude' => ['t_comment'],
        ]));

        $xls = $describer->generateSpec($this->outdir);
        $book = IOFactory::load($xls);
        $this->assertEquals(2, $book->getSheetCount());

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('t_article', $content);
        $this->assertStringNotContainsString('t_comment', $content);
    }

    function test_callback()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'schemaCallback' => function (Schema $schema) {
                $table = $schema->createTable('tmptable');
                $table->addColumn('xxx', 'integer');
                $table->setPrimaryKey(['xxx']);

                $schema->createView('tmpview', 'select 1');
            },
            'tableCallback'  => function (Table $table) {
                if ($table->getName() === 't_article') {
                    $table->addOption('comment', $table->getName() . 'ccc');
                }
                if ($table->getName() === 't_comment') {
                    return false;
                }
            },
            'viewCallback'   => function (View $view) {
                return false;
            },
            'columns'        => 'related',
        ]));

        $xls = @$describer->generateSpec($this->outdir);
        $book = IOFactory::load($xls);
        $this->assertEquals(3, $book->getSheetCount());

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('t_articleccc', $content);
        $this->assertStringContainsString('cluster_tmptable', $content);
    }

    function test_template()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'template' => __DIR__ . '/_files/sheets.xlsx',
            'sheets'   => [
                'sheet1' => [
                    'v1' => 'hoge',
                    'v2' => 'fuga',
                ],
                'sheet2' => [
                    'hoge' => 'HOGE',
                    'fuga' => 'FUGA',
                ],
                'index'  => [
                    'title' => 'schema title',
                ],
                'table'  => [
                    'title' => 'table title',
                ],
            ],
        ]));

        $xls = $describer->generateSpec($this->outdir);
        $book = IOFactory::load($xls);
        $this->assertEquals(5, $book->getSheetCount());

        $sheet0 = $book->getSheet(0);
        $this->assertEquals('hoge', $sheet0->getCell('B1')->getValue());
        $this->assertEquals('fuga', $sheet0->getCell('B2')->getValue());

        $sheet1 = $book->getSheet(1);
        $this->assertEquals('HOGE', $sheet1->getCell('B1')->getValue());
        $this->assertEquals('FUGA', $sheet1->getCell('B2')->getValue());

        $sheet2 = $book->getSheet(2);
        $this->assertEquals('schema title', $sheet2->getCell('A1')->getValue());

        $sheet3 = $book->getSheet(3);
        $this->assertEquals('table title', $sheet3->getCell('A1')->getValue());
    }

    function test_column()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'columns' => 'all',
        ]));
        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('title', $content);

        $describer = new Describer(TEST_DSN, $this->getConfig([
            'columns' => 'related',
        ]));
        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringNotContainsString('title', $content);
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

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('column_t_comment_article_id', $content);
        $this->assertStringContainsString('column_t_comment_comment_id', $content);
    }

    function test_delimiter()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'delimiter' => ':',
            'columns'   => 'related',
        ]));

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('PrimaryKey', $content);
        $this->assertStringNotContainsString('summary', $content);
    }

    function test_dot()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'graph'   => [
                'nodesep' => '99',
            ],
            'node'    => [
                'width' => '99',
            ],
            'edge'    => [
                'arrowsize' => '99',
            ],
            'columns' => 'related',
        ]));

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString('nodesep="99"', $content);
        $this->assertStringContainsString('width="99"', $content);
        $this->assertStringContainsString('arrowsize="99"', $content);
    }

    function test_pdf()
    {
        $describer = new Describer(TEST_DSN, $this->getConfig([
            'dot' => PHP_BINARY . ' --version',
        ]));

        $dot = $describer->generateErd($this->outdir, ['format' => 'pdf']);
        $content = file_get_contents($dot);
        $this->assertStringContainsString(PHP_VERSION, $content);
    }
}
