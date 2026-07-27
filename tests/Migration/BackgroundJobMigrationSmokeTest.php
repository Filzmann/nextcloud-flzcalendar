<?php

declare(strict_types=1);

$migration = file_get_contents(__DIR__ . '/../../lib/Migration/Version000007Date202607220002.php');
if ($migration === false) throw new RuntimeException('Hintergrundjob-Migration fehlt.');

foreach ([
    'IJobList',
    'postSchemaChange',
    'ReconcileShiftCalendarsJob::class',
    '->has(',
    '->add(',
] as $contract) {
    if (!str_contains($migration, $contract)) {
        throw new RuntimeException("Hintergrundjob-Migrationsvertrag fehlt: {$contract}");
    }
}

echo "BackgroundJobMigrationSmokeTest: OK\n";
