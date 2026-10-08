<?php

declare(strict_types=1);

$migration = file_get_contents(__DIR__ . '/../../lib/Migration/Version000006Date202607220001.php');
$repository = file_get_contents(__DIR__ . '/../../lib/Repository/CalendarEntryRepository.php');
if ($migration === false || $repository === false) throw new RuntimeException('Serien-Migrationsvertrag konnte nicht gelesen werden.');

foreach (['series_uid', 'series_timezone', "'notnull' => false", 'flz_calendar_series_uid'] as $contract) {
    if (!str_contains($migration, $contract)) throw new RuntimeException("Serien-Migrationsvertrag fehlt: {$contract}");
}
foreach (['findSeries', 'deleteSeries', "'series_uid' => \$entry->seriesUid()", "'series_timezone' => \$entry->seriesTimezone()"] as $contract) {
    if (!str_contains($repository, $contract)) throw new RuntimeException("Serien-Repositoryvertrag fehlt: {$contract}");
}

echo "RecurrenceMigrationSmokeTest: OK\n";
