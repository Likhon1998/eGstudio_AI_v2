<?php

$maps = require __DIR__ . '/occasion_presets/maps.php';
$poster = require __DIR__ . '/occasion_presets/poster.php';
$meta = require __DIR__ . '/occasion_presets/poster_meta.php';
$texts = require __DIR__ . '/occasion_presets/texts.php';
$design = require __DIR__ . '/occasion_presets/design_directives.php';
$semantics = require __DIR__ . '/occasion_presets/poster_semantics.php';

return array_merge($maps, $poster, $meta, $design, $semantics, [
    'textsMap' => $texts,
]);
