<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Repository {
    final class CalendarEntryRepository {
        public array $ranges = [];
        public function findRange(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            $this->ranges[] = [$start->format('Y-m-d'), $end->format('Y-m-d'), $uids];
            return [];
        }
    }
}

namespace OCA\AdCalendar\Service {
    final class DefaultShiftMaterializer {
        public array $weeks = [];
        public function syncWeek(\DateTimeImmutable $start, array $uids, array $absences = []): void {
            $this->weeks[] = [$start->format('Y-m-d'), $uids, $absences];
        }
    }
    final class AbsenceService {
        public array $queries = [];
        public function query(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            $this->queries[] = [$start->format('Y-m-d'), $end->format('Y-m-d'), $uids];
            return [];
        }
    }
    final class ContainingShiftAssignment {}
    final class ShiftCalendarSyncService {}
}

namespace {

    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\AbsenceService;
    use OCA\AdCalendar\Service\CalendarService;
    use OCA\AdCalendar\Service\ContainingShiftAssignment;
    use OCA\AdCalendar\Service\DefaultShiftMaterializer;
    use OCA\AdCalendar\Service\ShiftCalendarSyncService;

    $repository = new CalendarEntryRepository();
    $materializer = new DefaultShiftMaterializer();
    $absences = new AbsenceService();
    $service = new CalendarService($repository, $materializer, $absences, new ContainingShiftAssignment(), new ShiftCalendarSyncService());
    $employees = [['uid' => 'person-a']];

    $result = $service->range(new DateTimeImmutable('2026-06-29'), new DateTimeImmutable('2026-08-03'), $employees);
    if ($result['start'] !== '2026-06-29' || $result['end'] !== '2026-08-03' || $result['employees'] !== $employees) {
        throw new RuntimeException('Monatsbereich liefert keinen stabilen Zeitraumvertrag.');
    }
    if (array_column($materializer->weeks, 0) !== ['2026-06-29', '2026-07-06', '2026-07-13', '2026-07-20', '2026-07-27']) {
        throw new RuntimeException('Standarddienste werden nicht für jede sichtbare Monatswoche materialisiert.');
    }
    if ($repository->ranges !== [['2026-06-29', '2026-08-03', ['person-a']]] || $absences->queries !== [['2026-06-29', '2026-08-03', ['person-a']]]) {
        throw new RuntimeException('Monatsdaten werden nicht in einem einzigen begrenzten Bereich gelesen.');
    }

    try {
        $service->range(new DateTimeImmutable('2026-06-29'), new DateTimeImmutable('2026-08-17'), $employees);
        throw new RuntimeException('Ein zu großer Kalenderbereich wurde akzeptiert.');
    } catch (InvalidArgumentException $error) {
        if (!str_contains($error->getMessage(), '42')) throw $error;
    }

    echo "CalendarServiceRangeTest: OK\n";
}
