<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCP\Config\IUserConfig;
use OCP\IConfig;

/**
 * Zweck: Synchronisiert gespeicherte Standard-Dienstzeiten idempotent in reale Wochen-Dienste.
 * Zusammenspiel: CalendarService ruft vor Wochenansicht und Meetinglückensuche Materializer -> PreferenceService/Repository auf.
 * Vertrag: Manuell geänderte oder gelöschte Einzelvorkommen werden niemals aus der Serie überschrieben oder neu erzeugt.
 */
final class DefaultShiftMaterializer {
    public function __construct(
        private CalendarEntryRepository $entries,
        private CalendarPreferenceService $preferences,
        private DefaultShiftOccurrenceFactory $factory,
        private IConfig $config,
        private IUserConfig $userConfig,
        private ShiftCalendarSyncService $shiftSync,
        private PlanningConflictService $planningConflicts,
    ) {}

    /** @param list<string> $employeeUids */
    public function syncWeek(
        DateTimeImmutable $weekStart,
        array $employeeUids,
        array $absences = [],
        ?array $planningConflicts = null,
        string $planningConflictStatus = 'available',
    ): void {
        foreach (array_values(array_unique($employeeUids)) as $employeeUid) {
            $defaults = $this->preferences->storedShiftDefaults($employeeUid);
            if ($defaults === null) continue;
            $timezone = $this->timezone($employeeUid);
            for ($offset = 0; $offset < 7; $offset++) {
                $date = $weekStart->modify("+{$offset} days")->format('Y-m-d');
                $weekday = (string)(new DateTimeImmutable($date, $timezone))->format('N');
                $this->syncOccurrence($employeeUid, $date, $defaults[$weekday], $timezone, $absences, $planningConflicts, $planningConflictStatus);
            }
        }
    }

    private function syncOccurrence(
        string $employeeUid,
        string $date,
        array $rule,
        DateTimeZone $timezone,
        array $absences,
        ?array $planningConflicts,
        string $planningConflictStatus,
    ): void {
        $existing = $this->entries->findDefaultOccurrence($employeeUid, $date);
        if ($existing?->defaultDeleted() || $existing?->defaultModified()) return;
        if (!$rule['enabled']) {
            if ($existing !== null) {
                $this->entries->removeGeneratedDefault((int)$existing->id());
                $this->shiftSync->remove($existing);
            }
            return;
        }

        $occurrence = $this->factory->create($employeeUid, $date, $rule, $timezone, $existing?->id());
        $planningCheck = $planningConflicts === null
            ? $this->planningConflicts->checkShift($employeeUid, $occurrence->start(), $occurrence->end())
            : [
                'status' => $planningConflictStatus,
                'blocked' => $this->hasPlanningConflict($employeeUid, $occurrence->start(), $occurrence->end(), $planningConflicts),
            ];
        if ($planningCheck['status'] !== 'available') return;
        if ($planningCheck['blocked']) {
            if ($existing !== null) {
                $this->entries->removeGeneratedDefault((int)$existing->id());
                $this->shiftSync->remove($existing);
            }
            return;
        }
        foreach ($absences as $absence) {
            $blocksOccurrence = $absence->employeeUid() === $employeeUid
                && $absence->overlaps($occurrence->start(), $occurrence->end());
            if (!$blocksOccurrence) continue;

            if ($existing !== null) {
                $this->entries->removeGeneratedDefault((int)$existing->id());
                $this->shiftSync->remove($existing);
            }
            return;
        }
        if ($this->entries->overlappingShifts($employeeUid, $occurrence->start(), $occurrence->end(), $existing?->id()) !== []) return;
        if ($existing !== null && $existing->start() == $occurrence->start() && $existing->end() == $occurrence->end()) return;

        $id = $this->entries->save($occurrence, $employeeUid);
        $this->entries->attachContainedAppointments($id, $employeeUid, $occurrence->start(), $occurrence->end());
        $this->shiftSync->publish(CalendarEntry::get(array_replace($occurrence->toArray(), ['id' => $id])));
    }

    private function hasPlanningConflict(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end, array $conflicts): bool {
        foreach ($conflicts as $conflict) {
            if (($conflict['employeeUid'] ?? null) !== $employeeUid) continue;
            try {
                $conflictStart = new DateTimeImmutable((string)($conflict['start'] ?? ''));
                $conflictEnd = new DateTimeImmutable((string)($conflict['end'] ?? ''));
            } catch (\Throwable) {
                continue;
            }
            if ($conflictStart < $end && $conflictEnd > $start) return true;
        }
        return false;
    }

    private function timezone(string $employeeUid): DateTimeZone {
        $name = $this->userConfig->getValueString($employeeUid, 'core', 'timezone');
        if ($name === '') $name = $this->config->getSystemValueString('default_timezone', 'UTC');

        try {
            return new DateTimeZone($name);
        } catch (\Throwable) {
            return new DateTimeZone('UTC');
        }
    }
}
