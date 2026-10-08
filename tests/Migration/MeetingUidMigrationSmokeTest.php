<?php

declare(strict_types=1);

$migration = file_get_contents(__DIR__ . '/../../lib/Migration/Version000005Date202607130002.php');
$repository = file_get_contents(__DIR__ . '/../../lib/Repository/CalendarEntryRepository.php');
if ($migration === false || $repository === false) throw new RuntimeException('Meeting-Migrationsvertrag konnte nicht gelesen werden.');

foreach (['meeting_uid', "'notnull' => false", 'flz_calendar_meeting_uid'] as $contract) {
    if (!str_contains($migration, $contract)) throw new RuntimeException("Meeting-Migrationsvertrag fehlt: {$contract}");
}
foreach (['findMeeting', 'deleteMeeting', "'meeting_uid' => \$entry->meetingUid()"] as $contract) {
    if (!str_contains($repository, $contract)) throw new RuntimeException("Meeting-Repositoryvertrag fehlt: {$contract}");
}

echo "MeetingUidMigrationSmokeTest: OK\n";
