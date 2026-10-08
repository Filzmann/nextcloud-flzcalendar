<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\CalendarSync;

use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\LocalBase\Calendar\AbsenceInterval;

/** Vertrag für den privaten Nextcloud-Kalender mit eigenen Einträgen und read-only Urlauben. */
interface PersonalCalendarPublisher {
    /**
     * @param list<CalendarEntry> $entries
     * @param list<AbsenceInterval> $absences
     */
    public function replaceAllContent(string $employeeUid, array $entries, array $absences): void;
    public function publish(CalendarEntry $entry): void;
    public function removeEntry(CalendarEntry $entry): void;
    public function removeCalendar(string $employeeUid): void;
}
