<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\AdCalendar\Model\CalendarEntry;
use OCP\IL10N;

/** Zweck: Serialisiert genau einen persistierten AD-Dienst als privaten, stabil identifizierbaren VEVENT. */
final class ShiftCalendarEventSerializer {
    public function __construct(private IL10N $l10n) {}

    public function objectUri(CalendarEntry $shift): string {
        $this->assertPublishable($shift);
        return 'adcalendar-shift-' . $shift->id() . '.ics';
    }

    public function serialize(CalendarEntry $shift, ?string $dateStamp = null): string {
        $this->assertPublishable($shift);
        $dateStamp ??= (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
        if (preg_match('/^\d{8}T\d{6}Z$/', $dateStamp) !== 1) throw new InvalidArgumentException('Der ICS-Zeitstempel ist ungültig.');

        $title = $shift->title() !== '' ? $shift->title() : $this->l10n->t('Shift');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AD Suite//AD Kalender//DE',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:adcalendar-shift-' . $shift->id() . '@local',
            'DTSTAMP:' . $dateStamp,
            'DTSTART:' . $this->utc($shift->start()),
            'DTEND:' . $this->utc($shift->end()),
            'SUMMARY:' . $this->text($title),
            'DESCRIPTION:' . $this->text($this->l10n->t('Automatically synchronised from AD Calendar. Please make changes there.')),
            'CLASS:PRIVATE',
            'TRANSP:OPAQUE',
            'X-AD-CALENDAR-SOURCE:adcalendar',
            'X-AD-CALENDAR-ENTRY-ID:' . $shift->id(),
            'END:VEVENT',
            'END:VCALENDAR',
        ];
        return implode("\r\n", array_map($this->fold(...), $lines)) . "\r\n";
    }

    private function assertPublishable(CalendarEntry $shift): void {
        if ($shift->type() !== CalendarEntry::TYPE_SHIFT || $shift->id() === null) {
            throw new InvalidArgumentException('Nur persistierte Dienste können veröffentlicht werden.');
        }
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
