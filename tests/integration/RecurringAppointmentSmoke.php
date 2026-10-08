<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/lib/base.php';

use OCA\FlzCalendar\Repository\CalendarEntryRepository;
use OCA\FlzCalendar\Service\RecurringAppointmentService;
use OCP\IUserManager;

/**
 * Zweck: Prüft Serienerstellung, Gesamtänderung und Löschung gegen die reale Nextcloud-Datenbank.
 * Vertrag: Der Test verwendet ein temporäres synthetisches Konto und entfernt alle Vorkommen auch nach einem Fehler.
 */

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$users = \OCP\Server::get(IUserManager::class);
$entries = \OCP\Server::get(CalendarEntryRepository::class);
$recurrences = \OCP\Server::get(RecurringAppointmentService::class);
$uid = 'flz-calendar-series-smoke-' . bin2hex(random_bytes(5));
$user = $users->createUser($uid, bin2hex(random_bytes(24)));
if ($user === null) throw new RuntimeException('Temporäres Serienkonto konnte nicht angelegt werden.');

$timezone = new DateTimeZone('Europe/Berlin');
$start = new DateTimeImmutable('monday next week 09:00:00', $timezone);
$seriesUid = null;

try {
    $ids = $recurrences->create([
        'employeeUid' => $uid,
        'start' => $start->format(DATE_ATOM),
        'end' => $start->modify('+1 hour')->format(DATE_ATOM),
        'type' => 'appointment',
        'title' => 'Synthetischer Serientermin',
    ], [
        'frequency' => 'weekly',
        'interval' => 1,
        'until' => $start->modify('+2 weeks')->format('Y-m-d'),
        'weekdays' => [(int)$start->format('N')],
        'timezone' => $timezone->getName(),
    ], $uid);

    $first = $entries->find($ids[0]);
    $seriesUid = $first?->seriesUid();
    $assert(count($ids) === 3 && $seriesUid !== null, 'Die reale Persistenz hat die Serie nicht vollständig angelegt.');
    $stored = $entries->findSeries($seriesUid);
    $assert(count($stored) === 3, 'Die reale Serienabfrage liefert nicht alle Vorkommen.');
    $assert(array_filter($stored, static fn($entry): bool => $entry->start()->setTimezone($timezone)->format('H:i') !== '09:00') === [], 'Die reale Serie hält die lokale Uhrzeit nicht stabil.');

    $updatedIds = $recurrences->updateSeries($stored[1], [
        'employeeUid' => $uid,
        'start' => $stored[1]->start()->setTimezone($timezone)->setTime(10, 30)->format(DATE_ATOM),
        'end' => $stored[1]->start()->setTimezone($timezone)->setTime(12, 0)->format(DATE_ATOM),
        'type' => 'appointment',
        'title' => 'Aktualisierte synthetische Serie',
    ], $uid);
    $updated = $entries->findSeries($seriesUid);
    $assert($updatedIds === $ids && count($updated) === 3, 'Die reale Gesamtänderung ist unvollständig.');
    $assert(array_filter($updated, static fn($entry): bool => $entry->title() !== 'Aktualisierte synthetische Serie' || $entry->durationMinutes() !== 90) === [], 'Titel oder Dauer wurden nicht atomar aktualisiert.');

    $recurrences->deleteSeries($seriesUid);
    $assert($entries->findSeries($seriesUid) === [], 'Die reale Serienlöschung hat Vorkommen zurückgelassen.');
    $seriesUid = null;

    echo "Filzmann Kalender/Serientermine DDEV-Integration: OK\n";
} finally {
    if ($seriesUid !== null) $entries->deleteSeries($seriesUid);
    $user->delete();
}
