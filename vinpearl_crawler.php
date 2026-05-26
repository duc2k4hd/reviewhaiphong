<?php

$outputFile = __DIR__ . '/vinpearl_links.txt';

$startPage = 1;
$endPage = 433;

$baseUrl = 'https://vinpearl.com/vi/news/travel-guide?page=';

$lines = [];

for ($page = $startPage; $page <= $endPage; $page++) {
    $lines[] = $baseUrl . $page;
}

file_put_contents($outputFile, implode(PHP_EOL, $lines));

echo "Đã ghi xong " . count($lines) . " link vào file: " . $outputFile . PHP_EOL;