<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\LocalBase\Calendar\ScheduleConflict;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Kapselt die optionale read-only Abfrage von Assistenzschichten hinter dem LocalBase-Konfliktvertrag.
 * Vertrag: Fehlende Listener ergeben eine leere, gültige Antwort; Providerfehler werden lesend sichtbar und schreibend fail-closed behandelt.
 */
final class PlanningConflictService {
    private const REQUESTER_APP_ID = 'adcalendar';
    private const PROVIDER_APP_ID = 'adplaner';

    public function __construct(
        private IEventDispatcher $events,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param list<string> $employeeUids
     * @return array{status: 'available'|'unavailable', conflicts: list<array<string, mixed>>}
     */
    public function queryRange(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids): array {
        $conflicts = [];
        try {
            foreach (array_values(array_unique($employeeUids)) as $employeeUid) {
                foreach ($this->query($employeeUid, $start, $end) as $conflict) {
                    $conflicts[] = [
                        'employeeUid' => $employeeUid,
                        'start' => $conflict->start()->format(DATE_ATOM),
                        'end' => $conflict->end()->format(DATE_ATOM),
                        'type' => 'shift',
                        'label' => 'Assistenz',
                        'sourceAppId' => self::PROVIDER_APP_ID,
                    ];
                }
            }
        } catch (\Throwable $error) {
            $this->logFailure('Kalenderansicht konnte Assistenzkonflikte nicht vollständig prüfen.', $error, 'range');
            return ['status' => 'unavailable', 'conflicts' => []];
        }

        return ['status' => 'available', 'conflicts' => $conflicts];
    }

    public function assertShiftWritable(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): void {
        try {
            $conflicts = $this->query($employeeUid, $start, $end);
        } catch (\Throwable $error) {
            $this->logFailure('Dienst konnte nicht gegen Assistenzkonflikte geprüft werden.', $error, 'mutation');
            throw new InvalidArgumentException('Assistenzkonflikte konnten nicht geprüft werden. Der Dienst wurde nicht gespeichert.');
        }
        if ($conflicts !== []) {
            throw new InvalidArgumentException('Assistenz blockiert Dienste in diesem Zeitraum.');
        }
    }

    /** @return array{status: 'available'|'unavailable', blocked: bool} */
    public function checkShift(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): array {
        try {
            return ['status' => 'available', 'blocked' => $this->query($employeeUid, $start, $end) !== []];
        } catch (\Throwable $error) {
            $this->logFailure('Standarddienst konnte nicht gegen Assistenzkonflikte geprüft werden.', $error, 'materialization');
            return ['status' => 'unavailable', 'blocked' => false];
        }
    }

    /** @return list<ScheduleConflict> */
    private function query(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): array {
        $event = new ScheduleConflictQueryEvent($employeeUid, $start, $end, self::REQUESTER_APP_ID);
        $this->events->dispatchTyped($event);

        return array_values(array_filter(
            $event->conflicts(),
            static fn(ScheduleConflict $conflict): bool => $conflict->type() === 'shift'
                && $conflict->sourceAppId() === self::PROVIDER_APP_ID
                && $conflict->start() < $end
                && $conflict->end() > $start,
        ));
    }

    private function logFailure(string $message, \Throwable $error, string $phase): void {
        $this->logger->error($message, ['phase' => $phase, 'exceptionClass' => $error::class]);
    }
}
