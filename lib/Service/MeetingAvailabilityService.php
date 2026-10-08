<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use DateTimeImmutable;
use OCA\FlzCalendar\Model\CalendarEntry;

/**
 * Zweck: Findet gemeinsame freie Zeit innerhalb der Dienste mehrerer Personen.
 * Zusammenspiel: MeetingService liefert Kalendereinträge und Abwesenheiten; dieser Service berechnet reine Zeitintervalle.
 * Vertrag: Termine werden als belegt abgezogen; Ergebnisse sind maximale gemeinsame Lücken von mindestens der Wunschdauer.
 */
final class MeetingAvailabilityService {
    /** @param list<CalendarEntry> $entries @param list<string> $employeeUids */
    public function find(
        array $entries,
        array $employeeUids,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
        int $durationMinutes,
        array $absences = [],
    ): array {
        $availability = [];
        foreach ($employeeUids as $uid) {
            $shifts = [];
            $appointments = [];
            foreach ($entries as $entry) {
                if ($entry->employeeUid() !== $uid) continue;

                $interval = [
                    max($entry->start()->getTimestamp(), $rangeStart->getTimestamp()),
                    min($entry->end()->getTimestamp(), $rangeEnd->getTimestamp()),
                ];
                if ($interval[0] >= $interval[1]) continue;

                if ($entry->type() === CalendarEntry::TYPE_SHIFT) {
                    $shifts[] = $interval;
                } else {
                    $appointments[] = $interval;
                }
            }
            foreach ($absences as $absence) {
                if ($absence->employeeUid() === $uid && $absence->approved()) {
                    $appointments[] = [
                        max($absence->start()->getTimestamp(), $rangeStart->getTimestamp()),
                        min($absence->end()->getTimestamp(), $rangeEnd->getTimestamp()),
                    ];
                }
            }

            $free = $this->merge($shifts);
            foreach ($appointments as $busy) {
                $free = $this->subtract($free, $busy);
            }
            $availability[] = $free;
        }

        $common = array_shift($availability) ?? [];
        foreach ($availability as $free) {
            $common = $this->intersect($common, $free);
        }
        $minimum = $durationMinutes * 60;

        return array_values(array_map(
            static fn(array $gap): array => [
                'start' => (new DateTimeImmutable('@' . $gap[0]))->format(DATE_ATOM),
                'end' => (new DateTimeImmutable('@' . $gap[1]))->format(DATE_ATOM),
                'durationMinutes' => intdiv($gap[1] - $gap[0], 60),
            ],
            array_filter($common, static fn(array $gap): bool => $gap[1] - $gap[0] >= $minimum),
        ));
    }

    private function merge(array $intervals): array {
        usort($intervals, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $result = [];
        foreach ($intervals as $interval) {
            $last = array_key_last($result);
            if ($last === null || $interval[0] > $result[$last][1]) {
                $result[] = $interval;
            } else {
                $result[$last][1] = max($result[$last][1], $interval[1]);
            }
        }

        return $result;
    }

    private function subtract(array $free, array $busy): array {
        $result = [];
        foreach ($free as $interval) {
            if ($busy[1] <= $interval[0] || $busy[0] >= $interval[1]) {
                $result[] = $interval;
                continue;
            }
            if ($busy[0] > $interval[0]) $result[] = [$interval[0], min($busy[0], $interval[1])];
            if ($busy[1] < $interval[1]) $result[] = [max($busy[1], $interval[0]), $interval[1]];
        }

        return $result;
    }

    private function intersect(array $left, array $right): array {
        $result = [];
        foreach ($left as $a) {
            foreach ($right as $b) {
                $start = max($a[0], $b[0]);
                $end = min($a[1], $b[1]);
                if ($start < $end) $result[] = [$start, $end];
            }
        }

        return $this->merge($result);
    }
}
