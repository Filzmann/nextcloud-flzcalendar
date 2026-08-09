<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/lib/base.php';

set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, $error::class . ': ' . $error->getMessage() . "\n");
    exit(1);
});

use OCA\AdCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Service\CalendarService;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCA\AdCalendar\Service\ShiftCalendarReconciliationService;
use OCA\AdCalendar\Service\ShiftCalendarSyncService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\AdUrlaub\Model\Vacation;
use OCA\AdUrlaub\Repository\VacationRepository;
use OCP\IUserManager;
use Sabre\VObject\Reader;

/**
 * Zweck: Prüft den einseitigen persönlichen Abgleich gegen den realen internen Nextcloud-DAV-Adapter.
 * Vertrag: Eigene Dienste, Termine und bounded gelesene Urlaube werden idempotent veröffentlicht; Opt-out entfernt nur app-eigene Objekte.
 * Datenschutz: Der Test verwendet ausschließlich ein temporäres synthetisches Konto und räumt alle Daten auf.
 */

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$users = \OCP\Server::get(IUserManager::class);
$entries = \OCP\Server::get(CalendarEntryRepository::class);
$calendar = \OCP\Server::get(CalendarService::class);
$sync = \OCP\Server::get(ShiftCalendarSyncService::class);
$reconciliation = \OCP\Server::get(ShiftCalendarReconciliationService::class);
$publisher = \OCP\Server::get(PersonalCalendarPublisher::class);
$backend = \OCP\Server::get(CalDavBackend::class);
$targets = \OCP\Server::get(CalendarTargetConfig::class);
$vacations = \OCP\Server::get(VacationRepository::class);

$uid = 'adc-dav-smoke-' . bin2hex(random_bytes(5));
$user = $users->createUser($uid, bin2hex(random_bytes(24)));
if ($user === null) throw new RuntimeException('Temporäres DAV-Integrationskonto konnte nicht angelegt werden.');

$principal = 'principals/users/' . $uid;
$calendarId = null;
$entryId = null;
$appointmentId = null;
$vacationId = null;
$start = new DateTimeImmutable('tomorrow 09:00:00', new DateTimeZone('UTC'));
$end = $start->modify('+8 hours');

$calendarName = $targets->calendarName();
$findCalendar = static function () use ($backend, $principal, $calendarName): ?array {
    foreach ($backend->getCalendarsForUser($principal) as $candidate) {
        if (($candidate['{DAV:}displayname'] ?? '') === $calendarName) return $candidate;
    }
    return null;
};

