<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Repository {
    final class CalendarEntryRepository {
        public array $ranges = [];
        public function findRange(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            $this->ranges[] = [$start->format('Y-m-d'), $end->format('Y-m-d'), $uids];
            return [];
        }
    }
}

namespace OCA\FlzCalendar\Service {
    final class DefaultShiftMaterializer {
        public array $weeks = [];
        public function syncWeek(\DateTimeImmutable $start, array $uids, array $absences = [], ?array $planningConflicts = null, string $planningConflictStatus = 'available'): void {
            $this->weeks[] = [$start->format('Y-m-d'), $uids, $absences, $planningConflicts, $planningConflictStatus];
        }
    }
    final class AbsenceService {
        public array $queries = [];
        public function query(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            $this->queries[] = [$start->format('Y-m-d'), $end->format('Y-m-d'), $uids];
            return [];
        }
    }
    final class PlanningConflictService {
        public array $queries = [];
        public function queryRange(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            $this->queries[] = [$start->format('Y-m-d'), $end->format('Y-m-d'), $uids];
            return ['status' => 'available', 'conflicts' => [[
                'employeeUid' => $uids[0], 'start' => '2026-07-10T08:00:00Z', 'end' => '2026-07-10T16:00:00Z',
                'type' => 'shift', 'label' => 'Assistenz', 'sourceAppId' => 'flzplaner',
            ]]];
        }
    }
    final class ContainingShiftAssignment {}
    final class ShiftCalendarSyncService {}
}

namespace {

    use OCA\FlzCalendar\Repository\CalendarEntryRepository;
    use OCA\FlzCalendar\Service\AbsenceService;
    use OCA\FlzCalendar\Service\CalendarService;
    use OCA\FlzCalendar\Service\ContainingShiftAssignment;
    use OCA\FlzCalendar\Service\DefaultShiftMaterializer;
    use OCA\FlzCalendar\Service\PlanningConflictService;
    use OCA\FlzCalendar\Service\ShiftCalendarSyncService;

    $repository = new CalendarEntryRepository();
    $materializer = new DefaultShiftMaterializer();
    $absences = new AbsenceService();
    $planning = new PlanningConflictService();
    $service = new CalendarService($repository, $materializer, $absences, new ContainingShiftAssignment(), new ShiftCalendarSyncService(), $planning);
    $employees = [['uid' => 'person-a']];

    $result = $service->range(new DateTimeImmutable('2026-06-29'), new DateTimeImmutable('2026-08-03'), $employees);
    if ($result['start'] !== '2026-06-29' || $result['end'] !== '2026-08-03' || $result['employees'] !== $employees) {
        throw new RuntimeException('Monatsbereich liefert keinen stabilen Zeitraumvertrag.');
    }
    if (array_column($materializer->weeks, 0) !== ['2026-06-29', '2026-07-06', '2026-07-13', '2026-07-20', '2026-07-27']) {
        throw new RuntimeException('Standarddienste werden nicht für jede sichtbare Monatswoche materialisiert.');
    }
    if (($materializer->weeks[0][3][0]['sourceAppId'] ?? null) !== 'flzplaner' || ($materializer->weeks[0][4] ?? null) !== 'available') {
        throw new RuntimeException('Die einmalige Bereichsabfrage wird nicht an die Standarddienstmaterialisierung weitergereicht.');
    }
    if ($repository->ranges !== [['2026-06-29', '2026-08-03', ['person-a']]] || $absences->queries !== [['2026-06-29', '2026-08-03', ['person-a']]]) {
        throw new RuntimeException('Monatsdaten werden nicht in einem einzigen begrenzten Bereich gelesen.');
    }
    if (($result['planningConflictStatus'] ?? null) !== 'available'
        || ($result['planningConflicts'][0]['label'] ?? null) !== 'Assistenz'
        || $planning->queries !== [['2026-06-29', '2026-08-03', ['person-a']]]) {
        throw new RuntimeException('Der Kalenderbereich liefert Assistenzsperren nicht in einem begrenzten Consumeraufruf.');
    }

    try {
        $service->range(new DateTimeImmutable('2026-06-29'), new DateTimeImmutable('2026-08-17'), $employees);
        throw new RuntimeException('Ein zu großer Kalenderbereich wurde akzeptiert.');
    } catch (InvalidArgumentException $error) {
        if (!str_contains($error->getMessage(), '42')) throw $error;
    }

    echo "CalendarServiceRangeTest: OK\n";
}
