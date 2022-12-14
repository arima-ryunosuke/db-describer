<?php return [
    'delimiter'     => ':',
    'tableCallback' => function ($table) {
        $table->addOption('comment', strtr($table->getOption('comment'), [':' => '_']));
    },
];
