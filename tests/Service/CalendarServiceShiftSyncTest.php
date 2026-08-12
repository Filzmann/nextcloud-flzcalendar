<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Repository {
    use OCA\AdCalendar\Model\CalendarEntry;

    final class CalendarEntryRepository {
        public ?CalendarEntry $found = null;
        public array $saved = [];
        public array $deleted = [];
        public int $nextId = 41;
        public function find(int $id): ?CalendarEntry { return $this->found; }
        public function save(CalendarEntry $entry, string $actorUid): int { $this->saved[] = [$entry, $actorUid]; return $entry->id() ?? $this->nextId; }
        public function overlappingShifts(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end, ?int $excludeId = null): array { return []; }
        public function containingShifts(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end, ?int $excludeId = null): array { return []; }
        public function children(int $id): array { return []; }
        public function delete(int $id): void { $this->deleted[] = ['entry', $id]; }
        public function deleteShift(int $id, string $mode): void { $this->deleted[] = ['shift', $id, $mode]; }
        public function deleteDefaultShift(int $id, string $mode): void { $this->deleted[] = ['default', $id, $mode]; }
        public function detachChild(int $id): void {}
    }
}

namespace OCA\AdCalendar\Service {
    use OCA\AdCalendar\Model\CalendarEntry;

    final class DefaultShiftMaterializer { public function syncWeek(\DateTimeImmutable $start, array $uids, array $absences = []): void {} }
    final class AbsenceService {
        public array $shiftChecks = [];
        public function query(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array { return []; }
        public function assertShiftWritable(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end): void { $this->shiftChecks[] = $uid; }
    }
    final class ContainingShiftAssignment { public function assign(CalendarEntry $entry, array $parents): CalendarEntry { return $entry; } }
    final class ShiftCalendarSyncService {
        public array $published = [];
        public array $removed = [];
        public function publish(CalendarEntry $entry): bool { $this->published[] = $entry; return true; }
        public function remove(CalendarEntry $entry): bool { $this->removed[] = $entry; return true; }
    }
}

namespace {

    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\AbsenceService;
    use OCA\AdCalendar\Service\CalendarService;
    use OCA\AdCalendar\Service\ContainingShiftAssignment;
    use OCA\AdCalendar\Service\DefaultShiftMaterializer;
    use OCA\AdCalendar\Service\ShiftCalendarSyncService;

    $repository = new CalendarEntryRepository();
    $sync = new ShiftCalendarSyncService();
    $absences = new AbsenceService();
    $service = new CalendarService($repository, new DefaultShiftMaterializer(), $absences, new ContainingShiftAssignment(), $sync);
    $payload = ['employeeUid' => 'person-a', 'start' => '2026-07-20T08:00:00+02:00', 'end' => '2026-07-20T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => ''];

    $id = $service->save($payload, null, 'planner');
    if ($id !== 41 || ($sync->published[0]->id() ?? null) !== 41) throw new RuntimeException('Neu gespeicherter Dienst wird nicht mit persistenter ID veröffentlicht.');

    $existing = CalendarEntry::get($payload + ['id' => 41]);
    $repository->found = $existing;
    $service->save(array_replace($payload, ['employeeUid' => 'person-b']), 41, 'planner');
    if (($sync->removed[0]->employeeUid() ?? '') !== 'person-a' || ($sync->published[1]->employeeUid() ?? '') !== 'person-b') {
        throw new RuntimeException('Personenwechsel entfernt den alten und veröffentlicht den neuen Dienst nicht.');
    }

    $repository->found = CalendarEntry::get(array_replace($payload, ['id' => 41, 'employeeUid' => 'person-b']));
    $service->delete(41, '');
    if (($sync->removed[1]->employeeUid() ?? '') !== 'person-b' || ($repository->deleted[0][0] ?? '') !== 'shift') {
        throw new RuntimeException('Gelöschter Dienst wird nicht aus dem privaten Kalender entfernt.');
    }

    $repository->found = null;
    $service->save(['employeeUid' => 'person-a', 'start' => '2026-07-20T10:00:00+02:00', 'end' => '2026-07-20T11:00:00+02:00', 'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Termin'], null, 'planner');
    if (count($sync->published) !== 3 || $sync->published[2]->type() !== CalendarEntry::TYPE_APPOINTMENT) throw new RuntimeException('Eigener Termin wurde nicht an den privaten Kalender übergeben.');
    $repository->found = CalendarEntry::get([
        'id' => 43, 'employeeUid' => 'person-a', 'start' => '2026-07-20T10:00:00+02:00', 'end' => '2026-07-20T11:00:00+02:00',
        'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Termin',
    ]);
    $service->delete(43, '');
    if (($sync->removed[2]->id() ?? null) !== 43 || ($repository->deleted[1] ?? null) !== ['entry', 43]) {
        throw new RuntimeException('Gelöschter eigener Termin bleibt im privaten Kalender.');
    }
    if ($absences->shiftChecks !== ['person-a', 'person-b']) throw new RuntimeException('Urlaub wird nicht ausschließlich für Dienstmutationen geprüft.');

    $repository->found = CalendarEntry::get([
        'id' => 42, 'employeeUid' => 'person-a', 'start' => '2026-07-20T10:00:00+02:00', 'end' => '2026-07-20T11:00:00+02:00',
        'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Serientermin', 'seriesUid' => 'series_demo', 'seriesTimezone' => 'Europe/Berlin',
    ]);
    $service->save(['employeeUid' => 'person-a', 'start' => '2026-07-20T10:30:00+02:00', 'end' => '2026-07-20T11:30:00+02:00', 'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Einzelausnahme'], 42, 'planner');
    $savedException = $repository->saved[array_key_last($repository->saved)][0];
    if ($savedException->seriesUid() !== 'series_demo' || $savedException->seriesTimezone() !== 'Europe/Berlin') {
        throw new RuntimeException('Bearbeitetes Einzelvorkommen hat seine Serienzuordnung verloren.');
    }

    echo "CalendarServiceShiftSyncTest: OK\n";
}
