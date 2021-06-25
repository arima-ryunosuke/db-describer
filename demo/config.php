<?php return [
    'tableCallback' => function ($table) {
        $table->addOption('comment', strtr($table->getOption('comment'), [':' => '_']));
    },
];
