<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\CalendarSync;

use InvalidArgumentException;
use OCA\FlzCalendar\AppInfo\Application;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use RuntimeException;

/** Kapselt den Google-Webserver-OAuth-Vertrag einschließlich einmaligem Status und Offline-Token. */
final class GoogleOAuthService {
    private const CLIENT_ID_KEY = 'google_oauth_client_id';
    private const CLIENT_SECRET_KEY = 'google_oauth_client_secret';
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    private const SCOPE = 'https://www.googleapis.com/auth/calendar.app.created';

    public function __construct(
        private IAppConfig $appConfig,
        private IURLGenerator $urls,
        private IClientService $clients,
        private ExternalCalendarConnectionStore $connections,
    ) {}

    public function configured(): bool {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    /** Liefert ausschließlich nicht geheime Werte für den Nextcloud-Adminbereich. */
    public function adminStatus(): array {
        return [
            'configured' => $this->configured(),
            'clientId' => $this->clientId(),
            'secretConfigured' => $this->clientSecret() !== '',
            'redirectUri' => $this->redirectUri(),
        ];
    }

    public function saveConfiguration(string $clientId, string $clientSecret = ''): array {
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);
        if ($clientId === '' || strlen($clientId) > 512) throw new InvalidArgumentException('Eine gültige Google-Client-ID ist erforderlich.');
        if (strlen($clientSecret) > 4096) throw new InvalidArgumentException('Das Google-Client-Secret ist zu lang.');

        $currentId = $this->clientId();
        $storedSecret = $this->clientSecret();
        if ($clientSecret === '') {
            if ($storedSecret === '') throw new InvalidArgumentException('Das Google-Client-Secret ist erforderlich.');
            if ($currentId !== '' && $currentId !== $clientId) {
                throw new InvalidArgumentException('Bei einer Änderung der Client-ID muss das zugehörige Client-Secret erneut eingegeben werden.');
            }
            $clientSecret = $storedSecret;
        }

        $this->appConfig->setValueString(Application::APP_ID, self::CLIENT_ID_KEY, $clientId, true);
        $this->appConfig->setValueString(Application::APP_ID, self::CLIENT_SECRET_KEY, $clientSecret, true, true);
        return $this->adminStatus();
    }

    public function removeConfiguration(): array {
        $this->appConfig->deleteKey(Application::APP_ID, self::CLIENT_ID_KEY);
        $this->appConfig->deleteKey(Application::APP_ID, self::CLIENT_SECRET_KEY);
        return $this->adminStatus();
    }

    public function authorizationUrl(string $uid): string {
        $this->assertConfigured();
        $state = $this->connections->createOAuthState($uid);
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchange(string $uid, string $state, string $code): array {
        $this->assertConfigured();
        if (!$this->connections->consumeOAuthState($uid, $state)) throw new InvalidArgumentException('Die Google-Anmeldung ist abgelaufen oder ungültig.');
        if (trim($code) === '') throw new InvalidArgumentException('Google hat keinen Autorisierungscode geliefert.');
        $tokens = $this->tokenRequest([
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);
        $refreshToken = (string)($tokens['refresh_token'] ?? '');
        if ($refreshToken === '') throw new RuntimeException('Google hat keinen dauerhaften Kalenderzugriff erteilt.');
        return [
            'refreshToken' => $refreshToken,
            'accessToken' => (string)($tokens['access_token'] ?? ''),
            'expiresAt' => time() + max(60, (int)($tokens['expires_in'] ?? 3600)),
        ];
    }

    public function cancel(string $uid, string $state): void {
        if (!$this->connections->consumeOAuthState($uid, $state)) throw new InvalidArgumentException('Die Google-Anmeldung ist abgelaufen oder ungültig.');
    }

    public function accessToken(string $uid, array $connection): string {
        $token = (string)($connection['accessToken'] ?? '');
        if ($token !== '' && (int)($connection['expiresAt'] ?? 0) > time() + 60) return $token;
        $refreshToken = (string)($connection['refreshToken'] ?? '');
        if ($refreshToken === '') throw new RuntimeException('Die Google-Verbindung muss erneut autorisiert werden.');
        $tokens = $this->tokenRequest([
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        $connection['accessToken'] = (string)($tokens['access_token'] ?? '');
        $connection['expiresAt'] = time() + max(60, (int)($tokens['expires_in'] ?? 3600));
        if ($connection['accessToken'] === '') throw new RuntimeException('Google hat kein Zugriffstoken geliefert.');
        $this->connections->save($uid, 'google', $connection);
        return $connection['accessToken'];
    }

    public function revoke(array $connection): void {
        $token = (string)($connection['refreshToken'] ?? $connection['accessToken'] ?? '');
        if ($token === '') return;
        $client = $this->clients->newClient();
        try {
            $response = $client->post(self::REVOKE_URL, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => http_build_query(['token' => $token], '', '&', PHP_QUERY_RFC3986),
                'timeout' => 20,
            ]);
        } catch (\Throwable $error) {
            throw new RuntimeException('Die Google-Freigabe konnte nicht widerrufen werden.', 0, $error);
        }
        if (!in_array($response->getStatusCode(), [200, 204], true)) throw new RuntimeException('Google hat den Widerruf abgewiesen.');
    }

    private function tokenRequest(array $fields): array {
        $client = $this->clients->newClient();
        try {
            $response = $client->post(self::TOKEN_URL, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => http_build_query($fields, '', '&', PHP_QUERY_RFC3986),
                'timeout' => 20,
            ]);
        } catch (\Throwable $error) {
            throw new RuntimeException('Google konnte nicht erreicht werden.', 0, $error);
        }
        $body = $response->getBody();
        if (is_resource($body)) $body = stream_get_contents($body) ?: '';
        $decoded = is_string($body) ? json_decode($body, true) : null;
        if ($response->getStatusCode() !== 200 || !is_array($decoded)) throw new RuntimeException('Google hat die Autorisierung abgewiesen.');
        return $decoded;
    }

    private function redirectUri(): string {
        return $this->urls->linkToRouteAbsolute('flzcalendar.external_calendar.googleCallback');
    }

    private function clientId(): string {
        return trim($this->appConfig->getValueString(Application::APP_ID, self::CLIENT_ID_KEY, '', true));
    }

    private function clientSecret(): string {
        return trim($this->appConfig->getValueString(Application::APP_ID, self::CLIENT_SECRET_KEY, '', true));
    }

    private function assertConfigured(): void {
        if (!$this->configured()) throw new RuntimeException('Google OAuth ist noch nicht durch die Administration konfiguriert.');
    }
}
