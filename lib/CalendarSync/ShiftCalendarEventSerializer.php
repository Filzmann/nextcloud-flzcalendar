<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\LocalBase\Calendar\AbsenceInterval;
use OCP\IL10N;

/** Zweck: Serialisiert eigene persistierte AD-Einträge und datensparsame Urlaube als private VEVENTs. */
final class ShiftCalendarEventSerializer {
    public function __construct(private IL10N $l10n) {}

    public function objectUri(CalendarEntry $entry): string {
        $this->assertPublishable($entry);
        return 'adcalendar-' . $entry->type() . '-' . $entry->id() . '.ics';
    }

    public function serialize(CalendarEntry $entry, ?string $dateStamp = null): string {
        $this->assertPublishable($entry);
        $dateStamp ??= (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
        $this->assertDateStamp($dateStamp);

        $title = $entry->title() !== '' ? $entry->title() : $this->l10n->t('Shift');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AD Suite//AD Kalender//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:adcalendar-' . $entry->type() . '-' . $entry->id() . '@local',
            'DTSTAMP:' . $dateStamp,
            'DTSTART:' . $this->utc($entry->start()),
            'DTEND:' . $this->utc($entry->end()),
            'SUMMARY:' . $this->text($title),
            'DESCRIPTION:' . $this->text($this->l10n->t('Automatically synchronised from AD Calendar. Please make changes there.')),
            'CLASS:PRIVATE',
            'TRANSP:OPAQUE',
            'X-AD-CALENDAR-SOURCE:adcalendar',
            'X-AD-CALENDAR-ENTRY-ID:' . $entry->id(),
            'X-AD-CALENDAR-ENTRY-TYPE:' . $entry->type(),
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        return implode("\r\n", array_map($this->fold(...), $lines)) . "\r\n";
    }

    public function absenceObjectUri(AbsenceInterval $absence): string {
        return 'adcalendar-absence-' . $this->absenceId($absence) . '.ics';
    }

    public function serializeAbsence(AbsenceInterval $absence, ?string $dateStamp = null): string {
        $dateStamp ??= (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
        $this->assertDateStamp($dateStamp);
        $absenceId = $this->absenceId($absence);
        $planned = $absence->status() === AbsenceInterval::STATUS_PLANNED;
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AD Suite//AD Kalender//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:adcalendar-absence-' . $absenceId . '@local',
            'DTSTAMP:' . $dateStamp,
            'DTSTART;VALUE=DATE:' . $absence->start()->format('Ymd'),
            'DTEND;VALUE=DATE:' . $absence->end()->format('Ymd'),
            'SUMMARY:' . $this->text($planned ? $this->l10n->t('Planned vacation') : $this->l10n->t('Vacation')),
            'DESCRIPTION:' . $this->text($this->l10n->t('Automatically synchronised from AD Calendar. Please make changes there.')),
            'CLASS:PRIVATE',
            'STATUS:' . ($planned ? 'TENTATIVE' : 'CONFIRMED'),
            'TRANSP:' . ($planned ? 'TRANSPARENT' : 'OPAQUE'),
            'X-AD-CALENDAR-SOURCE:adcalendar',
            'X-AD-CALENDAR-ABSENCE-ID:' . $absenceId,
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        return implode("\r\n", array_map($this->fold(...), $lines)) . "\r\n";
    }

    private function assertPublishable(CalendarEntry $entry): void {
        if (!in_array($entry->type(), [CalendarEntry::TYPE_SHIFT, CalendarEntry::TYPE_APPOINTMENT], true) || $entry->id() === null) {
            throw new InvalidArgumentException('Nur persistierte Dienste und Termine können veröffentlicht werden.');
        }
    }

    private function assertDateStamp(string $dateStamp): void {
        if (preg_match('/^\d{8}T\d{6}Z$/', $dateStamp) !== 1) throw new InvalidArgumentException('Der ICS-Zeitstempel ist ungültig.');
    }

    private function absenceId(AbsenceInterval $absence): string {
        return hash('sha256', implode("\0", [
            $absence->employeeUid(),
            $absence->start()->format('Y-m-d'),
            $absence->end()->format('Y-m-d'),
        ]));
    }

    private function utc(DateTimeImmutable $value): string {
        return $value->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    private function text(string $value): string {
        return str_replace(
            ["\\", "\r\n", "\r", "\n", ';', ','],
            ["\\\\", '\\n', '\\n', '\\n', '\\;', '\\,'],
            $value,
        );
    }

    /** RFC 5545: Inhaltszeilen umfassen höchstens 75 Oktette; Fortsetzungen beginnen mit einem Leerzeichen. */
    private function fold(string $line): string {
        if (strlen($line) <= 75) return $line;
        $characters = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) throw new InvalidArgumentException('Der ICS-Inhalt enthält ungültiges UTF-8.');
        $result = [];
        $current = '';
        foreach ($characters as $character) {
            if (strlen($current) + strlen($character) > 75) {
                $result[] = $current;
                $current = ' ' . $character;
                continue;
            }
            $current .= $character;
        }
        if ($current !== '') $result[] = $current;
        return implode("\r\n", $result);
    }
}
