<?php

namespace ryunosuke\Test;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Trigger;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;

abstract class AbstractUnitTestCase extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $config = ['url' => TEST_DSN];
        $mparam = DriverManager::getConnection($config)->getParams();
        $dbname = isset($mparam['dbname']) ? $mparam['dbname'] : (isset($mparam['path']) ? $mparam['path'] : '');
        unset($mparam['url'], $mparam['dbname'], $mparam['path']);
        DriverManager::getConnection($mparam)->getSchemaManager()->dropAndCreateDatabase($dbname);

        $connection = DriverManager::getConnection($config);
        $connection->connect();

        $connection->getSchemaManager()->createTable(new Table('t_article',
            [
                new Column('article_id', Type::getType('integer'), ['Comment' => "PrimaryKey:summary"]),
                new Column('title', Type::getType('string')),
            ],
            [
                new Index('PRIMARY', ['article_id'], true, true),
                new Index('secondary', ['title'])
            ],
            [],
            [
                new Trigger('ThisIsTrigger', 'BEGIN
  INSERT INTO t_comment VALUES();
  INSERT INTO t_comment VALUES();
  INSERT INTO t_comment VALUES();
END', [
                    'Event'  => 'INSERT',
                    'Timing' => 'AFTER',
                ]),
            ],
            0,
            ['Comment' => "Article:summary"]
        ));
        $connection->getSchemaManager()->createTable(new Table('t_comment',
            [
                new Column('comment_id', Type::getType('integer'), ['autoincrement' => true, 'Comment' => "PrimaryKey:summary"]),
                new Column('article_id', Type::getType('integer')),
                new Column('comment', Type::getType('text')),
            ],
            [new Index('PRIMARY', ['comment_id'], true, true)],
            [
                new ForeignKeyConstraint(['article_id'], 't_article', ['article_id'], 'fk_articlecomment', [
                    'onUpdate' => 'CASCADE',
                    'onDelete' => 'CASCADE'
                ])
            ],
            [
                new Trigger('insert_before', 'INSERT INTO t_comment VALUES()', [
                    'Event'  => 'INSERT',
                    'Timing' => 'BEFORE',
                ]),
                new Trigger('delete_after', 'INSERT INTO t_comment VALUES()', [
                    'Event'  => 'DELETE',
                    'Timing' => 'AFTER',
                ]),
            ],
            0,
            ['Comment' => "Comment:summary"]
        ));
        $connection->getSchemaManager()->createView(new View('v_blog', '
            SELECT
              A.article_id,
              A.title,
              C.comment_id,
              C.comment
            FROM t_article A
            JOIN t_comment C ON A.article_id = C.article_id
        '));
    }

    public static function assertException($e, $callback)
    {
        if (is_string($e)) {
            $e = new \Exception($e);
        }

        $callback = self::forcedCallize($callback);

        try {
            $callback(...array_slice(func_get_args(), 2));
        }
        catch (\PHPUnit_Framework_Error $ex) {
            throw $ex;
        }
        catch (\Exception $ex) {
            self::assertInstanceOf(get_class($e), $ex);
            self::assertEquals($e->getCode(), $ex->getCode());
            if (strlen($e->getMessage()) > 0) {
                self::assertContains($e->getMessage(), $ex->getMessage());
            }
            return;
        }
        self::fail(get_class($e) . ' is not thrown.');
    }

    public static function forcedCallize($callable, $method = null)
    {
        if (func_num_args() == 2) {
            $callable = func_get_args();
        }

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $parts = explode('::', $callable);
            $method = new \ReflectionMethod($parts[0], $parts[1]);
            if (!$method->isPublic() && $method->isStatic()) {
                $method->setAccessible(true);
                return function () use ($method) {
                    return $method->invokeArgs(null, func_get_args());
                };
            }
        }

        if (is_array($callable) && count($callable) === 2) {
            try {
                $method = new \ReflectionMethod($callable[0], $callable[1]);
                if (!$method->isPublic()) {
                    $method->setAccessible(true);
                    return function () use ($callable, $method) {
                        return $method->invokeArgs($callable[0], func_get_args());
                    };
                }
            }
            catch (\ReflectionException $ex) {
                // __call を考慮するとどうしようもない
            }
        }

        return $callable;
    }
}
