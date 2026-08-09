<?php

declare(strict_types=1);

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; }
    }
}

namespace OCA\AdCalendar\Repository {
    final class CalendarEntryRepository {
        public array $entries = [];
        public function findEntriesForEmployee(string $uid): array { return $this->entries[$uid] ?? []; }
    }
}

namespace OCA\AdCalendar\Service {
    final class CalendarPreferenceService {
        public array $enabled = [];
        public function shiftCalendarSyncEnabled(string $uid): bool { return $this->enabled[$uid] ?? true; }
        public function saveShiftCalendarSyncEnabled(string $uid, bool $enabled): bool { return $this->enabled[$uid] = $enabled; }
    }
    final class CalendarTargetConfig { public function calendarName(): string { return 'Team & Dienst'; } }
    final class AbsenceService {
        public function query(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            return [new \OCA\LocalBase\Calendar\AbsenceInterval($uids[0], $start, $start->modify('+1 day'), 'planned')];
        }
    }
    final class CalendarSyncHorizon {
        public function range(): array { return [new \DateTimeImmutable('2026-01-01T00:00:00+01:00'), new \DateTimeImmutable('2029-01-01T00:00:00+01:00')]; }
    }
}
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore { public bool $connected = false; public function hasConnections(string $uid): bool { return $this->connected; } }
    final class ExternalShiftCalendarPublisher {
        public array $published = []; public array $removed = [];
        public function publish(\OCA\AdCalendar\Model\CalendarEntry $entry): void { $this->published[] = $entry; }
        public function remove(string $uid, int $id): void { $this->removed[] = [$uid, $id]; }
    }
}

namespace {

    use OCA\AdCalendar\CalendarSync\PersonalCalendarPublisher;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\ExternalShiftCalendarPublisher;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\AdCalendar\Service\CalendarTargetConfig;
    use OCA\AdCalendar\Service\AbsenceService;
    use OCA\AdCalendar\Service\CalendarSyncHorizon;
    use OCA\AdCalendar\Service\ShiftCalendarSyncService;
    use Psr\Log\LoggerInterface;

    $shift = CalendarEntry::get(['id' => 9, 'employeeUid' => 'sync-person', 'start' => '2026-07-20T08:00:00+02:00', 'end' => '2026-07-20T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    $appointment = CalendarEntry::get(['id' => 10, 'employeeUid' => 'sync-person', 'start' => '2026-07-20T10:00:00+02:00', 'end' => '2026-07-20T11:00:00+02:00', 'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => 'Termin']);
    $publisher = new class implements PersonalCalendarPublisher {
        public array $replaced = [];
        public array $published = [];
        public array $removed = [];
        public array $removedCalendars = [];
        public bool $fail = false;
        public function replaceAllContent(string $employeeUid, array $entries, array $absences): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->replaced[] = [$employeeUid, $entries, $absences]; }
        public function publish(CalendarEntry $entry): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->published[] = $entry; }
        public function removeEntry(CalendarEntry $entry): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->removed[] = [$entry->employeeUid(), $entry->id()]; }
        public function removeCalendar(string $employeeUid): void { if ($this->fail) throw new RuntimeException('DAV nicht erreichbar'); $this->removedCalendars[] = $employeeUid; }
    };
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    };
    $repository = new CalendarEntryRepository();
    $repository->entries['sync-person'] = [$shift, $appointment];
    $preferences = new CalendarPreferenceService();
    $external = new ExternalShiftCalendarPublisher();
    $externalConnections = new ExternalCalendarConnectionStore();
    $service = new ShiftCalendarSyncService($repository, $preferences, $publisher, $external, $externalConnections, new AbsenceService(), new CalendarSyncHorizon(), new CalendarTargetConfig(), $logger);

    if ($service->status('sync-person') !== ['enabled' => true, 'calendarName' => 'Team & Dienst']) throw new RuntimeException('Konfigurierter Zielkalendername fehlt im persönlichen Status.');
    $enabled = $service->configure('sync-person', true);
    if (!$enabled['enabled'] || count($publisher->replaced[0][1] ?? []) !== 2 || count($publisher->replaced[0][2] ?? []) !== 1 || !$preferences->enabled['sync-person']) throw new RuntimeException('Erneutes Aktivieren synchronisiert den vollständigen persönlichen Bestand nicht atomar vor dem Speichern.');
    if (!$service->publish($shift) || count($publisher->published) !== 1) throw new RuntimeException('Aktiver Dienst wurde nicht veröffentlicht.');
    if (!$service->publish($appointment) || count($publisher->published) !== 2 || $external->published !== []) throw new RuntimeException('Eigener Termin erreicht nicht ausschließlich den privaten Nextcloud-Kalender.');
    if (!$service->remove($shift) || $publisher->removed !== [['sync-person', 9]]) throw new RuntimeException('Aktiver Dienst wurde nicht aus dem Zielkalender entfernt.');
    if (!$service->remove($appointment) || $publisher->removed[1] !== ['sync-person', 10]) throw new RuntimeException('Eigener Termin wurde nicht aus dem privaten Zielkalender entfernt.');

    $publisher->fail = true;
    if ($service->publish($shift) || $logger->errors === []) throw new RuntimeException('DAV-Fehler gefährdet die führenden AD-Daten oder wird nicht protokolliert.');
    $publisher->fail = false;
    $disabled = $service->configure('sync-person', false);
    if ($disabled['enabled'] || $publisher->removedCalendars !== ['sync-person'] || $preferences->enabled['sync-person']) throw new RuntimeException('Opt-out entfernt den app-eigenen Kalender nicht vor dem Deaktivieren.');
    $externalConnections->connected = true;
    if (!$service->publish($shift) || count($external->published) !== 1 || count($publisher->published) !== 2) throw new RuntimeException('Externe Verbindung arbeitet unabhängig vom Nextcloud-Opt-out.');
    $externalConnections->connected = false;

    $publisher->fail = true;
    $preferences->saveShiftCalendarSyncEnabled('failed-person', false);
    try {
        $service->configure('failed-person', true);
        throw new RuntimeException('Fehlgeschlagene Aktivierung wurde gespeichert.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fehlgeschlagene Aktivierung wurde gespeichert.') throw $error;
    }
    if ($preferences->shiftCalendarSyncEnabled('failed-person')) throw new RuntimeException('Fehlgeschlagene Aktivierung bleibt aktiv.');

    echo "ShiftCalendarSyncServiceTest: OK\n";
}
