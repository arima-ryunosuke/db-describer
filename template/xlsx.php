<?php

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use ryunosuke\Excelate\Renderer;

return function ($outdir, $dbname, $schemaObjects, $schemaDot) {
    $result = [];

    // PhpSpreadsheet が内部で ZipArchive を使用してるので phar の中身は読めない（のでコピーする）
    $template = sys_get_temp_dir() . '/template.xlsx';
    copy(__DIR__ . '/xlsx/template.xlsx', $template);
    $book = IOFactory::load($template);

    $renderer = new Renderer();
    $renderer->registerEffector('Height', function (Cell $cell, $height) {
        $cell->getWorksheet()->getRowDimension($cell->getRow())->setRowHeight($height);
    });
    $renderer->registerEffector('Flat', function (Cell $cell, $array) {
        $array = array_filter($array, fn($v) => array_filter((array) $v, fn($v) => strlen($v ?? '')));
        $string = json_encode($array);
        $string = strtr($string, [
            '":' => ': ',
            ','  => ', ',
            '"'  => '',
            '['  => '',
            ']'  => '',
        ]);
        return trim($string, '{}');
    });

    $tablelist = $book->getSheetByName('index');
    $tablelist->setTitle($dbname);
    $renderer->renderSheet($tablelist, $schemaObjects);

    $templateSheet = $book->getSheetByName('table');
    $book->removeSheetByIndex($book->getIndex($templateSheet));
    foreach ($schemaObjects['Tables'] as $table) {
        $sheet = $templateSheet->copy();
        $sheet->setTitle($table['LogicalName'] ?: $table['Name']);
        $book->addSheet($sheet);
        $book->addDefinedName(new NamedRange($table['Name'], $sheet));
        $renderer->renderSheet($sheet, ['Table' => $table]);
    }

    $book->setActiveSheetIndex(0);
    $writer = IOFactory::createWriter($book, 'Xlsx');
    $writer->save("$outdir/$dbname.xlsx");
    $result[] = "$outdir/$dbname.xlsx";

    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $output = (static function () {
        extract(func_get_arg(1));
        extract(func_get_arg(2));
        ob_start();
        include func_get_arg(0);
        return ob_get_clean();
    })(__DIR__ . '/xlsx/erd.php', $schemaObjects, $schemaDot);

    file_put_contents("$outdir/$dbname.html", $output);
    $result[] = "$outdir/$dbname.html";

    return $result;
};
