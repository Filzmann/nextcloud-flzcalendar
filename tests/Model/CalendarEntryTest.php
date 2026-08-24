<?php

declare(strict_types=1);


use OCA\AdCalendar\Model\CalendarEntry;

$shift = CalendarEntry::get([
    'employeeUid' => 'test-person-1',
    'start' => '2026-07-13T08:00:00+02:00',
    'end' => '2026-07-13T16:00:00+02:00',
    'type' => CalendarEntry::TYPE_SHIFT,
]);
$appointment = CalendarEntry::get([
    'employeeUid' => 'test-person-1',
    'start' => '2026-07-13T10:00:00+02:00',
    'end' => '2026-07-13T11:00:00+02:00',
    'type' => CalendarEntry::TYPE_APPOINTMENT,
    'title' => 'Neutraler Testtermin',
]);

if ($shift->durationMinutes() !== 480 || !$appointment->isWithin($shift)) {
    throw new RuntimeException('Zeitraumvertrag ist verletzt.');
}

$laterShift = CalendarEntry::get(['employeeUid' => 'test-person-1', 'start' => '2026-07-13T15:00:00+02:00', 'end' => '2026-07-13T18:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT]);
$touchingShift = CalendarEntry::get(['employeeUid' => 'test-person-1', 'start' => '2026-07-13T16:00:00+02:00', 'end' => '2026-07-13T18:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT]);
if (!$shift->overlaps($laterShift)) throw new RuntimeException('Echte Dienstueberschneidung wurde nicht erkannt.');
if ($shift->overlaps($touchingShift)) throw new RuntimeException('Direkt anschliessende Dienste wurden als Ueberschneidung behandelt.');
$overnight = CalendarEntry::get(['employeeUid' => 'test-person-1', 'start' => '2026-07-12T22:00:00+02:00', 'end' => '2026-07-13T06:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT]);
if ($overnight->durationWithin(new DateTimeImmutable('2026-07-13T00:00:00+02:00'), new DateTimeImmutable('2026-07-20T00:00:00+02:00')) !== 360) throw new RuntimeException('Wochenanteil eines Nachtdiensts ist falsch.');

$linked = CalendarEntry::get(array_merge($appointment->toArray(), ['parentEntryId' => 42]));
if ($linked->parentEntryId() !== 42) throw new RuntimeException('Explizite Dienst-Termin-Zuordnung ging verloren.');

$meeting = CalendarEntry::get(array_merge($appointment->toArray(), ['meetingUid' => 'meeting_2026-07-13_demo']));
if ($meeting->meetingUid() !== 'meeting_2026-07-13_demo' || $meeting->toArray()['meetingUid'] !== 'meeting_2026-07-13_demo') {
    throw new RuntimeException('Gemeinsame Meeting-Referenz ging bei Hydration oder Serialisierung verloren.');
}

$series = CalendarEntry::get(array_merge($appointment->toArray(), ['seriesUid' => 'series_2026_demo', 'seriesTimezone' => 'Europe/Berlin']));
if ($series->seriesUid() !== 'series_2026_demo' || $series->seriesTimezone() !== 'Europe/Berlin' || $series->toArray()['seriesUid'] !== 'series_2026_demo') {
    throw new RuntimeException('Serienreferenz ging bei Hydration oder Serialisierung verloren.');
}

$defaultShift = CalendarEntry::get(array_merge($shift->toArray(), ['defaultDate' => '2026-07-13']));
if ($defaultShift->defaultDate() !== '2026-07-13' || $defaultShift->defaultModified() || $defaultShift->defaultDeleted()) {
    throw new RuntimeException('Standarddienst-Metadaten wurden nicht stabil hydratisiert.');
}

try {
    CalendarEntry::get(array_merge($shift->toArray(), ['parentEntryId' => 42]));
    throw new RuntimeException('Dienst mit Parent wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

try {
    CalendarEntry::get(array_merge($shift->toArray(), ['meetingUid' => 'unzulässig']));
    throw new RuntimeException('Dienst oder ungültige Meeting-Kennung wurde akzeptiert.');
} catch (InvalidArgumentException) {
}


foreach ([
    array_merge($shift->toArray(), ['seriesUid' => 'series_demo', 'seriesTimezone' => 'Europe/Berlin']),
    array_merge($appointment->toArray(), ['seriesUid' => 'series_demo']),
    array_merge($appointment->toArray(), ['seriesUid' => 'series_demo', 'seriesTimezone' => 'Keine/Zeitzone']),
    array_merge($appointment->toArray(), ['meetingUid' => 'meeting_demo', 'seriesUid' => 'series_demo', 'seriesTimezone' => 'Europe/Berlin']),
] as $invalidSeries) {
    try {
        CalendarEntry::get($invalidSeries);
        throw new RuntimeException('Ungültige Serienreferenz wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}

try {
    CalendarEntry::get([
        'employeeUid' => 'test-person-1',
        'start' => '2026-07-13T10:00:00+02:00',
        'end' => '2026-07-13T11:00:00+02:00',
        'type' => CalendarEntry::TYPE_APPOINTMENT,
    ]);
    throw new RuntimeException('Termin ohne Titel wurde akzeptiert.');
} catch (InvalidArgumentException) {
}

echo "CalendarEntryTest: OK\n";
