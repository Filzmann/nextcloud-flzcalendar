<?php

declare(strict_types=1);

namespace OCP {
    interface IAppConfig {
        public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string;
        public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;
        public function deleteKey(string $app, string $key): void;
    }
    interface IURLGenerator { public function linkToRouteAbsolute(string $routeName, array $arguments = []): string; }
}
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; }
    interface IClient { public function post(string $uri, array $options = []): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }
namespace OCA\FlzCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore {
        public string $state = '';
        public bool $consumed = false;
        public array $saved = [];
        public function createOAuthState(string $uid): string { return $this->state = 'state-for-' . $uid; }
        public function consumeOAuthState(string $uid, string $state): bool { if ($this->consumed || $state !== $this->state) return false; $this->consumed = true; return true; }
        public function save(string $uid, string $provider, array $connection): void { $this->saved = [$uid, $provider, $connection]; }
    }
}

namespace {

    use OCA\FlzCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\FlzCalendar\CalendarSync\GoogleOAuthService;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IAppConfig;
    use OCP\IURLGenerator;

    $config = new class implements IAppConfig {
        public array $values = ['google_oauth_client_id' => 'client-id', 'google_oauth_client_secret' => 'client-secret'];
        public array $writes = [];
        public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string { return $this->values[$key] ?? $default; }
        public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool { $this->values[$key] = $value; $this->writes[] = [$key, $value, $lazy, $sensitive]; return true; }
        public function deleteKey(string $app, string $key): void { unset($this->values[$key]); }
    };
    $urls = new class implements IURLGenerator { public function linkToRouteAbsolute(string $routeName, array $arguments = []): string { return 'https://cloud.example.test/apps/flzcalendar/oauth/google/callback'; } };
    $response = static fn(array $data): IResponse => new class($data) implements IResponse {
        public function __construct(private array $data) {}
        public function getBody(): string { return json_encode($this->data, JSON_THROW_ON_ERROR); }
        public function getStatusCode(): int { return 200; }
    };
    $client = new class([$response(['access_token' => 'access-a', 'refresh_token' => 'refresh-a', 'expires_in' => 3600]), $response(['access_token' => 'access-b', 'expires_in' => 3600])]) implements IClient {
        public array $calls = [];
        public function __construct(private array $responses) {}
        public function post(string $uri, array $options = []): IResponse { $this->calls[] = [$uri, $options]; return array_shift($this->responses); }
    };
    $clients = new class($client) implements IClientService { public function __construct(private IClient $client) {} public function newClient(): IClient { return $this->client; } };
    $store = new ExternalCalendarConnectionStore();
    $oauth = new GoogleOAuthService($config, $urls, $clients, $store);

    $authorizationUrl = $oauth->authorizationUrl('person-a');
    parse_str((string)parse_url($authorizationUrl, PHP_URL_QUERY), $query);
    if (($query['state'] ?? '') !== 'state-for-person-a' || ($query['access_type'] ?? '') !== 'offline' || !str_contains((string)($query['scope'] ?? ''), 'calendar.app.created') || str_contains($authorizationUrl, 'client-secret')) {
        throw new RuntimeException('Google-Autorisierungs-URL ist nicht minimal, offlinefähig oder geheimnisarm.');
    }
    $tokens = $oauth->exchange('person-a', 'state-for-person-a', 'authorization-code');
    if (($tokens['refreshToken'] ?? '') !== 'refresh-a' || ($tokens['accessToken'] ?? '') !== 'access-a') throw new RuntimeException('Google-Code wurde nicht in Offline-Tokens getauscht.');
    try { $oauth->exchange('person-a', 'state-for-person-a', 'replay-code'); throw new RuntimeException('OAuth-Replay wurde akzeptiert.'); } catch (InvalidArgumentException) {}

    $store->consumed = false;
    $token = $oauth->accessToken('person-a', ['refreshToken' => 'refresh-a', 'accessToken' => 'expired', 'expiresAt' => 1]);
    if ($token !== 'access-b' || ($store->saved[1] ?? '') !== 'google' || ($store->saved[2]['refreshToken'] ?? '') !== 'refresh-a') throw new RuntimeException('Google-Refresh-Token wird nicht sicher erneuert und bewahrt.');
    $requests = json_encode($client->calls, JSON_THROW_ON_ERROR);
    if (!str_contains($requests, 'authorization_code') || !str_contains($requests, 'refresh_token')) throw new RuntimeException('Google-Tokenflows sind unvollständig.');

    $status = $oauth->adminStatus();
    if (!$status['configured'] || !$status['secretConfigured'] || $status['clientId'] !== 'client-id' || $status['redirectUri'] !== 'https://cloud.example.test/apps/flzcalendar/oauth/google/callback' || str_contains(json_encode($status), 'client-secret')) {
        throw new RuntimeException('Google-Adminstatus fehlt oder gibt das Client-Secret aus.');
    }
    $oauth->saveConfiguration('client-id', '');
    if ($config->values['google_oauth_client_secret'] !== 'client-secret') throw new RuntimeException('Leeres Secret ersetzt die bestehende Google-Konfiguration.');
    try {
        $oauth->saveConfiguration('new-client-id', '');
        throw new RuntimeException('Geänderte Client-ID wurde ohne neues Secret akzeptiert.');
    } catch (InvalidArgumentException) {}
    $savedStatus = $oauth->saveConfiguration('new-client-id', 'new-client-secret');
    if (!$savedStatus['configured'] || $config->writes[array_key_last($config->writes)] !== ['google_oauth_client_secret', 'new-client-secret', true, true]) {
        throw new RuntimeException('Google-Secret wird nicht lazy und sensitiv gespeichert.');
    }
    $oauth->removeConfiguration();
    if ($oauth->configured() || isset($config->values['google_oauth_client_id'], $config->values['google_oauth_client_secret'])) {
        throw new RuntimeException('Google-OAuth-Konfiguration wurde nicht vollständig entfernt.');
    }

    echo "GoogleOAuthServiceTest: OK\n";
}
