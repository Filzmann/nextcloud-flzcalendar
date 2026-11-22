<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$violations = [];
$roots = ['js', 'templates'];

foreach ($roots as $relativeRoot) {
    $directory = $root . '/' . $relativeRoot;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        if ($path === __FILE__) {
            continue;
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Testdatei konnte nicht gelesen werden: ' . $path);
        }
        foreach (['window.LocalBase', "addScript('localbase'", '/../../flz_shared/', '/../flz_shared/'] as $forbidden) {
            if (str_contains($contents, $forbidden)) {
                $violations[] = substr($path, strlen($root) + 1) . ': ' . $forbidden;
            }
        }
    }
}

foreach (['api-client.js', 'model.js', 'repository.js', 'ui.js', 'LICENSE', 'flz-shared.lock.json'] as $asset) {
    if (!is_file($root . '/js/shared/' . $asset)) {
        $violations[] = 'js/shared/' . $asset . ': app-lokales Kategorie-A-Artefakt fehlt';
    }
}

$lockPath = $root . '/js/shared/flz-shared.lock.json';
if (is_file($lockPath)) {
    $lock = json_decode((string)file_get_contents($lockPath), true, flags: JSON_THROW_ON_ERROR);
    if (($lock['schema'] ?? null) !== 1
        || ($lock['component'] ?? null) !== 'browser'
        || ($lock['app_id'] ?? null) !== 'flzcalendar'
        || ($lock['app_namespace'] ?? null) !== 'FlzCalendar'
        || ($lock['browser_global'] ?? null) !== 'window.OCA.FlzCalendar.Shared'
        || ($lock['packages']['browser']['name'] ?? null) !== '@filzmann/shared-browser'
        || ($lock['packages']['browser']['version'] ?? null) !== '0.1.0'
        || !preg_match('/^[a-f0-9]{64}$/', (string)($lock['packages']['browser']['sha256'] ?? ''))
        || array_keys($lock['packages'] ?? []) !== ['browser']) {
        $violations[] = 'js/shared/flz-shared.lock.json: Browser-Paket, Zielglobal oder Quelldigest ist inkohärent';
    }
}

$licensePath = $root . '/js/shared/LICENSE';
if (is_file($licensePath)) {
    $license = (string)file_get_contents($licensePath);
    if (!str_contains($license, 'SPDX-License-Identifier: AGPL-3.0-or-later')
        || !str_contains($license, 'GNU Affero General Public License')) {
        $violations[] = 'js/shared/LICENSE: gebündelte Lizenzmetadaten fehlen';
    }
}

$indexTemplate = (string)file_get_contents($root . '/templates/index.php');
$adminTemplate = (string)file_get_contents($root . '/templates/admin.php');
foreach (['api-client', 'model', 'repository', 'ui'] as $asset) {
    if (!str_contains($indexTemplate, "addScript('flzcalendar', 'shared/{$asset}')")) {
        $violations[] = "templates/index.php: shared/{$asset} wird nicht app-lokal geladen";
    }
}
if (!str_contains($adminTemplate, "addScript('flzcalendar', 'shared/api-client')")) {
    $violations[] = 'templates/admin.php: shared/api-client wird nicht app-lokal geladen';
}
if (strpos($indexTemplate, "addScript('flzcalendar', 'shared/ui')")
    > strpos($indexTemplate, "addScript('flzcalendar', 'components/calendar-cell')")) {
    $violations[] = 'templates/index.php: Shared-Primitives werden nach ihren Consumern geladen';
}

if ($violations !== []) {
    throw new RuntimeException(
        "Filzmann Kalender besitzt weiterhin private Browser-Laufzeitkopplungen:\n- "
        . implode("\n- ", $violations),
    );
}

echo "LocalBaseIndependenceContractTest: OK\n";
