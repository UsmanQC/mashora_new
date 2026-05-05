#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Downloads intl-tel-input bundled country data (iso2 → dialCode) and writes
 * config/country_phone_territories.json for thepatient phone picker.
 */
$src = file_get_contents('https://unpkg.com/intl-tel-input@23.0.0/build/js/data.js');
if ($src === false) {
    fwrite(STDERR, "Failed to download data.js\n");
    exit(1);
}

if (! preg_match_all('/\[\s*"([a-z]{2})"\s*,\s*"(\d+)"[^\]]*\]/', $src, $matches, PREG_SET_ORDER)) {
    fwrite(STDERR, "Could not parse country rows\n");
    exit(1);
}

$seen = [];

foreach ($matches as $m) {
    $iso = strtoupper($m[1]);
    $dial = $m[2];
    if (isset($seen[$iso])) {
        continue;
    }
    $seen[$iso] = ['iso' => $iso, 'dial' => $dial];
}

$rows = array_values($seen);

usort($rows, static fn ($a, $b) => strcmp($a['iso'], $b['iso']));

$target = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'country_phone_territories.json';

$json = json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";

if (file_put_contents($target, $json) === false) {
    fwrite(STDERR, "Failed to write {$target}\n");
    exit(1);
}

fwrite(STDOUT, 'Wrote '.count($rows).' territories to '.$target.PHP_EOL);
