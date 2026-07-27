<?php

declare(strict_types=1);

namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; public function getHeader(string $key): string; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarEventSerializer.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ExternalCalendarUrlValidator.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ExternalCalendarConnectionException.php';
    require_once __DIR__ . '/../../lib/CalendarSync/CalDavClient.php';

    use OCA\AdCalendar\CalendarSync\CalDavClient;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionException;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarUrlValidator;
    use OCA\AdCalendar\CalendarSync\ShiftCalendarEventSerializer;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;

    $response = static fn(int $status, string $body = ''): IResponse => new class($status, $body) implements IResponse {
        public function __construct(private int $status, private string $body) {}
        public function getBody(): string { return $this->body; }
        public function getStatusCode(): int { return $this->status; }
        public function getHeader(string $key): string { return ''; }
    };
    $calendarXml = '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:"><d:response><d:href>/caldav/person/ad-dienste/</d:href><d:propstat><d:prop><d:displayname>AD Dienste</d:displayname></d:prop></d:propstat></d:response></d:multistatus>';
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
    $dav = new CalDavClient($clients, new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer());
    $connection = [
        'serverUrl' => 'https://calendar.example.test/',
        'calendarUrl' => 'https://calendar.example.test/caldav/person/ad-dienste/',
        'username' => 'person',
        'password' => 'secret',
    ];
    $shift = CalendarEntry::get(['id' => 42, 'employeeUid' => 'person', 'start' => '2026-07-22T08:00:00+02:00', 'end' => '2026-07-22T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    $dav->replaceAll($connection, [$shift]);
    $dav->remove($connection, 42);

    $methods = array_column($client->calls, 0);
    if ($methods !== ['PROPFIND', 'PROPFIND', 'GET', 'PUT', 'PROPFIND', 'GET', 'DELETE']) throw new RuntimeException('CalDAV-Abgleich ist nicht idempotent oder räumt Dienste nicht gezielt auf.');
    if (!str_contains($client->saved, 'X-AD-CALENDAR-SOURCE:adcalendar') || !str_contains($client->saved, 'X-AD-CALENDAR-ENTRY-ID:42')) throw new RuntimeException('CalDAV-Objekt trägt keine sichere Eigentumsmarkierung.');
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
    $probeStatus = (new CalDavClient($probeClients, new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer()))->probe([
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
        (new CalDavClient($blockedClients, new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer()))->connect([
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
