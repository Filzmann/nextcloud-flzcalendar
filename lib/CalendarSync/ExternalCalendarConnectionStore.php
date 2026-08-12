<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use InvalidArgumentException;
use OCA\AdCalendar\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\Security\ICrypto;
use RuntimeException;

/** Speichert persönliche Providerzugänge ausschließlich verschlüsselt und als sensible Nextcloud-Benutzerwerte. */
final class ExternalCalendarConnectionStore {
    public const PROVIDERS = ['kopano', 'google', 'apple', 'manual'];
    public const CALDAV_PROVIDERS = ['kopano', 'apple', 'manual'];
    private const KEY_PREFIX = 'external_calendar_';
    private const OAUTH_STATE_KEY = 'external_calendar_google_oauth_state';

    public function __construct(private IUserConfig $config, private ICrypto $crypto) {}

    public function save(string $uid, string $provider, array $connection): void {
        $this->assertProvider($provider);
        if (trim($uid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        $plaintext = json_encode($connection, JSON_THROW_ON_ERROR);
        $this->config->setValueString(
            $uid,
            Application::APP_ID,
            self::KEY_PREFIX . $provider,
            $this->crypto->encrypt($plaintext),
            true,
            IUserConfig::FLAG_SENSITIVE,
        );
    }

    public function connection(string $uid, string $provider): ?array {
        $this->assertProvider($provider);
        $raw = $this->config->getValueString($uid, Application::APP_ID, self::KEY_PREFIX . $provider, '', true);
        if ($raw === '') return null;
        try {
            $decoded = json_decode($this->crypto->decrypt($raw), true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            throw new RuntimeException('Die gespeicherte Kalenderverbindung ist beschädigt.', 0, $error);
        }
        return is_array($decoded) ? $decoded : null;
    }

    /** @return array<string,array<string,mixed>> */
    public function connections(string $uid): array {
        $result = [];
        foreach (self::PROVIDERS as $provider) {
            $connection = $this->connection($uid, $provider);
            if ($connection !== null) $result[$provider] = $connection;
        }
        return $result;
    }

    public function delete(string $uid, string $provider): void {
        $this->assertProvider($provider);
        $this->config->deleteUserConfig($uid, Application::APP_ID, self::KEY_PREFIX . $provider);
    }

    /** Öffentlicher Statusvertrag; enthält bewusst weder Kennungen, Adressen noch Geheimnisse. */
    public function statuses(string $uid, bool $googleConfigured, string $calendarName): array {
        $labels = ['kopano' => 'Kopano', 'google' => 'Google', 'apple' => 'Apple', 'manual' => 'Manuelles CalDAV'];
        $result = [];
        foreach (self::PROVIDERS as $provider) {
            $connection = $this->connection($uid, $provider);
            $result[$provider] = [
                'provider' => $provider,
                'label' => $labels[$provider],
                'connected' => $connection !== null,
                'available' => $provider !== 'google' || $googleConfigured,
                'calendarName' => $calendarName,
            ];
        }
        return $result;
    }

    public function hasConnections(string $uid): bool {
        foreach (self::PROVIDERS as $provider) if ($this->hasStored($uid, $provider)) return true;
        return false;
    }

    /** @return list<string> */
    public function connectedEmployeeUids(): array {
        $uids = [];
        foreach (self::PROVIDERS as $provider) {
            foreach (array_keys($this->config->getValuesByUsers(Application::APP_ID, self::KEY_PREFIX . $provider)) as $uid) {
                if (trim((string)$uid) !== '') $uids[] = (string)$uid;
            }
        }
        sort($uids, SORT_STRING);
        return array_values(array_unique($uids));
    }

    public function createOAuthState(string $uid): string {
        if (trim($uid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        $state = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $payload = json_encode(['state' => $state, 'expiresAt' => time() + 600], JSON_THROW_ON_ERROR);
        $this->config->setValueString(
            $uid,
            Application::APP_ID,
            self::OAUTH_STATE_KEY,
            $this->crypto->encrypt($payload),
            true,
            IUserConfig::FLAG_SENSITIVE,
        );
        return $state;
    }

    public function consumeOAuthState(string $uid, string $state): bool {
        $raw = $this->config->getValueString($uid, Application::APP_ID, self::OAUTH_STATE_KEY, '', true);
        $this->config->deleteUserConfig($uid, Application::APP_ID, self::OAUTH_STATE_KEY);
        if ($raw === '' || $state === '') return false;
        try {
            $payload = json_decode($this->crypto->decrypt($raw), true, 8, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }
        return is_array($payload)
            && (int)($payload['expiresAt'] ?? 0) >= time()
            && is_string($payload['state'] ?? null)
            && hash_equals($payload['state'], $state);
    }

    private function hasStored(string $uid, string $provider): bool {
        return $this->config->getValueString($uid, Application::APP_ID, self::KEY_PREFIX . $provider, '', true) !== '';
    }

    private function assertProvider(string $provider): void {
        if (!in_array($provider, self::PROVIDERS, true)) throw new InvalidArgumentException('Unbekannter Kalenderanbieter.');
    }
}
