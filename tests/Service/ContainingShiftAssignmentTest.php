<?php

declare(strict_types=1);


use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\FlzCalendar\Service\ContainingShiftAssignment;

$entry = static fn(array $payload): CalendarEntry => CalendarEntry::get($payload + [
    'employeeUid' => 'demo',
    'start' => '2026-07-15T10:00:00+02:00',
    'end' => '2026-07-15T11:00:00+02:00',
    'title' => '',
]);

$appointment = $entry(['type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Besprechung']);
$shift = $entry([
    'id' => 42,
    'type' => CalendarEntry::TYPE_SHIFT,
    'start' => '2026-07-15T08:00:00+02:00',
    'end' => '2026-07-15T16:00:00+02:00',
]);
$secondShift = $entry([
    'id' => 43,
    'type' => CalendarEntry::TYPE_SHIFT,
    'start' => '2026-07-15T09:00:00+02:00',
    'end' => '2026-07-15T17:00:00+02:00',
]);

$assignment = new ContainingShiftAssignment();
if ($assignment->assign($appointment, [])->parentEntryId() !== null) {
    throw new RuntimeException('Ein Termin ohne enthaltenden Dienst darf keine Parent-ID erhalten.');
}
if ($assignment->assign($appointment, [$shift])->parentEntryId() !== 42) {
    throw new RuntimeException('Der eindeutig enthaltende Dienst wurde nicht zugeordnet.');
}

$thrown = false;
try {
    $assignment->assign($appointment, [$shift, $secondShift]);
} catch (InvalidArgumentException $error) {
    $thrown = str_contains($error->getMessage(), 'mehreren Diensten');
}
if (!$thrown) {
    throw new RuntimeException('Mehrdeutige Dienstzuordnungen werden nicht abgewiesen.');
}

echo "ContainingShiftAssignmentTest: OK\n";
