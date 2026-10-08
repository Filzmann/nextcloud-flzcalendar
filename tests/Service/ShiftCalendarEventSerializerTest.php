<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }

namespace {

use OCA\FlzCalendar\CalendarSync\ShiftCalendarEventSerializer;
use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\LocalBase\Calendar\AbsenceInterval;
use OCP\IL10N;

$l10n = new class implements IL10N {
    public function t(string $text, array $parameters = []): string {
        return match ($text) {
            'Shift' => 'Übersetzter Dienst',
            'Automatically synchronised from Filzmann Calendar. Please make changes there.' => 'Übersetzte Kalenderbeschreibung.',
            default => strtr($text, $parameters),
        };
    }
};
$serializer = new ShiftCalendarEventSerializer($l10n);
$shift = CalendarEntry::get([
    'id' => 17,
    'employeeUid' => 'sync-person',
    'start' => '2026-07-20T08:00:00+02:00',
    'end' => '2026-07-20T16:00:00+02:00',
    'type' => CalendarEntry::TYPE_SHIFT,
    'title' => "Frühdienst, Büro; Nordost\nHinweis",
]);
$ics = $serializer->serialize($shift, '20260718T090000Z');

foreach ([
    "BEGIN:VCALENDAR\r\n",
    "UID:flzcalendar-shift-17@local\r\n",
    "DTSTAMP:20260718T090000Z\r\n",
    "DTSTART:20260720T060000Z\r\n",
    "DTEND:20260720T140000Z\r\n",
    "SUMMARY:Frühdienst\\, Büro\\; Nordost\\nHinweis\r\n",
    "CLASS:PRIVATE\r\n",
    "X-FLZ-CALENDAR-ENTRY-ID:17\r\n",
    "END:VCALENDAR\r\n",
] as $contract) {
    if (!str_contains($ics, $contract)) throw new RuntimeException("ICS-Vertrag fehlt: {$contract}");
}
if ($serializer->objectUri($shift) !== 'flzcalendar-shift-17.ics') throw new RuntimeException('Deterministische Objekt-URI fehlt.');

$appointment = CalendarEntry::get(array_replace($shift->toArray(), [
    'id' => 18,
    'type' => CalendarEntry::TYPE_APPOINTMENT,
    'title' => 'Eigener Termin',
]));
$appointmentIcs = $serializer->serialize($appointment, '20260718T090000Z');
if ($serializer->objectUri($appointment) !== 'flzcalendar-appointment-18.ics'
    || !str_contains($appointmentIcs, "UID:flzcalendar-appointment-18@local\r\n")
    || !str_contains($appointmentIcs, "SUMMARY:Eigener Termin\r\n")
    || !str_contains($appointmentIcs, "X-FLZ-CALENDAR-ENTRY-TYPE:appointment\r\n")) {
    throw new RuntimeException('Eigener Termin besitzt keinen stabilen privaten ICS-Vertrag.');
}

$planned = new AbsenceInterval(
    'sync-person',
    new DateTimeImmutable('2026-07-21T00:00:00Z'),
    new DateTimeImmutable('2026-07-24T00:00:00Z'),
    AbsenceInterval::STATUS_PLANNED,
);
$plannedUri = $serializer->absenceObjectUri($planned);
$plannedIcs = $serializer->serializeAbsence($planned, '20260718T090000Z');
if (!preg_match('/^flzcalendar-absence-[a-f0-9]{64}\.ics$/', $plannedUri)
    || !str_contains($plannedIcs, "DTSTART;VALUE=DATE:20260721\r\n")
    || !str_contains($plannedIcs, "DTEND;VALUE=DATE:20260724\r\n")
    || !str_contains($plannedIcs, "STATUS:TENTATIVE\r\n")
    || !str_contains($plannedIcs, "TRANSP:TRANSPARENT\r\n")
    || str_contains($plannedIcs, 'Notiz')) {
    throw new RuntimeException('Geplanter Urlaub ist nicht datensparsam und nicht-blockierend serialisiert.');
}
$approved = new AbsenceInterval(
    'sync-person',
    $planned->start(),
    $planned->end(),
    AbsenceInterval::STATUS_APPROVED,
);
if ($serializer->absenceObjectUri($approved) !== $plannedUri
    || !str_contains($serializer->serializeAbsence($approved, '20260718T090000Z'), "STATUS:CONFIRMED\r\n")
    || !str_contains($serializer->serializeAbsence($approved, '20260718T090000Z'), "TRANSP:OPAQUE\r\n")) {
    throw new RuntimeException('Genehmigung aktualisiert nicht dasselbe Urlaubsobjekt auf blockierend.');
}

$untitled = CalendarEntry::get(array_replace($shift->toArray(), ['title' => '']));
if (!str_contains($serializer->serialize($untitled, '20260718T090000Z'), "SUMMARY:Übersetzter Dienst\r\n")
    || !str_contains($serializer->serialize($untitled, '20260718T090000Z'), "DESCRIPTION:Übersetzte Kalenderbeschreibung.\r\n")) {
    throw new RuntimeException('Titelloser Dienst hat keinen verständlichen Kalendernamen.');
}

$long = CalendarEntry::get(array_replace($shift->toArray(), ['title' => str_repeat('Ä', 60)]));
foreach (explode("\r\n", $serializer->serialize($long, '20260718T090000Z')) as $line) {
    if (strlen($line) > 75) throw new RuntimeException('ICS-Zeile überschreitet 75 Oktette.');
}

try {
    $serializer->serialize(CalendarEntry::get(array_replace($shift->toArray(), ['id' => null])), '20260718T090000Z');
    throw new RuntimeException('Dienst ohne persistente ID wurde serialisiert.');
} catch (InvalidArgumentException) {
}

echo "ShiftCalendarEventSerializerTest: OK\n";
}
