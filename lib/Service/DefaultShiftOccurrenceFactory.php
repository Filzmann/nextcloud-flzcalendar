<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\FlzCalendar\Model\CalendarEntry;

/** Zweck: Baut aus einer gespeicherten Wochentagsregel genau einen lokalen Standarddienst. */
final class DefaultShiftOccurrenceFactory {
    public function create(string $employeeUid, string $date, array $rule, DateTimeZone $timezone, ?int $id = null): CalendarEntry {
        $start = new DateTimeImmutable($date . ' ' . $rule['start'], $timezone);
        $end = new DateTimeImmutable($date . ' ' . $rule['end'], $timezone);
        if ($end <= $start) $end = $end->modify('+1 day');
        $utc = new DateTimeZone('UTC');
        return CalendarEntry::get([
            'id' => $id,
            'employeeUid' => $employeeUid,
            'start' => $start->setTimezone($utc),
            'end' => $end->setTimezone($utc),
            'type' => CalendarEntry::TYPE_SHIFT,
            'title' => '',
            'defaultDate' => $date,
            'defaultModified' => false,
            'defaultDeleted' => false,
        ]);
    }
}
