<?php

return function ($outdir, $dbname, $schemaObjects, $schemaDot) {
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $output = (static function () {
        extract(func_get_arg(1));
        extract(func_get_arg(2));
        ob_start();
        include func_get_arg(0);
        return ob_get_clean();
    })(__DIR__ . '/html/template.php', $schemaObjects, $schemaDot);

    file_put_contents("$outdir/$dbname.html", $output);
    return ["$outdir/$dbname.html"];
};
