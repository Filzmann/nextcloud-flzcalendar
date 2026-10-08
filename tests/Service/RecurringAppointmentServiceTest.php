<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Repository {
    use OCA\FlzCalendar\Model\CalendarEntry;

    final class CalendarEntryRepository {
        public array $saved = [];
        public array $series = [];
        public array $deletedSeries = [];
        public function saveMany(array $entries, string $actorUid): array {
            $this->saved = $entries;
            return array_map(static fn(int $index): int => 100 + $index, array_keys($entries));
        }
        public function findSeries(string $seriesUid): array { return $this->series; }
        public function deleteSeries(string $seriesUid): void { $this->deletedSeries[] = $seriesUid; }
        public function containingShifts(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end, ?int $excludeId = null): array { return []; }
    }
}

namespace OCA\FlzCalendar\Service {
    use OCA\FlzCalendar\Model\CalendarEntry;

    final class ContainingShiftAssignment { public function assign(CalendarEntry $entry, array $parents): CalendarEntry { return $entry; } }
}

namespace {

    use OCA\FlzCalendar\Model\CalendarEntry;
    use OCA\FlzCalendar\Repository\CalendarEntryRepository;
    use OCA\FlzCalendar\Service\ContainingShiftAssignment;
    use OCA\FlzCalendar\Service\RecurringAppointmentService;

    $repository = new CalendarEntryRepository();
    $service = new RecurringAppointmentService($repository, new ContainingShiftAssignment());
    $payload = [
        'employeeUid' => 'person-a',
        'start' => '2026-03-23T08:00:00Z',
        'end' => '2026-03-23T09:00:00Z',
        'type' => CalendarEntry::TYPE_APPOINTMENT,
        'title' => 'Neutraler Serientermin',
    ];
    $ids = $service->create($payload, [
        'frequency' => 'weekly', 'interval' => 1, 'until' => '2026-04-06', 'weekdays' => [1], 'timezone' => 'Europe/Berlin',
    ], 'planner');
    if ($ids !== [100, 101, 102] || count($repository->saved) !== 3) {
        throw new RuntimeException('Serienvorkommen werden nicht vollständig und atomar gespeichert.');
    }
    $seriesUid = $repository->saved[0]->seriesUid();
    if ($seriesUid === null || array_filter($repository->saved, static fn(CalendarEntry $entry): bool => $entry->seriesUid() !== $seriesUid) !== []) {
        throw new RuntimeException('Serienkennung ist nicht stabil.');
    }
    if (array_map(static fn(CalendarEntry $entry): string => $entry->start()->setTimezone(new DateTimeZone('Europe/Berlin'))->format('Y-m-d H:i'), $repository->saved) !== [
        '2026-03-23 09:00', '2026-03-30 09:00', '2026-04-06 09:00',
    ]) {
        throw new RuntimeException('Materialisierte Serie hält die lokale Uhrzeit nicht stabil.');
    }

    $repository->series = array_map(static fn(CalendarEntry $entry, int $index): CalendarEntry => CalendarEntry::get(array_replace($entry->toArray(), ['id' => 100 + $index])), $repository->saved, array_keys($repository->saved));
    $anchor = $repository->series[1];
    $updated = $service->updateSeries($anchor, array_replace($payload, [
        'start' => '2026-03-30T08:30:00Z',
        'end' => '2026-03-30T10:00:00Z',
        'title' => 'Geänderte Serie',
    ]), 'planner');
    if ($updated !== [100, 101, 102] || array_map(static fn(CalendarEntry $entry): string => $entry->start()->setTimezone(new DateTimeZone('Europe/Berlin'))->format('H:i'), $repository->saved) !== ['10:30', '10:30', '10:30']) {
        throw new RuntimeException('Gesamtänderung aktualisiert die lokale Uhrzeit nicht für alle Vorkommen.');
    }
    if (array_filter($repository->saved, static fn(CalendarEntry $entry): bool => $entry->durationMinutes() !== 90 || $entry->title() !== 'Geänderte Serie') !== []) {
        throw new RuntimeException('Gesamtänderung übernimmt Dauer oder Titel nicht konsistent.');
    }

    $service->deleteSeries($seriesUid);
    if ($repository->deletedSeries !== [$seriesUid]) throw new RuntimeException('Gesamte Serie wurde nicht gelöscht.');

    try {
        $service->create(array_replace($payload, ['type' => CalendarEntry::TYPE_SHIFT, 'title' => '']), [
            'frequency' => 'daily', 'interval' => 1, 'until' => '2026-03-24', 'timezone' => 'Europe/Berlin',
        ], 'planner');
        throw new RuntimeException('Wiederholende Dienste wurden akzeptiert.');
    } catch (InvalidArgumentException) {
    }

    echo "RecurringAppointmentServiceTest: OK\n";
}
