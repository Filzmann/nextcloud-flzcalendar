<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/lib'));
$productionFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $productionFiles[] = substr($file->getPathname(), strlen($root) + 1);
    }
}

$violations = [];
foreach ($productionFiles as $relativePath) {
    $contents = file_get_contents($root . '/' . $relativePath);
    if (!is_string($contents)) {
        $violations[] = $relativePath . ': Datei nicht lesbar';
        continue;
    }
    if (str_contains($contents, 'OCA\\LocalBase\\Organization')
        || str_contains($contents, 'OCA\\LocalBase\\Calendar\\CalendarContextSettingsService')
        || str_contains($contents, 'OCA\\LocalBase\\Calendar\\HolidayCalendarService')) {
        $violations[] = $relativePath . ': parallele LocalBase-Organisationsquelle';
    }
}

if ($violations !== []) {
    throw new RuntimeException(
        "Filzmann Kalender konsumiert die Organisation nicht ausschließlich über flz_organization V1:\n- "
        . implode("\n- ", $violations),
    );
}

echo "OrganizationConsumerBoundaryTest: OK\n";
