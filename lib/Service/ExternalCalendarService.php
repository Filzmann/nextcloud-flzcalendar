<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use InvalidArgumentException;
use OCA\AdCalendar\CalendarSync\CalDavClient;
use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
use OCA\AdCalendar\CalendarSync\ExternalCalendarUrlValidator;
use OCA\AdCalendar\CalendarSync\ExternalShiftCalendarPublisher;
use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
use OCA\AdCalendar\Repository\CalendarEntryRepository;

/** Orchestriert persönliche Verbindungen; Zugangsdaten verlassen diese Backend-Grenze nie. */
final class ExternalCalendarService {
    private const DEFAULTS = [
        'kopano' => 'https://mail.adberlin.org/',
        'apple' => 'https://caldav.icloud.com/',
        'manual' => '',
    ];

    public function __construct(
        private ExternalCalendarConnectionStore $connections,
        private ExternalCalendarUrlValidator $urls,
        private CalDavClient $calDav,
        private ExternalShiftCalendarPublisher $publisher,
        private CalendarEntryRepository $entries,
        private GoogleOAuthService $googleOAuth,
    ) {}

    public function status(string $uid): array {
        if (trim($uid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        return $this->connections->statuses($uid, $this->googleOAuth->configured());
    }

    public function connectCalDav(string $uid, string $provider, string $serverUrl, string $username, string $password): array {
        if (trim($uid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        $connection = $this->calDavConnection($provider, $serverUrl, $username, $password);
        $previous = $this->connections->connection($uid, $provider);
        $connection['calendarUrl'] = $this->calDav->connect($connection);
        if ($previous !== null && ($previous['calendarUrl'] ?? '') !== $connection['calendarUrl']) {
            $this->publisher->removeProviderCalendar($uid, $provider, $previous);
        }
        $this->connections->save($uid, $provider, $connection);
        try {
            $this->publisher->replaceProvider($uid, $provider, $this->entries->findShiftsForEmployee($uid));
        } catch (\Throwable $error) {
            if ($previous === null) $this->connections->delete($uid, $provider);
            else {
                $this->connections->save($uid, $provider, $previous);
                try { $this->publisher->replaceProvider($uid, $provider, $this->entries->findShiftsForEmployee($uid)); } catch (\Throwable) {}
            }
            throw $error;
        }
        return $this->status($uid);
    }

    /** Prüft dieselbe Provideradresse wie die persönliche Verbindung, ohne Daten zu speichern oder Kalender anzulegen. */
    public function testCalDavConnection(string $provider, string $serverUrl, string $username, string $password): int {
        return $this->calDav->probe($this->calDavConnection($provider, $serverUrl, $username, $password));
    }

    private function calDavConnection(string $provider, string $serverUrl, string $username, string $password): array {
        if (!in_array($provider, ExternalCalendarConnectionStore::CALDAV_PROVIDERS, true)) throw new InvalidArgumentException('Unbekannter CalDAV-Anbieter.');
        $serverUrl = trim($serverUrl) === '' ? self::DEFAULTS[$provider] : $serverUrl;
        $serverUrl = $this->urls->normalize($serverUrl);
        $username = trim($username);
        if ($username === '' || $password === '') throw new InvalidArgumentException('Benutzername und Passwort sind erforderlich.');
        if (strlen($username) > 320 || strlen($password) > 4096) throw new InvalidArgumentException('Die Zugangsdaten sind zu lang.');
        if ($provider === 'kopano' && (parse_url($serverUrl, PHP_URL_PATH) ?: '/') === '/') {
            $serverUrl .= 'caldav/' . rawurlencode($username) . '/';
        }
        return ['serverUrl' => $serverUrl, 'username' => $username, 'password' => $password];
    }

    public function connectGoogle(string $uid, array $tokens): array {
        if (trim($uid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        $previous = $this->connections->connection($uid, 'google');
        if ($previous !== null && isset($previous['calendarId'])) $tokens['calendarId'] = $previous['calendarId'];
        $this->connections->save($uid, 'google', $tokens);
        try {
            $this->publisher->replaceProvider($uid, 'google', $this->entries->findShiftsForEmployee($uid));
        } catch (\Throwable $error) {
            if ($previous === null) $this->connections->delete($uid, 'google');
            else $this->connections->save($uid, 'google', $previous);
            throw $error;
        }
        return $this->status($uid);
    }

    public function disconnect(string $uid, string $provider): array {
        $connection = $this->connections->connection($uid, $provider);
        if ($connection === null) return $this->status($uid);
        $this->publisher->removeProviderCalendar($uid, $provider, $connection);
        if ($provider === 'google') $this->googleOAuth->revoke($connection);
        $this->connections->delete($uid, $provider);
        return $this->status($uid);
    }
}
