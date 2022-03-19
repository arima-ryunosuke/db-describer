<?php

namespace ryunosuke\Test\DbDescriber\Command;

use ryunosuke\DbDescriber\Command\DescribeCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DescribeCommandTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @var Application
     */
    protected $app;

    protected $commandName = 'describe';

    protected $defaultArgs = [];

    protected function setup(): void
    {
        parent::setUp();

        $this->app = new Application('Test');
        $this->app->setCatchExceptions(false);
        $this->app->setAutoExit(false);
        $this->app->add(new DescribeCommand());
    }

    /**
     * @param array $inputArray
     * @return string
     */
    protected function runApp($inputArray)
    {
        $inputArray = [
                'command' => $this->commandName,
            ] + $inputArray + $this->defaultArgs;

        $input = new ArrayInput($inputArray);
        $output = new BufferedOutput();

        $this->app->run($input, $output);

        return $output->fetch();
    }

    function test_describe()
    {
        $outdir = __DIR__ . '/../../../output';
        array_map('unlink', glob("$outdir/*"));

        $this->runApp([
            'dsn'    => TEST_DSN,
            'outdir' => $outdir,
            '--dot'  => PHP_BINARY . ' --version',
            '--mode' => ["html"],
        ]);
        $this->assertFileExists("$outdir/" . parse_url(TEST_DSN)['path'] . '.html');

        $this->runApp([
            'dsn'    => TEST_DSN,
            'outdir' => $outdir,
            '--dot'  => PHP_BINARY . ' --version',
            '--mode' => ["spec"],
        ]);
        $this->assertFileExists("$outdir/" . parse_url(TEST_DSN)['path'] . '.xlsx');

        $this->runApp([
            'dsn'    => TEST_DSN,
            'outdir' => $outdir,
            '--dot'  => PHP_BINARY . ' --version',
            '--mode' => ["erd"],
        ]);
        $this->assertFileExists("$outdir/" . parse_url(TEST_DSN)['path'] . '.pdf');
    }
}
