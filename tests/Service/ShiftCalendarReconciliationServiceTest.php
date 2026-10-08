<?php

declare(strict_types=1);

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; }
    }
}

namespace OCA\FlzCalendar\Repository {
    final class CalendarEntryRepository {
        public array $entries = [];
        public array $uids = [];
        public function findEntriesForEmployee(string $uid): array { return $this->entries[$uid] ?? []; }
        public function findShiftsForEmployee(string $uid): array { return array_values(array_filter($this->entries[$uid] ?? [], static fn($entry): bool => $entry->type() === 'shift')); }
        public function findEmployeeUidsWithEntries(): array { return $this->uids; }
        public function findEmployeeUidsWithShifts(): array { return $this->uids; }
    }
}

namespace OCA\FlzCalendar\Service {
    final class CalendarPreferenceService {
        public array $uids = [];
        public array $disabled = [];
        public function shiftCalendarSyncEmployeeUids(): array { return $this->uids; }
        public function shiftCalendarSyncEnabled(string $uid): bool { return !in_array($uid, $this->disabled, true); }
    }
    final class AbsenceService {
        public array $discovered = ['vacation-only'];
        public array $queried = [];
        public bool $failDiscovery = false;
        public function discover(\DateTimeImmutable $start, \DateTimeImmutable $end): array {
            if ($this->failDiscovery) throw new \RuntimeException('Urlaubs-Discovery nicht erreichbar');
            return $this->discovered;
        }
        public function query(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            $this->queried[] = [$start, $end, $uids];
            return $uids === ['vacation-only'] ? [new \OCA\LocalBase\Calendar\AbsenceInterval('vacation-only', $start, $start->modify('+1 day'), 'approved')] : [];
        }
    }
    final class CalendarSyncHorizon {
        public function range(): array { return [new \DateTimeImmutable('2026-01-01T00:00:00+01:00'), new \DateTimeImmutable('2029-01-01T00:00:00+01:00')]; }
    }
}
namespace OCA\FlzCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore {
        public array $uids = [];
        public function connectedEmployeeUids(): array { return $this->uids; }
        public function hasConnections(string $uid): bool { return in_array($uid, $this->uids, true); }
    }
    final class ExternalShiftCalendarPublisher {
        public array $replaced = [];
        public function replaceAll(string $uid, array $shifts): void { $this->replaced[] = [$uid, $shifts]; }
    }
}

namespace {

    use OCA\FlzCalendar\CalendarSync\PersonalCalendarPublisher;
    use OCA\FlzCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\FlzCalendar\CalendarSync\ExternalShiftCalendarPublisher;
    use OCA\FlzCalendar\Model\CalendarEntry;
    use OCA\FlzCalendar\Repository\CalendarEntryRepository;
    use OCA\FlzCalendar\Service\CalendarPreferenceService;
    use OCA\FlzCalendar\Service\AbsenceService;
    use OCA\FlzCalendar\Service\CalendarSyncHorizon;
    use OCA\FlzCalendar\Service\ShiftCalendarReconciliationService;
    use Psr\Log\LoggerInterface;

    $preferences = new CalendarPreferenceService();
    $preferences->uids = ['person-c'];
    $preferences->disabled = ['person-disabled'];
    $entries = new CalendarEntryRepository();
    $entries->uids = ['person-disabled', 'person-b', 'person-a'];
    $entries->entries['person-a'] = [CalendarEntry::get([
        'id' => 1,
        'employeeUid' => 'person-a',
        'start' => '2026-07-20T08:00:00+02:00',
        'end' => '2026-07-20T16:00:00+02:00',
        'type' => CalendarEntry::TYPE_SHIFT,
        'title' => '',
    ]), CalendarEntry::get([
        'id' => 2,
        'employeeUid' => 'person-a',
        'start' => '2026-07-20T10:00:00+02:00',
        'end' => '2026-07-20T11:00:00+02:00',
        'type' => CalendarEntry::TYPE_APPOINTMENT,
        'title' => 'Termin',
    ])];
    $publisher = new class implements PersonalCalendarPublisher {
        public array $replaced = [];
        public function replaceAllContent(string $employeeUid, array $entries, array $absences): void {
            $this->replaced[] = [$employeeUid, $entries, $absences];
            if ($employeeUid === 'person-b') throw new RuntimeException('DAV vorübergehend nicht erreichbar');
        }
        public function publish(CalendarEntry $entry): void {}
        public function removeEntry(CalendarEntry $entry): void {}
        public function removeCalendar(string $employeeUid): void {}
    };
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    };

    $external = new ExternalShiftCalendarPublisher();
    $externalConnections = new ExternalCalendarConnectionStore();
    $externalConnections->uids = ['person-b'];
    $absences = new AbsenceService();
    $service = new ShiftCalendarReconciliationService($entries, $preferences, $publisher, $external, $externalConnections, $absences, new CalendarSyncHorizon(), $logger);
    $result = $service->reconcileAll();
    if ($result !== ['attempted' => 4, 'succeeded' => 3, 'failed' => 1]) throw new RuntimeException('Abgleichszähler bilden Erfolg und Fehler nicht korrekt ab.');
    if (array_column($publisher->replaced, 0) !== ['person-a', 'person-b', 'person-c', 'vacation-only']) throw new RuntimeException('Ein DAV-Fehler oder ein Urlaub-only-Konto verhindert den vollständigen Abgleich.');
    if (count($publisher->replaced[0][1] ?? []) !== 2 || count($publisher->replaced[3][2] ?? []) !== 1) throw new RuntimeException('Abgleich verwendet nicht den vollständigen privaten Eintrags- und Urlaubsbestand je Person.');
    if (array_column($external->replaced, 0) !== ['person-b']) throw new RuntimeException('Ein interner DAV-Fehler blockiert den unabhängigen externen Dienstabgleich.');
    if (count($logger->errors) !== 1 || str_contains(json_encode($logger->errors), 'person-b')) throw new RuntimeException('Abgleichsfehler wird nicht sicher und datensparsam protokolliert.');
    if ($service->reconcileEmployee('person-disabled') || count($publisher->replaced) !== 4) throw new RuntimeException('Gezielter Abgleich ignoriert den persönlichen Opt-out nicht.');
    if (!$service->reconcileEmployee('person-default') || count($publisher->replaced) !== 5) throw new RuntimeException('Gezielter Abgleich verwendet den standardmäßig aktiven persönlichen Kalender nicht.');

    $absences->failDiscovery = true;
    $degradedResult = $service->reconcileAll();
    if ($degradedResult !== ['attempted' => 3, 'succeeded' => 2, 'failed' => 1]) {
        throw new RuntimeException('Eine ausgefallene optionale Urlaubs-Discovery blockiert den übrigen Kalenderabgleich.');
    }

    echo "ShiftCalendarReconciliationServiceTest: OK\n";
}
