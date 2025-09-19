<?php

use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/../vendor/autoload.php';

function getIterator($root)
{
    $rdi = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);
    $rii = new \RecursiveIteratorIterator($rdi);

    $DS = DIRECTORY_SEPARATOR;
    foreach ($rii as $it) {
        /** @var SplFileInfo $it */
        $path = $it->getRealPath();
        $ext = $it->getExtension();
        if (strpos($path, "{$DS}.") !== false) {
            continue;
        }
        if (strpos($path, "{$DS}vendor{$DS}") !== false) {
            continue;
        }
        if (!in_array(strtolower($ext), ['xls', 'xlsx'])) {
            continue;
        }

        yield $it;
    }
}

// delete metadata
$EMPTY = new Properties();
$EMPTY->setTitle('');
$EMPTY->setCreator('')->setCreated('2000-01-01 00:00:00');
$EMPTY->setLastModifiedBy('')->setModified('2000-01-01 00:00:00');
$KEYS = [
    'creator',
    'lastModifiedBy',
    'created',
    'modified',
    'title',
    'description',
    'subject',
    'keywords',
    'category',
    'manager',
    'company',
    'customProperties',
];
foreach (getIterator(__DIR__ . '/../') as $it) {
    $path = $it->getRealPath();
    $ext = $it->getExtension();

    $book = IOFactory::load($path);
    $properties = $book->getProperties();
    foreach ($KEYS as $key) {
        if ($properties->{"get$key"}() !== $EMPTY->{"get$key"}()) {
            $book->setProperties($EMPTY);
            IOFactory::createWriter($book, ucfirst($ext))->save($path);
            break;
        }
    }
}
