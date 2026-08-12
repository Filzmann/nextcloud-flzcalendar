<?php

declare(strict_types=1);


use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Service\MeetingAvailabilityService;
use OCA\LocalBase\Calendar\AbsenceInterval;

$entry = static fn(string $uid, string $start, string $end, string $type, string $title = ''): CalendarEntry => CalendarEntry::get(compact('uid', 'start', 'end', 'type', 'title') + ['employeeUid' => $uid]);
$shift = CalendarEntry::TYPE_SHIFT;
$appointment = CalendarEntry::TYPE_APPOINTMENT;
$entries = [
    $entry('a', '2026-07-13T08:00:00+02:00', '2026-07-13T16:00:00+02:00', $shift),
    $entry('a', '2026-07-13T10:00:00+02:00', '2026-07-13T11:00:00+02:00', $appointment, 'A belegt'),
    $entry('b', '2026-07-13T09:00:00+02:00', '2026-07-13T17:00:00+02:00', $shift),
    $entry('b', '2026-07-13T13:00:00+02:00', '2026-07-13T14:00:00+02:00', $appointment, 'B belegt'),
    $entry('c', '2026-07-13T09:30:00+02:00', '2026-07-13T15:30:00+02:00', $shift),
    $entry('c', '2026-07-13T11:30:00+02:00', '2026-07-13T12:00:00+02:00', $appointment, 'C belegt'),
];
$service = new MeetingAvailabilityService();
$start = new DateTimeImmutable('2026-07-13T00:00:00+02:00');
$end = $start->modify('+7 days');
$gaps = $service->find($entries, ['a', 'b', 'c'], $start, $end, 60);
if (count($gaps) !== 2 || $gaps[0]['durationMinutes'] !== 60 || $gaps[1]['durationMinutes'] !== 90) {
    throw new RuntimeException('Gemeinsame Meetinglücken oder Terminabzug sind falsch.');
}
if ($service->find($entries, ['a', 'b', 'c'], $start, $end, 120) !== []) {
    throw new RuntimeException('Zu kurze Lücken wurden für ein 120-Minuten-Meeting angeboten.');
}
$approved = new AbsenceInterval('a', new DateTimeImmutable('2026-07-13T09:00:00+02:00'), new DateTimeImmutable('2026-07-13T16:00:00+02:00'), AbsenceInterval::STATUS_APPROVED);
if ($service->find($entries, ['a','b','c'], $start, $end, 60, [$approved]) !== []) throw new RuntimeException('Genehmigter Urlaub wurde in Meetinglücken nicht blockiert.');
$planned = new AbsenceInterval('a', new DateTimeImmutable('2026-07-13T09:00:00+02:00'), new DateTimeImmutable('2026-07-13T16:00:00+02:00'), AbsenceInterval::STATUS_PLANNED);
if (count($service->find($entries, ['a','b','c'], $start, $end, 60, [$planned])) !== 2) throw new RuntimeException('Geplanter Urlaub darf Meetinglücken nicht blockieren.');

echo "MeetingAvailabilityServiceTest: OK\n";
