<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; public function getHeader(string $key): string; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\FlzCalendar\Service {
    final class CalendarTargetConfig { public function calendarName(): string { return 'Filzmann Dienste'; } }
}

namespace {

    use OCA\FlzCalendar\CalendarSync\CalDavClient;
    use OCA\FlzCalendar\CalendarSync\ExternalCalendarConnectionException;
    use OCA\FlzCalendar\CalendarSync\ExternalCalendarUrlValidator;
    use OCA\FlzCalendar\CalendarSync\ShiftCalendarEventSerializer;
    use OCA\FlzCalendar\Model\CalendarEntry;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IL10N;

    $l10n = new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } };

    $response = static fn(int $status, string $body = ''): IResponse => new class($status, $body) implements IResponse {
        public function __construct(private int $status, private string $body) {}
        public function getBody(): string { return $this->body; }
        public function getStatusCode(): int { return $this->status; }
        public function getHeader(string $key): string { return ''; }
    };
    $calendarXml = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:response><d:href>/caldav/person/flz-dienste/</d:href><d:propstat><d:prop><d:displayname>Filzmann Dienste</d:displayname></d:prop></d:propstat></d:response></d:multistatus>';
    $queue = [
        $response(207, $calendarXml),
        $response(207, $calendarXml),
        $response(404),
        $response(201),
        $response(207, $calendarXml),
        null,
        $response(204),
    ];
    $client = new class($queue) implements IClient {
        public array $calls = [];
        public string $saved = '';
        public function __construct(private array $queue) {}
        public function request(string $method, string $uri, array $options = []): IResponse {
            $this->calls[] = [$method, $uri, $options];
            if ($method === 'PUT') $this->saved = (string)($options['body'] ?? '');
            $next = array_shift($this->queue);
            if ($next === null) return new class($this) implements IResponse {
                public function __construct(private object $client) {}
                public function getBody(): string { return $this->client->saved; }
                public function getStatusCode(): int { return 200; }
                public function getHeader(string $key): string { return ''; }
            };
            return $next;
        }
        public function getResponseFromThrowable(\Throwable $error): IResponse { throw $error; }
    };
    $clients = new class($client) implements IClientService {
        public function __construct(private IClient $client) {}
        public function newClient(): IClient { return $this->client; }
    };
    $targets = new CalendarTargetConfig();
    $dav = new CalDavClient($clients, new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer($l10n), $targets);
    $connection = [
        'serverUrl' => 'https://calendar.example.test/',
        'calendarUrl' => 'https://calendar.example.test/caldav/person/flz-dienste/',
        'username' => 'person',
        'password' => 'secret',
    ];
    $shift = CalendarEntry::get(['id' => 42, 'employeeUid' => 'person', 'start' => '2026-07-22T08:00:00+02:00', 'end' => '2026-07-22T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    $dav->replaceAll($connection, [$shift]);
    $dav->remove($connection, 42);

    $methods = array_column($client->calls, 0);
    if ($methods !== ['PROPFIND', 'PROPFIND', 'GET', 'PUT', 'PROPFIND', 'GET', 'DELETE']) throw new RuntimeException('CalDAV-Abgleich ist nicht idempotent oder räumt Dienste nicht gezielt auf.');
    if (!str_contains($client->saved, 'X-FLZ-CALENDAR-SOURCE:flzcalendar') || !str_contains($client->saved, 'X-FLZ-CALENDAR-ENTRY-ID:42')) throw new RuntimeException('CalDAV-Objekt trägt keine sichere Eigentumsmarkierung.');
    foreach ($client->calls as [, $url, $options]) {
        if (str_contains($url, 'secret') || ($options['auth'] ?? null) !== ['person', 'secret'] || ($options['allow_redirects'] ?? null) !== false) {
            throw new RuntimeException('CalDAV-Zugangsdaten oder HTTPS-Weiterleitungsgrenze sind unsicher.');
        }
    }

    $probeClient = new class($response(207, $calendarXml)) implements IClient {
        public array $calls = [];
        public function __construct(private IResponse $response) {}
        public function request(string $method, string $uri, array $options = []): IResponse { $this->calls[] = [$method, $uri, $options]; return $this->response; }
        public function getResponseFromThrowable(\Throwable $error): IResponse { throw $error; }
    };
    $probeClients = new class($probeClient) implements IClientService {
        public function __construct(private IClient $client) {}
        public function newClient(): IClient { return $this->client; }
    };
    $probeStatus = (new CalDavClient($probeClients, new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer($l10n), $targets))->probe([
        'serverUrl' => 'https://calendar.example.test/caldav/person/',
        'username' => 'person',
        'password' => 'secret',
    ]);
    if ($probeStatus !== 207 || array_column($probeClient->calls, 0) !== ['PROPFIND']) {
        throw new RuntimeException('Administrativer CalDAV-Test ist nicht rein lesend.');
    }

    $blockedClient = new class($response(405)) implements IClient {
        public function __construct(private IResponse $response) {}
        public function request(string $method, string $uri, array $options = []): IResponse { throw new RuntimeException('HTTP 405'); }
        public function getResponseFromThrowable(\Throwable $error): IResponse { return $this->response; }
    };
    $blockedClients = new class($blockedClient) implements IClientService {
        public function __construct(private IClient $client) {}
        public function newClient(): IClient { return $this->client; }
    };
    try {
        (new CalDavClient($blockedClients, new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer($l10n), $targets))->connect([
            'serverUrl' => 'https://calendar.example.test/caldav/person/',
            'username' => 'person',
            'password' => 'secret',
        ]);
        throw new RuntimeException('HTTP 405 wurde nicht als blockierter CalDAV-Zugang erkannt.');
    } catch (ExternalCalendarConnectionException $error) {
        if ($error->getMessage() !== 'Der Kalenderanbieter erlaubt an dieser Adresse keine CalDAV-Verbindung (HTTP 405). Bitte wende dich an die Administration des Anbieters.') {
            throw new RuntimeException('HTTP-405-Diagnose ist für Nutzer*innen nicht eindeutig.');
        }
    }

    echo "CalDavClientTest: OK\n";
}
