<?php

declare(strict_types=1);

namespace OCP\Config {
    interface IUserConfig {
        public const FLAG_SENSITIVE = 1;
        public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string;
        public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool;
        public function getValuesByUsers(string $app, string $key, mixed $typedAs = null, ?array $userIds = null): array;
        public function deleteUserConfig(string $userId, string $app, string $key): void;
    }
}
namespace OCP\Security {
    interface ICrypto { public function encrypt(string $plaintext, string $password = ''): string; public function decrypt(string $authenticatedCiphertext, string $password = ''): string; }
}
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }

namespace {

    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCP\Config\IUserConfig;
    use OCP\Security\ICrypto;

    $config = new class implements IUserConfig {
        public array $values = [];
        public array $flags = [];
        public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string { return $this->values[$userId][$app][$key] ?? $default; }
        public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool { $this->values[$userId][$app][$key] = $value; $this->flags[$userId][$key] = $flags; return true; }
        public function getValuesByUsers(string $app, string $key, mixed $typedAs = null, ?array $userIds = null): array { $result = []; foreach ($this->values as $uid => $apps) if (isset($apps[$app][$key])) $result[$uid] = $apps[$app][$key]; return $result; }
        public function deleteUserConfig(string $userId, string $app, string $key): void { unset($this->values[$userId][$app][$key]); }
    };
    $crypto = new class implements ICrypto {
        public function encrypt(string $plaintext, string $password = ''): string { return 'cipher:' . base64_encode($plaintext); }
        public function decrypt(string $authenticatedCiphertext, string $password = ''): string { return base64_decode(substr($authenticatedCiphertext, 7), true) ?: ''; }
    };
    $store = new ExternalCalendarConnectionStore($config, $crypto);
    $store->save('person-a', 'kopano', [
        'serverUrl' => 'https://mail.adberlin.org/',
        'username' => 'person-a',
        'password' => 'nicht-ausgeben',
        'calendarUrl' => 'https://mail.adberlin.org/caldav/person-a/ad-dienste/',
    ]);
    $raw = json_encode($config->values, JSON_THROW_ON_ERROR);
    if (str_contains($raw, 'nicht-ausgeben') || ($config->flags['person-a']['external_calendar_kopano'] ?? 0) !== IUserConfig::FLAG_SENSITIVE) {
        throw new RuntimeException('CalDAV-Zugangsdaten sind nicht verschlüsselt und als sensibel gespeichert.');
    }
    if (($store->connection('person-a', 'kopano')['password'] ?? '') !== 'nicht-ausgeben') throw new RuntimeException('Verschlüsselte Verbindung ist intern nicht lesbar.');
    if ($store->privacyConnectedProviders('person-a') !== ['kopano']) throw new RuntimeException('Die Privacy-Projektion erkennt die gespeicherte Verbindung nicht ohne Geheimnisausgabe.');
    $public = $store->statuses('person-a', false, 'Team & Dienst');
    if (($public['kopano']['connected'] ?? false) !== true
        || ($public['kopano']['calendarName'] ?? '') !== 'Team & Dienst'
        || str_contains(json_encode($public), 'default.example.test')
        || str_contains(json_encode($public), 'nicht-ausgeben') || str_contains(json_encode($public), 'person-a')) {
        throw new RuntimeException('Öffentlicher Verbindungsstatus fehlt oder gibt Adressen beziehungsweise Zugangsdaten preis.');
    }
    $store->save('person-b', 'manual', ['serverUrl' => 'https://calendar.example.test/', 'username' => 'b', 'password' => 'secret']);
    if ($store->connectedEmployeeUids() !== ['person-a', 'person-b']) throw new RuntimeException('Hintergrundabgleich findet verbundene Konten nicht stabil.');
    $store->delete('person-a', 'kopano');
    if ($store->connection('person-a', 'kopano') !== null) throw new RuntimeException('Trennen entfernt die persönliche Verbindung nicht.');

    $state = $store->createOAuthState('person-b');
    if (!$store->hasPendingGoogleOAuthState('person-b')) throw new RuntimeException('Ein gespeicherter OAuth-Vorgang bleibt in der Privacy-Projektion unsichtbar.');
    if (!$store->consumeOAuthState('person-b', $state) || $store->consumeOAuthState('person-b', $state)) {
        throw new RuntimeException('OAuth-Status ist nicht nutzergebunden, einmalig oder replay-sicher.');
    }
    if ($store->hasPendingGoogleOAuthState('person-b')) throw new RuntimeException('Ein verbrauchter OAuth-Vorgang wird weiterhin als gespeichert gemeldet.');

    echo "ExternalCalendarConnectionStoreTest: OK\n";
}
