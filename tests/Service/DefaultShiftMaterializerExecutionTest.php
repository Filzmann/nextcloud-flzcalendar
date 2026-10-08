<?php

declare(strict_types=1);

namespace OCP {
    interface IConfig { public function getSystemValueString(string $key, string $default = ''): string; }
}
namespace OCP\Config {
    interface IUserConfig { public function getValueString(string $uid, string $app, string $key, string $default = ''): string; }
}
namespace OCA\FlzCalendar\Repository {
    use OCA\FlzCalendar\Model\CalendarEntry;
    final class CalendarEntryRepository {
        public array $existing = [];
        public array $removed = [];
        public array $attached = [];
        public array $saved = [];
        public function findDefaultOccurrence(string $uid, string $date): ?CalendarEntry { return $this->existing[$date] ?? null; }
        public function removeGeneratedDefault(int $id): void { $this->removed[] = $id; }
        public function overlappingShifts(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end, ?int $excludeId): array {
            return $start->format('Y-m-d') === '2026-07-10' ? ['occupied'] : [];
        }
        public function save(CalendarEntry $entry, string $actor): int { $this->saved[] = [$entry, $actor]; return 77; }
        public function attachContainedAppointments(int $id, string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end): void {
            $this->attached[] = [$id, $uid, $start, $end];
        }
    }
}
namespace OCA\FlzCalendar\Service {
    use OCA\FlzCalendar\Model\CalendarEntry;
    final class CalendarPreferenceService {
        public array $defaults = [];
        public function storedShiftDefaults(string $uid): ?array { return $this->defaults[$uid] ?? null; }
    }
    final class DefaultShiftOccurrenceFactory {
        public array $calls = [];
        public function create(string $uid, string $date, array $rule, \DateTimeZone $timezone, ?int $id = null): CalendarEntry {
            $this->calls[] = [$uid, $date, $timezone->getName(), $id];
            $start = new \DateTimeImmutable($date . ' ' . $rule['start'], $timezone);
            $end = new \DateTimeImmutable($date . ' ' . $rule['end'], $timezone);
            if ($end <= $start) $end = $end->modify('+1 day');
            return CalendarEntry::get([
                'id' => $id, 'employeeUid' => $uid, 'start' => $start, 'end' => $end,
                'type' => 'shift', 'defaultDate' => $date,
            ]);
        }
    }
    final class ShiftCalendarSyncService {
        public array $removed = [];
        public array $published = [];
        public function remove(CalendarEntry $entry): void { $this->removed[] = $entry->id(); }
        public function publish(CalendarEntry $entry): void { $this->published[] = $entry; }
    }
    final class PlanningConflictService {
        public array $checks = [];
        public function checkShift(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end): array {
            $this->checks[] = [$uid, $start->format('Y-m-d')];
            return ['status' => 'available', 'blocked' => $start->format('Y-m-d') === '2026-07-11'];
        }
    }
}

namespace {

    use OCA\FlzCalendar\Model\CalendarEntry;
    use OCA\FlzCalendar\Repository\CalendarEntryRepository;
    use OCA\FlzCalendar\Service\CalendarPreferenceService;
    use OCA\FlzCalendar\Service\DefaultShiftMaterializer;
    use OCA\FlzCalendar\Service\DefaultShiftOccurrenceFactory;
    use OCA\FlzCalendar\Service\PlanningConflictService;
    use OCA\FlzCalendar\Service\ShiftCalendarSyncService;
    use OCP\Config\IUserConfig;
    use OCP\IConfig;

    $entry = static function(int $id, string $date, bool $modified = false, bool $deleted = false): CalendarEntry {
        return CalendarEntry::get([
            'id' => $id, 'employeeUid' => 'person-a',
            'start' => "{$date}T08:00:00+00:00", 'end' => "{$date}T16:00:00+00:00",
            'type' => 'shift', 'defaultDate' => $date,
            'defaultModified' => $modified, 'defaultDeleted' => $deleted,
        ]);
    };
    $entries = new CalendarEntryRepository();
    $entries->existing = [
        '2026-07-06' => $entry(1, '2026-07-06', false, true),
        '2026-07-07' => $entry(2, '2026-07-07', true),
        '2026-07-08' => $entry(3, '2026-07-08'),
        '2026-07-09' => $entry(4, '2026-07-09'),
        '2026-07-11' => $entry(6, '2026-07-11'),
    ];
    $preferences = new CalendarPreferenceService();
    $preferences->defaults['person-a'] = [
        '1' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'],
        '2' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'],
        '3' => ['enabled' => false, 'start' => '08:00', 'end' => '16:00'],
        '4' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'],
        '5' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'],
        '6' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'],
        '7' => ['enabled' => true, 'start' => '22:00', 'end' => '06:00'],
    ];
    $factory = new DefaultShiftOccurrenceFactory();
    $sync = new ShiftCalendarSyncService();
    $config = new class implements IConfig {
        public function getSystemValueString(string $key, string $default = ''): string { return 'Invalid/Timezone'; }
    };
    $userConfig = new class implements IUserConfig {
        public function getValueString(string $uid, string $app, string $key, string $default = ''): string { return ''; }
    };
    $absence = new class {
        public function employeeUid(): string { return 'person-a'; }
        public function approved(): bool { return false; }
        public function overlaps(\DateTimeImmutable $start, \DateTimeImmutable $end): bool { return $start->format('Y-m-d') === '2026-07-09'; }
    };
    $irrelevantAbsence = new class {
        public function employeeUid(): string { return 'other'; }
        public function approved(): bool { return false; }
        public function overlaps(\DateTimeImmutable $start, \DateTimeImmutable $end): bool { return true; }
    };

    $planning = new PlanningConflictService();
    $materializer = new DefaultShiftMaterializer($entries, $preferences, $factory, $config, $userConfig, $sync, $planning);
    $materializer->syncWeek(new DateTimeImmutable('2026-07-06T00:00:00Z'), ['person-a', 'person-a', 'no-defaults'], [$irrelevantAbsence, $absence]);

    if ($entries->removed !== [3, 4, 6] || $sync->removed !== [3, 4, 6]) {
        throw new RuntimeException('Deaktivierte oder durch Urlaub beziehungsweise Assistenz blockierte Standarddienste werden nicht konsistent entfernt.');
    }
    if (count($entries->saved) !== 1 || $entries->saved[0][1] !== 'person-a'
        || $entries->saved[0][0]->defaultDate() !== '2026-07-12'
        || $entries->saved[0][0]->end()->getTimestamp() - $entries->saved[0][0]->start()->getTimestamp() !== 8 * 3600) {
        throw new RuntimeException('Nur der freie neue Standarddienst wird einschließlich Nachtschicht materialisiert.');
    }
    if (count($entries->attached) !== 1 || $entries->attached[0][0] !== 77
        || count($sync->published) !== 1 || $sync->published[0]->id() !== 77) {
        throw new RuntimeException('Neuer Standarddienst ordnet Termine nicht zu oder wird nicht veröffentlicht.');
    }
    if (count($factory->calls) !== 4 || array_unique(array_column($factory->calls, 2)) !== ['UTC']) {
        throw new RuntimeException('Geschützte Vorkommen werden nicht übersprungen oder ungültige Zeitzone fällt nicht auf UTC zurück.');
    }
    if (!in_array(['person-a', '2026-07-11'], $planning->checks, true)) {
        throw new RuntimeException('Regelmäßige Kalenderdienste werden nicht gegen Assistenzschichten geprüft.');
    }

    echo "DefaultShiftMaterializerExecutionTest: OK\n";
}
