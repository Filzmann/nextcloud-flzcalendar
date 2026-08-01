<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$catalogs = [];
foreach (['de', 'en_GB'] as $locale) {
    $decoded = json_decode((string)file_get_contents("{$root}/l10n/{$locale}.json"), true, 512, JSON_THROW_ON_ERROR);
    $catalogs[$locale] = $decoded['translations'] ?? [];
}

$files = glob("{$root}/templates/*.php") ?: [];
$files = array_merge($files, glob("{$root}/templates/partials/*.php") ?: []);
foreach (glob("{$root}/lib/{CalendarSync,Command,Controller,Listener,Settings}/*.php", GLOB_BRACE) ?: [] as $file) $files[] = $file;

$keys = [];
foreach ($files as $file) {
    $source = (string)file_get_contents($file);
    if (preg_match_all('/(?:\\$l|l10n)->t\(\'([^\']+)\'/', $source, $matches) === false) {
        throw new RuntimeException("L10N-Quelltexte konnten nicht gelesen werden: {$file}");
    }
    foreach ($matches[1] as $key) $keys[$key] = $file;
    if (preg_match_all('/errors->create\(\'[^\']+\',\s*\'([^\']+)\'/', $source, $errorMatches) === false) {
        throw new RuntimeException("API-L10N-Quelltexte konnten nicht gelesen werden: {$file}");
    }
    foreach ($errorMatches[1] as $key) $keys[$key] = $file;
}

foreach ($catalogs as $locale => $translations) {
    foreach ($keys as $key => $file) {
        if (!array_key_exists($key, $translations)) {
            throw new RuntimeException("L10N-Schlüssel fehlt in {$locale} ({$file}): {$key}");
        }
    }
}

echo 'L10nCatalogContractTest: OK (' . count($keys) . " server keys)\n";
