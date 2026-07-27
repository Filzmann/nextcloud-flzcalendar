<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Repository { final class CalendarEntryRepository { public array $shifts = []; public function findShiftsForEmployee(string $uid): array { return $this->shifts[$uid] ?? []; } } }
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore {
        public const CALDAV_PROVIDERS = ['kopano', 'apple', 'manual'];
        public array $data = [];
        public function statuses(string $uid, bool $googleConfigured): array { return ['kopano' => ['connected' => isset($this->data[$uid]['kopano'])], 'google' => ['connected' => false, 'available' => $googleConfigured]]; }
        public function save(string $uid, string $provider, array $connection): void { $this->data[$uid][$provider] = $connection; }
        public function connection(string $uid, string $provider): ?array { return $this->data[$uid][$provider] ?? null; }
        public function delete(string $uid, string $provider): void { unset($this->data[$uid][$provider]); }
    }
    final class ExternalCalendarUrlValidator { public function normalize(string $url): string { if (!str_starts_with($url, 'https://')) throw new \InvalidArgumentException('HTTPS erforderlich.'); return rtrim($url, '/') . '/'; } }
    final class CalDavClient { public array $connected = []; public array $probed = []; public function connect(array $connection): string { $this->connected[] = $connection; return $connection['serverUrl'] . 'calendars/ad-dienste/'; } public function probe(array $connection): int { $this->probed[] = $connection; return 207; } }
    final class ExternalShiftCalendarPublisher { public array $replaced = []; public array $removed = []; public bool $fail = false; public function replaceProvider(string $uid, string $provider, array $shifts): void { $this->replaced[] = [$uid, $provider, $shifts]; if ($this->fail) throw new \RuntimeException('Providerfehler'); } public function removeProviderCalendar(string $uid, string $provider, array $connection): void { $this->removed[] = [$uid, $provider, $connection]; } }
    final class GoogleOAuthService { public bool $configured = false; public function configured(): bool { return $this->configured; } }
}

namespace {
    require_once __DIR__ . '/../../lib/Service/ExternalCalendarService.php';

    use OCA\AdCalendar\CalendarSync\CalDavClient;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarUrlValidator;
    use OCA\AdCalendar\CalendarSync\ExternalShiftCalendarPublisher;
    use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\AdCalendar\Service\ExternalCalendarService;

    $entries = new CalendarEntryRepository();
    $entries->shifts['person-a'] = ['shift-a'];
    $store = new ExternalCalendarConnectionStore();
    $dav = new CalDavClient();
    $publisher = new ExternalShiftCalendarPublisher();
    $service = new ExternalCalendarService($store, new ExternalCalendarUrlValidator(), $dav, $publisher, $entries, new GoogleOAuthService());

    $probe = $service->testCalDavConnection('kopano', '', 'person-a', 'secret');
    if ($probe !== 207 || ($dav->probed[0]['serverUrl'] ?? '') !== 'https://mail.adberlin.org/caldav/person-a/' || $store->data !== [] || $publisher->replaced !== []) {
        throw new RuntimeException('Administrativer Kopano-Test nutzt nicht den realen Pfad oder verändert gespeicherte Verbindungen.');
    }

    $status = $service->connectCalDav('person-a', 'kopano', '', 'person-a', 'secret');
    $saved = $store->data['person-a']['kopano'] ?? [];
    if (($saved['serverUrl'] ?? '') !== 'https://mail.adberlin.org/caldav/person-a/' || ($saved['password'] ?? '') !== 'secret') throw new RuntimeException('Kopano-Vorgabe oder persönlicher CalDAV-Endpunkt wurde nicht gespeichert.');
    if (($status['kopano']['connected'] ?? false) !== true || $publisher->replaced !== [['person-a', 'kopano', ['shift-a']]]) throw new RuntimeException('Erstverbindung synchronisiert vorhandene Dienste nicht.');

    $publisher->fail = true;
    try { $service->connectCalDav('person-a', 'kopano', 'https://kopano-neu.example.test', 'person-a', 'new-secret'); throw new RuntimeException('Fehlgeschlagener Verbindungswechsel wurde gespeichert.'); }
    catch (RuntimeException $error) { if ($error->getMessage() === 'Fehlgeschlagener Verbindungswechsel wurde gespeichert.') throw $error; }
    if (($store->data['person-a']['kopano']['serverUrl'] ?? '') !== 'https://mail.adberlin.org/caldav/person-a/') throw new RuntimeException('Fehlgeschlagener Wechsel stellt die vorherige Verbindung nicht wieder her.');
    $publisher->fail = false;

    $service->disconnect('person-a', 'kopano');
    if ($publisher->removed === [] || isset($store->data['person-a']['kopano'])) throw new RuntimeException('Trennen räumt app-eigene Objekte und Zugangsdaten nicht in sicherer Reihenfolge auf.');

    foreach ([['unknown', 'https://example.test'], ['apple', 'http://example.test']] as [$provider, $url]) {
        try { $service->connectCalDav('person-a', $provider, $url, 'a', 'b'); throw new RuntimeException('Ungültige Providerdaten wurden akzeptiert.'); } catch (InvalidArgumentException) {}
    }

    echo "ExternalCalendarServiceTest: OK\n";
}
