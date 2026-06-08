<?php

require __DIR__ . '/../vendor/autoload.php';

$raw = json_decode(file_get_contents(__DIR__ . '/../storage/logs/autofill_test.json'), true);
$mapped = App\Support\CgiAutofillMapper::map($raw);

echo json_encode(['mapped' => $mapped], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
