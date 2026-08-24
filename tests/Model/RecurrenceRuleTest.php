<?php

declare(strict_types=1);


use OCA\AdCalendar\Model\RecurrenceRule;

$start = new DateTimeImmutable('2026-03-23T09:00:00+01:00');
$weekly = RecurrenceRule::get([
    'frequency' => 'weekly',
    'interval' => 1,
    'until' => '2026-04-06',
    'weekdays' => [1, 3],
    'timezone' => 'Europe/Berlin',
], $start);
$starts = $weekly->starts($start);
if (array_map(static fn(DateTimeImmutable $date): string => $date->format('Y-m-d H:i P'), $starts) !== [
    '2026-03-23 09:00 +01:00',
    '2026-03-25 09:00 +01:00',
    '2026-03-30 09:00 +02:00',
    '2026-04-01 09:00 +02:00',
    '2026-04-06 09:00 +02:00',
]) {
    throw new RuntimeException('Wöchentliche Serie hält Wochentage oder lokale Uhrzeit über die Sommerzeit nicht stabil.');
}

$daily = RecurrenceRule::get([
    'frequency' => 'daily', 'interval' => 2, 'until' => '2026-03-27', 'timezone' => 'Europe/Berlin',
], $start);
if (array_map(static fn(DateTimeImmutable $date): string => $date->format('Y-m-d'), $daily->starts($start)) !== ['2026-03-23', '2026-03-25', '2026-03-27']) {
    throw new RuntimeException('Tägliches Wiederholungsintervall ist falsch.');
}

$monthlyStart = new DateTimeImmutable('2026-01-31T09:00:00+01:00');
$monthly = RecurrenceRule::get([
    'frequency' => 'monthly', 'interval' => 1, 'until' => '2026-05-31', 'timezone' => 'Europe/Berlin',
], $monthlyStart);
if (array_map(static fn(DateTimeImmutable $date): string => $date->format('Y-m-d'), $monthly->starts($monthlyStart)) !== ['2026-01-31', '2026-03-31', '2026-05-31']) {
    throw new RuntimeException('Monate ohne den gewählten Kalendertag werden nicht sauber ausgelassen.');
}

foreach ([
    ['frequency' => 'yearly', 'interval' => 1, 'until' => '2027-03-23', 'timezone' => 'Europe/Berlin'],
    ['frequency' => 'daily', 'interval' => 0, 'until' => '2026-03-27', 'timezone' => 'Europe/Berlin'],
    ['frequency' => 'daily', 'interval' => 1, 'until' => '2026-03-22', 'timezone' => 'Europe/Berlin'],
    ['frequency' => 'weekly', 'interval' => 1, 'until' => '2026-03-30', 'weekdays' => [], 'timezone' => 'Europe/Berlin'],
] as $invalid) {
    try {
        RecurrenceRule::get($invalid, $start);
        throw new RuntimeException('Ungültige Serienregel wurde akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}

try {
    RecurrenceRule::get([
        'frequency' => 'daily', 'interval' => 1, 'until' => '2028-03-23', 'timezone' => 'Europe/Berlin',
    ], $start)->starts($start);
    throw new RuntimeException('Serienlimit von 500 Vorkommen wurde nicht erzwungen.');
} catch (InvalidArgumentException) {
}

echo "RecurrenceRuleTest: OK\n";
