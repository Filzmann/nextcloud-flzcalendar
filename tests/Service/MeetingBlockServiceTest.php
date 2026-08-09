<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Repository {
    use DateTimeImmutable;
    use OCA\AdCalendar\Model\CalendarEntry;

    class CalendarEntryRepository {
        /** @var list<CalendarEntry> */ public array $range = [];
        /** @var list<CalendarEntry> */ public array $saved = [];
        /** @var list<CalendarEntry> */ public array $meeting = [];
        public ?string $deletedMeetingUid = null;
        public function findRange(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids): array { return $this->range; }
        public function containingShifts(string $uid, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): array {
            return array_values(array_filter($this->range, static fn(CalendarEntry $entry): bool => $entry->employeeUid() === $uid && $entry->type() === CalendarEntry::TYPE_SHIFT && $start >= $entry->start() && $end <= $entry->end()));
        }
        public function saveMany(array $entries, string $actorUid): array { $this->saved = $entries; $this->meeting = $entries; return array_map(static fn(int $index): int => $index + 10, array_keys($entries)); }
        public function findMeeting(string $meetingUid): array { return array_values(array_filter($this->meeting, static fn(CalendarEntry $entry): bool => $entry->meetingUid() === $meetingUid)); }
        public function deleteMeeting(string $meetingUid): void { $this->deletedMeetingUid = $meetingUid; $this->meeting = []; }
        public function find(int $id): ?CalendarEntry { return null; }
        public function save(CalendarEntry $entry, string $actorUid): int { return 1; }
        public function overlappingShifts(string $uid, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): array { return []; }
        public function children(int $id): array { return []; }
        public function detachChild(int $id): void {}
    }
}

namespace OCA\AdCalendar\Service {
    use DateTimeImmutable;
    class DefaultShiftMaterializer { public function syncWeek(DateTimeImmutable $start, array $uids, array $absences): void {} }
    class AbsenceService {
        public function query(DateTimeImmutable $start, DateTimeImmutable $end, array $uids): array { return []; }
        public function assertShiftWritable(string $uid, DateTimeImmutable $start, DateTimeImmutable $end): void { throw new \RuntimeException('Sperrtermine dürfen nicht durch Urlaub blockiert werden.'); }
    }
}

namespace {

    use OCA\AdCalendar\Exception\MeetingSlotUnavailableException;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\AbsenceService;
    use OCA\AdCalendar\Service\DefaultShiftMaterializer;
    use OCA\AdCalendar\Service\MeetingAvailabilityService;
    use OCA\AdCalendar\Service\MeetingService;

    $repository = new CalendarEntryRepository();
    $shift = static fn(int $id, string $uid): CalendarEntry => CalendarEntry::get(['id' => $id, 'employeeUid' => $uid, 'start' => '2026-07-13T08:00:00+02:00', 'end' => '2026-07-13T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    $repository->range = [$shift(1, 'anna'), $shift(2, 'bea')];
    $service = new MeetingService($repository, new MeetingAvailabilityService(), new DefaultShiftMaterializer(), new AbsenceService());
    $ids = $service->block(new DateTimeImmutable('2026-07-13T10:00:00+02:00'), new DateTimeImmutable('2026-07-13T11:00:00+02:00'), ['anna', 'bea'], 'Teamsitzung', 'anna');
    if ($ids !== [10, 11] || count($repository->saved) !== 2 || $repository->saved[0]->parentEntryId() !== 1 || $repository->saved[1]->parentEntryId() !== 2) throw new RuntimeException('Gemeinsame Meetingblockierung wurde nicht vollständig den Diensten zugeordnet.');
    $meetingUid = $repository->saved[0]->meetingUid();
    if ($meetingUid === null || $repository->saved[1]->meetingUid() !== $meetingUid) throw new RuntimeException('Blockierte Termine wurden nicht mit einer gemeinsamen Meeting-Kennung verknüpft.');

    $repository->range = [$shift(1, 'anna'), $shift(2, 'bea'), ...$repository->saved];
    $updatedIds = $service->update($meetingUid, new DateTimeImmutable('2026-07-13T11:00:00+02:00'), new DateTimeImmutable('2026-07-13T12:00:00+02:00'), 'Verschobene Teamsitzung', 'anna');
    if ($updatedIds !== [10, 11] || $repository->saved[0]->title() !== 'Verschobene Teamsitzung' || $repository->saved[1]->end()->format('H:i') !== '12:00') {
        throw new RuntimeException('Gemeinsame Meetingbearbeitung wurde nicht vollständig gespeichert.');
    }
    $service->delete($meetingUid);
    if ($repository->deletedMeetingUid !== $meetingUid || $repository->meeting !== []) throw new RuntimeException('Gemeinsames Meeting wurde nicht vollständig gelöscht.');
    try {
        $service->block(new DateTimeImmutable('2026-07-13T17:00:00+02:00'), new DateTimeImmutable('2026-07-13T18:00:00+02:00'), ['anna', 'bea'], 'Zu spät', 'anna');
        throw new RuntimeException('Nicht verfügbare Meetinglücke wurde gespeichert.');
    } catch (MeetingSlotUnavailableException) {}

    echo "MeetingBlockServiceTest: OK\n";
}
