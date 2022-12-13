<?php

namespace ryunosuke\DbDescriber\Command;

use ryunosuke\DbDescriber\Describer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DescribeCommand extends Command
{
    protected function configure()
    {
        $this->setName('describe')->setDescription('describe Database.');
        $this->setDefinition([
            new InputArgument('dsn', InputArgument::REQUIRED, 'Specify Database DSN'),
            new InputArgument('outdir', InputArgument::OPTIONAL, 'Specify Output directory'),
            new InputOption('include', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify Include table', []),
            new InputOption('exclude', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify Exclude table', []),
            new InputOption('delimiter', 'l', InputOption::VALUE_REQUIRED, 'Specify Comment delimiter for summary', "\n"),
            new InputOption('template', 't', InputOption::VALUE_REQUIRED, 'Specify Spec template'),
            new InputOption('columns', 'c', InputOption::VALUE_REQUIRED, 'Specify Erd columns([related|all])', 'related'),
            new InputOption('config', 'C', InputOption::VALUE_REQUIRED, 'Specify Configuration filepath', 'config.php'),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(var_export($input->getOptions(), true), OutputInterface::VERBOSITY_DEBUG);

        $default = [
            // 共通
            'include'            => $input->getOption('include'),
            'exclude'            => $input->getOption('exclude'),
            'delimiter'          => $input->getOption('delimiter'),
            'relation'           => [],
            'connectionCallback' => function () { },
            'schemaCallback'     => function () { },
            'tableCallback'      => function () { },
            'viewCallback'       => function () { },
            // spec 用
            'vars'               => [],
            // erd 用
            'columns'            => $input->getOption('columns'),
            'graph'              => [],
            'node'               => [],
            'edge'               => [],
        ];
        $default['template'] = $input->getOption('template') ?: __DIR__ . '/../../template/standard.phtml';

        $config = (file_exists($input->getOption('config')) ? require $input->getOption('config') : []) + $default;

        $describer = new Describer($input->getArgument('dsn'), $config);

        $outdir = $input->getArgument('outdir') ?: getcwd();
        @mkdir($outdir, 0777, true);

        $describer->generateHtml($outdir);

        return 0;
    }
}
