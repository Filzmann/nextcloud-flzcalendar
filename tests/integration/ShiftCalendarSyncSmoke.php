<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/lib/base.php';

use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Service\CalendarService;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCA\AdCalendar\Service\ShiftCalendarReconciliationService;
use OCA\AdCalendar\Service\ShiftCalendarSyncService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUserManager;
use Sabre\VObject\Reader;

/**
 * Zweck: Prüft den einseitigen Dienstabgleich gegen den realen internen Nextcloud-DAV-Adapter.
 * Vertrag: Der standardmäßig aktive Abgleich erzeugt den privaten Kalender; Opt-out, Änderungen und Löschungen folgen der führenden AD-Datenquelle.
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
$publisher = \OCP\Server::get(ShiftCalendarPublisher::class);
$backend = \OCP\Server::get(CalDavBackend::class);
$targets = \OCP\Server::get(CalendarTargetConfig::class);

$uid = 'adc-dav-smoke-' . bin2hex(random_bytes(5));
$user = $users->createUser($uid, bin2hex(random_bytes(24)));
if ($user === null) throw new RuntimeException('Temporäres DAV-Integrationskonto konnte nicht angelegt werden.');

$principal = 'principals/users/' . $uid;
$calendarId = null;
$entryId = null;
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

    $calendar->delete($entryId, '');
    $entryId = null;
    $assert($backend->getCalendarObject($calendarId, $uri) === null, 'Der gelöschte AD-Dienst blieb im DAV-Kalender erhalten.');

    $status = $sync->configure($uid, false);
    $calendarId = null;
    $assert(($status['enabled'] ?? true) === false, 'Persönliches DAV-Opt-out wurde nicht gespeichert.');
    $assert($findCalendar() === null, 'Der leere private AD-Kalender wurde beim Opt-out nicht entfernt.');

    echo "AD Kalender/DAV DDEV-Integration: OK\n";
} finally {
    if ($entryId !== null && $entries->find($entryId) !== null) $entries->delete($entryId);
    try {
        $publisher->removeCalendar($uid);
    } catch (Throwable) {
        $remainingCalendar = $findCalendar();
        if ($remainingCalendar !== null) $backend->deleteCalendar((int)$remainingCalendar['id'], true);
    }
    $user->delete();
}
