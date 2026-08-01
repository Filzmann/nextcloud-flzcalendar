<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\AdCalendar\CalendarSync {
    final class GoogleOAuthService { public function accessToken(string $uid, array $connection): string { return 'token-for-' . $uid; } }
    final class ExternalCalendarConnectionStore {
        public array $saved = [];
        public function save(string $uid, string $provider, array $connection): void { $this->saved[] = [$uid, $provider, $connection]; }
    }
}
namespace OCA\AdCalendar\Service { final class CalendarTargetConfig { public function calendarName(): string { return 'AD Teamdienste'; } } }

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/CalendarSync/GoogleCalendarClient.php';

    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\GoogleCalendarClient;
    use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Service\CalendarTargetConfig;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IL10N;

    $response = static fn(int $status, array $body = []): IResponse => new class($status, $body) implements IResponse {
        public function __construct(private int $status, private array $body) {}
        public function getBody(): string { return $this->body === [] ? '' : json_encode($this->body, JSON_THROW_ON_ERROR); }
        public function getStatusCode(): int { return $this->status; }
    };
    $clientFor = static fn(array $responses): IClient => new class($responses) implements IClient {
        public array $calls = [];
        public function __construct(private array $responses) {}
        public function request(string $method, string $uri, array $options = []): IResponse {
            $this->calls[] = [$method, $uri, $options];
            $next = array_shift($this->responses);
            if ($next instanceof Throwable) throw $next;
            if (!$next instanceof IResponse) throw new RuntimeException('Fehlende Testantwort.');
            return $next;
        }
        public function getResponseFromThrowable(Throwable $error): IResponse { throw $error; }
    };
    $serviceFor = static fn(IClient $client): IClientService => new class($client) implements IClientService {
        public function __construct(private IClient $client) {}
        public function newClient(): IClient { return $this->client; }
    };
    $googleFor = static function (IClient $client, ?ExternalCalendarConnectionStore $store = null) use ($serviceFor): array {
        $store ??= new ExternalCalendarConnectionStore();
        $google = new GoogleCalendarClient(
            $serviceFor($client), new GoogleOAuthService(), $store, new CalendarTargetConfig(),
            new class implements IL10N { public function t(string $text, array $parameters = []): string { return 'L10N:' . strtr($text, $parameters); } },
        );
        return [$google, $store];
    };
    $shift = static fn(int $id): CalendarEntry => CalendarEntry::get([
        'id' => $id, 'employeeUid' => 'person-a', 'start' => '2026-07-22T08:00:00+02:00',
        'end' => '2026-07-22T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '',
    ]);
    $owned = static fn(string $id): array => [
        'id' => $id, 'extendedProperties' => ['private' => ['adcalendarSource' => 'adcalendar']],
    ];

    $connectClient = $clientFor([$response(200, ['id' => 'created/calendar'])]);
    [$google, $store] = $googleFor($connectClient);
    $created = $google->connect('person-a', ['refreshToken' => 'refresh']);
    if (($created['calendarId'] ?? '') !== 'created/calendar' || ($created['calendarName'] ?? '') !== 'AD Teamdienste'
        || $store->saved !== [['person-a', 'google', $created]]
        || array_column($connectClient->calls, 0) !== ['POST']) {
        throw new RuntimeException('Google-Zielkalender wird nicht eindeutig angelegt und gespeichert.');
    }
    $google->connect('person-a', $created);
    if (count($connectClient->calls) !== 1) throw new RuntimeException('Vorhandene Google-Kalender-ID löst eine Neuanlage aus.');

    [$missingIdGoogle] = $googleFor($clientFor([$response(200)]));
    try {
        $missingIdGoogle->connect('person-a', []);
        throw new RuntimeException('Google-Antwort ohne Kalender-ID wurde akzeptiert.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Google-Antwort ohne Kalender-ID wurde akzeptiert.') throw $error;
    }

    $renameClient = $clientFor([$response(200, ['id' => 'renamed-calendar'])]);
    [$renameGoogle] = $googleFor($renameClient);
    if (($renameGoogle->renameCalendar('person-a', [])['calendarId'] ?? '') !== 'renamed-calendar') {
        throw new RuntimeException('Umbenennung ohne Kalender-ID delegiert nicht an die sichere Anlage.');
    }

    $syncClient = $clientFor([
        $response(200, ['items' => [['id' => 'adcalendarshift99'], ['id' => 'adcalendarshift99'], ['id' => 7]], 'nextPageToken' => 'page-2']),
        $response(200, ['items' => [['id' => 'adcalendarshift100']]]),
        $response(200, $owned('adcalendarshift99')), $response(204),
        $response(404),
        $response(404), $response(200),
    ]);
    [$syncGoogle] = $googleFor($syncClient);
    $connection = ['calendarId' => 'calendar/example', 'refreshToken' => 'refresh'];
    $syncGoogle->replaceAll('person-a', $connection, [$shift(51)]);
    if (array_column($syncClient->calls, 0) !== ['GET', 'GET', 'GET', 'DELETE', 'GET', 'GET', 'POST']
        || !str_contains($syncClient->calls[1][1], 'pageToken=page-2')
        || !str_contains($syncClient->calls[3][1], 'adcalendarshift99')) {
        throw new RuntimeException('Paginierter Google-Abgleich bereinigt veraltete App-Ereignisse nicht gezielt.');
    }
    $published = json_decode((string)($syncClient->calls[6][2]['body'] ?? ''), true);
    if (($published['summary'] ?? '') !== 'L10N:Shift') throw new RuntimeException('Titelloser Google-Dienst erhält keinen lokalisierten Standardtitel.');

    $emptyClient = $clientFor([]);
    [$emptyGoogle] = $googleFor($emptyClient);
    $emptyGoogle->remove('person-a', [], 51);
    $emptyGoogle->removeCalendar('person-a', []);
    if ($emptyClient->calls !== []) throw new RuntimeException('Google-Bereinigung ohne Zielkalender führt Providerzugriffe aus.');

    $removeAllClient = $clientFor([
        $response(200, ['items' => [['id' => 'adcalendarshift70']]]),
        $response(200, $owned('adcalendarshift70')), $response(204),
    ]);
    [$removeAllGoogle] = $googleFor($removeAllClient);
    $removeAllGoogle->removeCalendar('person-a', $connection);
    if (array_column($removeAllClient->calls, 0) !== ['GET', 'GET', 'DELETE']) {
        throw new RuntimeException('Google-Kalenderbereinigung entfernt nicht ausschließlich gefundene App-Ereignisse.');
    }

    $foreignClient = $clientFor([$response(200, ['id' => 'adcalendarshift51'])]);
    [$foreignGoogle] = $googleFor($foreignClient);
    try {
        $foreignGoogle->publish('person-a', $connection, $shift(51));
        throw new RuntimeException('Fremdes Google-Ereignis mit reservierter ID wurde überschrieben.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fremdes Google-Ereignis mit reservierter ID wurde überschrieben.') throw $error;
    }

    $failureClient = new class($response) implements IClient {
        public bool $recoverResponse = false;
        public function __construct(private Closure $response) {}
        public function request(string $method, string $uri, array $options = []): IResponse { throw new RuntimeException('network'); }
        public function getResponseFromThrowable(Throwable $error): IResponse {
            if (!$this->recoverResponse) throw new RuntimeException('no response');
            return ($this->response)(500);
        }
    };
    [$failureGoogle] = $googleFor($failureClient);
    foreach ([false, true] as $recoverResponse) {
        $failureClient->recoverResponse = $recoverResponse;
        try {
            $failureGoogle->publish('person-a', $connection, $shift(51));
            throw new RuntimeException('Google-Netzwerkfehler wurde verschluckt.');
        } catch (RuntimeException $error) {
            if ($error->getMessage() === 'Google-Netzwerkfehler wurde verschluckt.') throw $error;
        }
    }

    echo "GoogleCalendarClientExecutionTest: OK\n";
}
