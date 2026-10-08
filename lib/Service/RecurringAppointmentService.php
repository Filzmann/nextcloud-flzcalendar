<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\FlzCalendar\Model\RecurrenceRule;
use OCA\FlzCalendar\Repository\CalendarEntryRepository;

/**
 * Zweck: Erzeugt, ändert und löscht begrenzte Terminserien atomar.
 * Zusammenspiel: ApiController -> RecurringAppointmentService -> CalendarEntryRepository.
 */
final class RecurringAppointmentService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private ContainingShiftAssignment $shiftAssignment,
    ) {}

    /** @return list<int> */
    public function create(array $payload, array $recurrence, string $actorUid): array {
        $prototype = CalendarEntry::get($payload);
        if ($prototype->type() !== CalendarEntry::TYPE_APPOINTMENT || $prototype->meetingUid() !== null) {
            throw new InvalidArgumentException('Wiederholungen sind nur für einzelne Termine verfügbar.');
        }
        $rule = RecurrenceRule::get($recurrence, $prototype->start());
        $seriesUid = bin2hex(random_bytes(16));
        $duration = $prototype->end()->getTimestamp() - $prototype->start()->getTimestamp();
        $occurrences = array_map(function(DateTimeImmutable $start) use ($prototype, $seriesUid, $rule, $duration): CalendarEntry {
            return $this->prepare(CalendarEntry::get(array_replace($prototype->toArray(), [
                'id' => null,
                'start' => $start,
                'end' => $start->modify('+' . $duration . ' seconds'),
                'parentEntryId' => null,
                'seriesUid' => $seriesUid,
                'seriesTimezone' => $rule->timezoneName(),
            ])));
        }, $rule->starts($prototype->start()));

        return $this->entries->saveMany($occurrences, $actorUid);
    }

    /** @return list<int> */
    public function updateSeries(CalendarEntry $anchor, array $payload, string $actorUid): array {
        $seriesUid = $anchor->seriesUid();
        if ($seriesUid === null || $anchor->seriesTimezone() === null) {
            throw new InvalidArgumentException('Der Termin gehört zu keiner Serie.');
        }
        $prototype = CalendarEntry::get($payload);
        if ($prototype->type() !== CalendarEntry::TYPE_APPOINTMENT || $prototype->meetingUid() !== null) {
            throw new InvalidArgumentException('Eine Terminserie kann nicht in einen anderen Eintragstyp geändert werden.');
        }
        $series = $this->seriesEntries($seriesUid);
        if ($series === []) throw new InvalidArgumentException('Terminserie nicht gefunden.');

        $timezone = new \DateTimeZone($anchor->seriesTimezone());
        $oldLocal = $anchor->start()->setTimezone($timezone);
        $newLocal = $prototype->start()->setTimezone($timezone);
        $oldDay = new DateTimeImmutable($oldLocal->format('Y-m-d') . ' 00:00:00', $timezone);
        $newDay = new DateTimeImmutable($newLocal->format('Y-m-d') . ' 00:00:00', $timezone);
        $dayOffset = (int)$oldDay->diff($newDay)->format('%r%a');
        $duration = $prototype->end()->getTimestamp() - $prototype->start()->getTimestamp();

        $updated = array_map(function(CalendarEntry $entry) use ($prototype, $timezone, $newLocal, $dayOffset, $duration): CalendarEntry {
            $localDay = $entry->start()->setTimezone($timezone)->setTime(0, 0)->modify($dayOffset . ' days');
            $start = $localDay->setTime((int)$newLocal->format('H'), (int)$newLocal->format('i'), (int)$newLocal->format('s'));
            return $this->prepare(CalendarEntry::get(array_replace($entry->toArray(), [
                'employeeUid' => $prototype->employeeUid(),
                'start' => $start,
                'end' => $start->modify('+' . $duration . ' seconds'),
                'type' => CalendarEntry::TYPE_APPOINTMENT,
                'title' => $prototype->title(),
                'parentEntryId' => null,
            ])));
        }, $series);

        return $this->entries->saveMany($updated, $actorUid);
    }

    /** @return list<CalendarEntry> */
    public function seriesEntries(string $seriesUid): array {
        return $this->entries->findSeries($seriesUid);
    }

    public function deleteSeries(string $seriesUid): void {
        if (preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $seriesUid) !== 1) {
            throw new InvalidArgumentException('Ungültige Serienkennung.');
        }
        $this->entries->deleteSeries($seriesUid);
    }

    private function prepare(CalendarEntry $entry): CalendarEntry {
        $parents = $this->entries->containingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id());
        return $this->shiftAssignment->assign($entry, $parents);
    }
}
