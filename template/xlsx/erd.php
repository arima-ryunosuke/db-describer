<?php
/**
 * @var string $Schema
 * @var string $Erddot
 */

$h = function ($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
};
?>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $h($Schema) ?> ERD</title>

    <style>
        svg text {
            font-family: serif;
        }
    </style>

    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/viz.js/2.1.2/viz.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/viz.js/2.1.2/full.render.js"></script>
    <script defer src="https://unpkg.com/@panzoom/panzoom@4.6.0/dist/panzoom.min.js"></script>
    <script type="module">
        const viz = new Viz();
        const svg = await viz.renderSVGElement(document.querySelector('#relationship-dot').textContent);
        document.querySelector('#relationship-svg').appendChild(svg);

        const panzoom = Panzoom(svg);
        svg.parentElement.addEventListener('wheel', panzoom.zoomWithWheel);
    </script>
</head>

<body>
<section>
    <div id="relationship-dot" hidden><?= $h($Erddot) ?></div>
    <div id="relationship-svg"></div>
</section>
</body>

</html>
