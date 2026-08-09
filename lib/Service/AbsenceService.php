<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use OCA\LocalBase\Calendar\AbsenceEmployeeDiscoveryEvent;
use OCA\LocalBase\Calendar\AbsenceInterval;
use OCA\LocalBase\Calendar\AbsenceQueryEvent;
use OCP\EventDispatcher\IEventDispatcher;

/** Zweck: Kapselt die optionale read-only Abwesenheitsabfrage hinter dem neutralen LocalBase-Event. */
final class AbsenceService {
    public function __construct(private IEventDispatcher $events) {}

    /** @return list<string> */
    public function discover(DateTimeImmutable $start, DateTimeImmutable $end): array {
        $event = new AbsenceEmployeeDiscoveryEvent($start, $end);
        $this->events->dispatchTyped($event);

        return $event->employeeUids();
    }

    /** @return list<AbsenceInterval> */
    public function query(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids): array {
        $event = new AbsenceQueryEvent($start, $end, $employeeUids);
        $this->events->dispatchTyped($event);

        return $event->absences();
    }

    public function assertShiftWritable(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): void {
        foreach ($this->query($start, $end, [$employeeUid]) as $absence) {
            if ($absence->overlaps($start, $end)) {
                throw new \InvalidArgumentException('Urlaub blockiert Dienste in diesem Zeitraum.');
            }
        }
    }
}