try {
    $entryId = $calendar->save([
        'employeeUid' => $uid,
        'start' => $start->format(DATE_ATOM),
        'end' => $end->format(DATE_ATOM),
        'type' => 'shift',
        'title' => 'Synthetischer DAV-Dienst',
    ], null, $uid);
    $assert(in_array($uid, $entries->findEmployeeUidsWithShifts(), true), 'Das Konto mit Dienst fehlt in der Standardabgleichsauswahl.');
    $status = $sync->status($uid);
    $assert(($status['enabled'] ?? false) === true, 'Der persönliche DAV-Abgleich ist nicht standardmäßig aktiv.');
    $createdCalendar = $findCalendar();
    $assert($createdCalendar !== null, 'Der standardmäßig aktive private Kalender mit dem konfigurierten Namen wurde nicht angelegt.');
    $calendarId = (int)$createdCalendar['id'];

    $uri = 'adcalendar-shift-' . $entryId . '.ics';
    $object = $backend->getCalendarObject($calendarId, $uri);
    $assert($object !== null, 'Der vorhandene AD-Dienst wurde beim standardmäßig aktiven Abgleich nicht veröffentlicht.');
    $vcalendar = Reader::read((string)$object['calendardata']);
    $assert((string)$vcalendar->VEVENT->SUMMARY === 'Synthetischer DAV-Dienst', 'Der veröffentlichte DAV-Titel ist falsch.');

    $appointmentId = $calendar->save([
        'employeeUid' => $uid,
        'start' => $start->modify('+1 hour')->format(DATE_ATOM),
        'end' => $start->modify('+2 hours')->format(DATE_ATOM),
        'type' => 'appointment',
        'title' => 'Synthetischer DAV-Termin',
    ], null, $uid);
    $appointmentUri = 'adcalendar-appointment-' . $appointmentId . '.ics';
    $appointmentObject = $backend->getCalendarObject($calendarId, $appointmentUri);
    $assert($appointmentObject !== null, 'Der eigene Termin wurde nicht im privaten DAV-Kalender veröffentlicht.');
    $appointmentCalendar = Reader::read((string)$appointmentObject['calendardata']);
    $assert((string)$appointmentCalendar->VEVENT->SUMMARY === 'Synthetischer DAV-Termin', 'Der veröffentlichte Termintitel ist falsch.');

    $vacationStart = $start->modify('+2 days')->format('Y-m-d');
    $vacationEnd = $start->modify('+4 days')->format('Y-m-d');
    $vacationId = $vacations->save(Vacation::get([
        'employeeUid' => $uid,
        'startDate' => $vacationStart,
        'endDate' => $vacationEnd,
        'status' => Vacation::STATUS_PLANNED,
        'note' => 'Synthetische Notiz darf nicht exportiert werden',
    ]), $uid);
    $assert($reconciliation->reconcileEmployee($uid), 'Der Urlaub wurde vom gezielten Vollabgleich nicht verarbeitet.');
    $absenceObjects = array_values(array_filter(
        $backend->getCalendarObjects($calendarId),
        static fn(array $candidate): bool => str_starts_with((string)($candidate['uri'] ?? ''), 'adcalendar-absence-'),
    ));
    $assert(count($absenceObjects) === 1, 'Der geplante Urlaub wurde nicht als genau ein DAV-Objekt veröffentlicht.');
    $absenceUri = (string)$absenceObjects[0]['uri'];
    $absenceObject = $backend->getCalendarObject($calendarId, $absenceUri);
    $assert($absenceObject !== null, 'Das vollständige DAV-Urlaubsobjekt ist nicht lesbar.');
    $absenceData = (string)($absenceObject['calendardata'] ?? '');
    $assert(!str_contains($absenceData, 'Synthetische Notiz'), 'Eine Urlaubsnotiz wurde in den privaten DAV-Kalender exportiert.');
    $absenceCalendar = Reader::read($absenceData);
    $assert((string)$absenceCalendar->VEVENT->STATUS === 'TENTATIVE'
        && (string)$absenceCalendar->VEVENT->TRANSP === 'TRANSPARENT', 'Geplanter Urlaub ist im DAV-Kalender nicht tentative und transparent.');

    $vacations->save(Vacation::get([
        'id' => $vacationId,
        'employeeUid' => $uid,
        'startDate' => $vacationStart,
        'endDate' => $vacationEnd,
        'status' => Vacation::STATUS_APPROVED,
        'note' => 'Synthetische Notiz darf nicht exportiert werden',
    ]), $uid);
    $assert($reconciliation->reconcileEmployee($uid), 'Der genehmigte Urlaub wurde nicht nachgeführt.');
    $approvedObject = $backend->getCalendarObject($calendarId, $absenceUri);
    $assert($approvedObject !== null, 'Der Urlaubsstatuswechsel hat die stabile Objekt-ID verloren.');
    $approvedCalendar = Reader::read((string)$approvedObject['calendardata']);
    $assert((string)$approvedCalendar->VEVENT->STATUS === 'CONFIRMED'
        && (string)$approvedCalendar->VEVENT->TRANSP === 'OPAQUE', 'Genehmigter Urlaub ist im DAV-Kalender nicht confirmed und opaque.');

    $backend->deleteCalendarObject($calendarId, $uri, CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
    $assert($backend->getCalendarObject($calendarId, $uri) === null, 'Der DAV-Reparaturfall konnte nicht vorbereitet werden.');
    $assert($reconciliation->reconcileEmployee($uid), 'Der gezielte periodische DAV-Abgleich meldet einen Fehler.');
    $assert($backend->getCalendarObject($calendarId, $uri) !== null, 'Der periodische DAV-Abgleich hat den fehlenden Dienst nicht wiederhergestellt.');

    $calendar->save([
        'employeeUid' => $uid,
        'start' => $start->format(DATE_ATOM),
        'end' => $end->modify('+30 minutes')->format(DATE_ATOM),
        'type' => 'shift',
        'title' => 'Aktualisierter DAV-Dienst',
    ], $entryId, $uid);
    $updated = $backend->getCalendarObject($calendarId, $uri);
    $assert($updated !== null, 'Der aktualisierte AD-Dienst fehlt im DAV-Kalender.');
    $vcalendar = Reader::read((string)$updated['calendardata']);
    $assert((string)$vcalendar->VEVENT->SUMMARY === 'Aktualisierter DAV-Dienst', 'Die DAV-Aktualisierung wurde nicht übernommen.');

    $foreignUri = 'synthetic-foreign.ics';
    $backend->createCalendarObject($calendarId, $foreignUri, "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:synthetic-foreign@local\r\nDTSTAMP:20260809T120000Z\r\nDTSTART:20260810T120000Z\r\nDTEND:20260810T130000Z\r\nSUMMARY:Fremdobjekt\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
    $status = $sync->configure($uid, false);
    $assert(($status['enabled'] ?? true) === false, 'Persönliches DAV-Opt-out wurde nicht gespeichert.');
    $assert($backend->getCalendarObject($calendarId, $foreignUri) !== null, 'Opt-out hat ein fremdes DAV-Objekt entfernt.');
    foreach ([$uri, $appointmentUri, $absenceUri] as $ownedUri) {
        $assert($backend->getCalendarObject($calendarId, $ownedUri) === null, 'Opt-out hat ein app-eigenes DAV-Objekt bewahrt: ' . $ownedUri);
    }
    $assert(($sync->configure($uid, true)['enabled'] ?? false) === true, 'Persönlicher DAV-Abgleich ließ sich nicht reaktivieren.');
    foreach ([$uri, $appointmentUri, $absenceUri] as $ownedUri) {
        $assert($backend->getCalendarObject($calendarId, $ownedUri) !== null, 'Reaktivierung hat den vollständigen persönlichen Bestand nicht wiederhergestellt.');
    }

    $calendar->delete($appointmentId, '');
    $appointmentId = null;
    $calendar->delete($entryId, '');
    $entryId = null;
    $vacations->delete($vacationId);
    $vacationId = null;
    $backend->deleteCalendarObject($calendarId, $foreignUri, CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
    $status = $sync->configure($uid, false);
    $calendarId = null;
    $assert(($status['enabled'] ?? true) === false && $findCalendar() === null, 'Der leere private AD-Kalender wurde beim abschließenden Opt-out nicht entfernt.');

    echo "AD Kalender/DAV DDEV-Integration: OK\n";
} finally {
    if ($entryId !== null && $entries->find($entryId) !== null) $entries->delete($entryId);
    if ($appointmentId !== null && $entries->find($appointmentId) !== null) $entries->delete($appointmentId);
    if ($vacationId !== null) $vacations->delete($vacationId);
    try {
        $publisher->removeCalendar($uid);
    } catch (Throwable) {
        $remainingCalendar = $findCalendar();
        if ($remainingCalendar !== null) $backend->deleteCalendar((int)$remainingCalendar['id'], true);
    }
    $user->delete();
}
