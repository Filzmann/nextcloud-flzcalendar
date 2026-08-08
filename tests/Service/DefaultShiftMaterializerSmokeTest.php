<?php

declare(strict_types=1);

require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
require_once __DIR__ . '/../../lib/Service/DefaultShiftOccurrenceFactory.php';

use OCA\AdCalendar\Service\DefaultShiftOccurrenceFactory;

$factory = new DefaultShiftOccurrenceFactory();
$timezone = new DateTimeZone('Europe/Berlin');
$day = $factory->create('admin', '2026-07-13', ['start' => '08:00', 'end' => '16:30'], $timezone);
if ($day->defaultDate() !== '2026-07-13' || $day->durationMinutes() !== 510 || $day->start()->setTimezone($timezone)->format('H:i P') !== '08:00 +02:00') {
    throw new RuntimeException('Standarddienst wurde nicht als lokales Tagesvorkommen erzeugt.');
}
$overnight = $factory->create('admin', '2026-07-13', ['start' => '20:00', 'end' => '06:00'], $timezone);
if ($overnight->durationMinutes() !== 600 || $overnight->end()->setTimezone($timezone)->format('Y-m-d H:i') !== '2026-07-14 06:00') {
    throw new RuntimeException('Standard-Nachtdienst endet nicht am Folgetag.');
}

$materializer = file_get_contents(__DIR__ . '/../../lib/Service/DefaultShiftMaterializer.php');
$repository = file_get_contents(__DIR__ . '/../../lib/Repository/CalendarEntryRepository.php');
$calendar = file_get_contents(__DIR__ . '/../../lib/Service/CalendarService.php');
$meetings = file_get_contents(__DIR__ . '/../../lib/Service/MeetingService.php');
$migration = file_get_contents(__DIR__ . '/../../lib/Migration/Version000004Date202607130001.php');
foreach ([$materializer, $repository, $calendar, $meetings, $migration] as $source) {
    if ($source === false) throw new RuntimeException('Standarddienst-Vertragsdatei konnte nicht gelesen werden.');
}
foreach (['storedShiftDefaults', 'findDefaultOccurrence', 'defaultDeleted()', 'defaultModified()', 'removeGeneratedDefault', 'attachContainedAppointments', 'ShiftCalendarSyncService'] as $contract) {
    if (!str_contains($materializer, $contract)) throw new RuntimeException("Materialisierungsvertrag fehlt: {$contract}");
}
if (!str_contains($materializer, 'absence->overlaps')) throw new RuntimeException('Urlaubsblockade fehlt.');
if (str_contains($materializer, 'absence->approved()')) throw new RuntimeException('Geplanter Urlaub blockiert Standarddienste noch nicht.');
foreach (['OCP\\Config\\IUserConfig', 'userConfig->getValueString'] as $contract) if (!str_contains($materializer, $contract)) throw new RuntimeException("Moderner Benutzerkonfigurationsvertrag fehlt: {$contract}");
if (str_contains($materializer, 'config->getUserValue')) throw new RuntimeException('Materialisierung verwendet noch den veralteten IConfig-Benutzerwertzugriff.');
foreach (['deleteDefaultShift', "set('default_deleted'", 'default_date', 'default_modified'] as $contract) {
    if (!str_contains($repository . $migration, $contract)) throw new RuntimeException("Ausnahme-/Persistenzvertrag fehlt: {$contract}");
}
if (!str_contains($repository, 'if ($insert) $qb->setValue') || !str_contains($repository, 'else $qb->set($field')) {
    throw new RuntimeException('Insert und Update verwenden nicht ihre jeweiligen QueryBuilder-Verträge.');
}
if (!str_contains($repository, '$qb->getLastInsertId()') || str_contains($repository, 'db->lastInsertId')) {
    throw new RuntimeException('Kalendereinträge verwenden nicht den modernen QueryBuilder-ID-Vertrag.');
}
if (substr_count($calendar, 'defaultShifts->syncWeek') !== 1 || substr_count($meetings, 'defaultShifts->syncWeek') !== 1 || !str_contains($calendar, "'defaultModified' => true")) {
    throw new RuntimeException('Wochenansicht, Meetinglücken oder individuelle Bearbeitung umgehen Standarddienste.');
}
if (substr_count($materializer, 'shiftSync->publish') !== 1 || substr_count($materializer, 'shiftSync->remove') < 2) {
    throw new RuntimeException('Materialisierte oder entfernte Standarddienste werden nicht im privaten Kalender gespiegelt.');
}

echo "DefaultShiftMaterializerSmokeTest: OK\n";
