<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/lib/base.php';

use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Service\AbsenceService;
use OCA\AdCalendar\Service\CalendarService;
use OCA\AdCalendar\Service\CalendarPreferenceService;
use OCA\AdCalendar\Service\DefaultShiftMaterializer;
use OCA\AdUrlaub\Model\Vacation;
use OCA\AdUrlaub\Repository\VacationRepository;
use OCA\AdUrlaub\Service\VacationService;
use OCP\IUserManager;

/**
 * Zweck: Prüft Standarddienst-Tombstones und die optionale Urlaubsintegration gegen die reale Nextcloud-Datenbank.
 * Zusammenspiel: AdCalendar-Materializer -> LocalBase-Events -> AdUrlaub-Listener/Repository.
 * Vertrag: Der Test verwendet ein temporäres Konto und entfernt alle erzeugten Daten auch nach einem Fehler.
 */

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$users = \OCP\Server::get(IUserManager::class);
$preferences = \OCP\Server::get(CalendarPreferenceService::class);
$materializer = \OCP\Server::get(DefaultShiftMaterializer::class);
$entries = \OCP\Server::get(CalendarEntryRepository::class);
$absences = \OCP\Server::get(AbsenceService::class);
$calendar = \OCP\Server::get(CalendarService::class);
$vacations = \OCP\Server::get(VacationService::class);
$vacationRepository = \OCP\Server::get(VacationRepository::class);

$uid = 'adc-integration-' . bin2hex(random_bytes(5));
$user = $users->createUser($uid, bin2hex(random_bytes(24)));
if ($user === null) throw new RuntimeException('Temporäres Integrationskonto konnte nicht angelegt werden.');

$timezone = new DateTimeZone('Europe/Berlin');
$weekStart = new DateTimeImmutable('monday this week 00:00:00', $timezone);
$weekEnd = $weekStart->modify('+7 days');
$date = $weekStart->format('Y-m-d');
$vacationId = null;
$appointmentId = null;

try {
    $defaults = [];
    for ($weekday = 1; $weekday <= 7; $weekday++) {
        $defaults[(string)$weekday] = [
            'enabled' => $weekday === 1,
            'start' => '08:00',
            'end' => '16:30',
        ];
    }
    $preferences->saveShiftDefaults($uid, $defaults);

    $materializer->syncWeek($weekStart, [$uid]);
    $occurrence = $entries->findDefaultOccurrence($uid, $date);
    $assert($occurrence !== null && !$occurrence->defaultDeleted(), 'Standarddienst wurde nicht materialisiert.');

    $occurrenceId = (int)$occurrence->id();
    $entries->deleteDefaultShift($occurrenceId, 'detach');
    $tombstone = $entries->findDefaultOccurrence($uid, $date);
    $assert($tombstone?->id() === $occurrenceId && $tombstone->defaultDeleted(), 'Gelöschter Standarddienst wurde nicht als Tombstone bewahrt.');

    $materializer->syncWeek($weekStart, [$uid]);
    $tombstone = $entries->findDefaultOccurrence($uid, $date);
    $assert($tombstone?->id() === $occurrenceId && $tombstone->defaultDeleted(), 'Tombstone wurde erneut materialisiert oder überschrieben.');
    $entries->delete($occurrenceId);

    $vacationId = $vacations->save([
        'employeeUid' => $uid,
        'startDate' => $date,
        'endDate' => $date,
        'status' => Vacation::STATUS_PLANNED,
        'note' => 'Integrationstest geplant',
    ], null, $uid);
    $plannedAbsences = $absences->query($weekStart, $weekEnd, [$uid]);
    $assert(count($plannedAbsences) === 1 && !$plannedAbsences[0]->approved(), 'Geplanter Urlaub wurde nicht über den Eventvertrag geliefert.');
    $materializer->syncWeek($weekStart, [$uid], $plannedAbsences);
    $assert($entries->findDefaultOccurrence($uid, $date) === null, 'Geplanter Urlaub blockiert den Standarddienst nicht.');
    try {
        $absences->assertShiftWritable($uid, $weekStart->setTime(8, 0), $weekStart->setTime(16, 30));
        throw new RuntimeException('Geplanter Urlaub blockiert einen manuellen Dienst nicht.');
    } catch (InvalidArgumentException) {
    }
    $appointmentId = $calendar->save([
        'employeeUid' => $uid,
        'start' => $weekStart->setTime(10, 0),
        'end' => $weekStart->setTime(11, 0),
        'type' => 'appointment',
        'title' => 'Neutraler Sperrtermin',
    ], null, $uid);
    $assert($appointmentId > 0, 'Sperrtermin wurde während geplantem Urlaub blockiert.');
    $entries->delete($appointmentId);
    $appointmentId = null;
    $vacationRepository->delete($vacationId);
    $vacationId = null;

    $vacationId = $vacations->save([
        'employeeUid' => $uid,
        'startDate' => $date,
        'endDate' => $date,
        'status' => Vacation::STATUS_APPROVED,
        'note' => 'Integrationstest genehmigt',
    ], null, $uid);
    $approvedAbsences = $absences->query($weekStart, $weekEnd, [$uid]);
    $assert(count($approvedAbsences) === 1 && $approvedAbsences[0]->approved(), 'Genehmigter Urlaub wurde nicht über den Eventvertrag geliefert.');
    $materializer->syncWeek($weekStart, [$uid], $approvedAbsences);
    $assert($entries->findDefaultOccurrence($uid, $date) === null, 'Genehmigter Urlaub blockiert den Standarddienst nicht.');

    echo "AD Kalender/Urlaub DDEV-Integration: OK\n";
} finally {
    if ($appointmentId !== null) $entries->delete($appointmentId);
    $remaining = $entries->findDefaultOccurrence($uid, $date);
    if ($remaining !== null) $entries->delete((int)$remaining->id());
    if ($vacationId !== null) $vacationRepository->delete($vacationId);
    $user->delete();
}
