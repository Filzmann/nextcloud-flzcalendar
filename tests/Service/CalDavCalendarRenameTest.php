<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; public function getHeader(string $name): string; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\FlzCalendar\Service {
    final class CalendarTargetConfig { public const DEFAULT_CALENDAR_NAME = 'Filzmann Dienste'; public function calendarName(): string { return 'Team & Dienst'; } }
}

namespace {

    use OCA\FlzCalendar\CalendarSync\CalDavClient;
    use OCA\FlzCalendar\CalendarSync\ExternalCalendarUrlValidator;
    use OCA\FlzCalendar\CalendarSync\ShiftCalendarEventSerializer;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IL10N;

    $l10n = new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } };

    $calendarXml = static fn(string $name): string => '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:response><d:href>/caldav/person/flz-dienste/</d:href><d:propstat><d:prop><d:displayname>' . htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</d:displayname><d:resourcetype><c:calendar/></d:resourcetype></d:prop></d:propstat></d:response></d:multistatus>';
    $response = static fn(int $status, string $body = ''): IResponse => new class($status, $body) implements IResponse {
        public function __construct(private int $status, private string $body) {}
        public function getBody(): string { return $this->body; }
        public function getStatusCode(): int { return $this->status; }
        public function getHeader(string $name): string { return ''; }
    };
    $clientFor = static fn(array $responses): IClient => new class($responses) implements IClient {
        public array $calls = [];
        public function __construct(private array $responses) {}
        public function request(string $method, string $uri, array $options = []): IResponse { $this->calls[] = [$method, $uri, $options]; return array_shift($this->responses); }
        public function getResponseFromThrowable(\Throwable $error): IResponse { throw $error; }
    };
    $serviceFor = static fn(IClient $client): IClientService => new class($client) implements IClientService {
        public function __construct(private IClient $client) {}
        public function newClient(): IClient { return $this->client; }
    };
    $connection = [
        'serverUrl' => 'https://calendar.example.test/',
        'calendarUrl' => 'https://calendar.example.test/caldav/person/flz-dienste/',
        'calendarName' => 'Filzmann Dienste',
        'username' => 'person-a',
        'password' => 'secret',
    ];

    $client = $clientFor([$response(207, $calendarXml('Filzmann Dienste')), $response(207), $response(207, $calendarXml('Team & Dienst'))]);
    $dav = new CalDavClient($serviceFor($client), new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer($l10n), new CalendarTargetConfig());
    $renamed = $dav->renameCalendar($connection);
    if (($renamed['calendarName'] ?? '') !== 'Team & Dienst' || ($renamed['calendarUrl'] ?? '') !== $connection['calendarUrl']
        || array_column($client->calls, 0) !== ['PROPFIND', 'PROPPATCH', 'PROPFIND']
        || !str_contains((string)($client->calls[1][2]['body'] ?? ''), 'Team &amp; Dienst')) {
        throw new RuntimeException('Vorhandener CalDAV-Kalender wird nicht sicher und XML-escaped umbenannt.');
    }

    $stableClient = $clientFor([$response(207, $calendarXml('Team & Dienst'))]);
    $stable = new CalDavClient($serviceFor($stableClient), new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer($l10n), new CalendarTargetConfig());
    $stable->renameCalendar($renamed);
    if (array_column($stableClient->calls, 0) !== ['PROPFIND']) throw new RuntimeException('Wiederholte CalDAV-Umbenennung ist nicht idempotent.');

    $foreignClient = $clientFor([$response(207, $calendarXml('Privat'))]);
    $foreign = new CalDavClient($serviceFor($foreignClient), new ExternalCalendarUrlValidator(), new ShiftCalendarEventSerializer($l10n), new CalendarTargetConfig());
    try {
        $foreign->renameCalendar($connection);
        throw new RuntimeException('Fremder CalDAV-Kalender wurde umbenannt.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fremder CalDAV-Kalender wurde umbenannt.') throw $error;
    }
    if (array_column($foreignClient->calls, 0) !== ['PROPFIND']) throw new RuntimeException('Abgewiesener Fremdkalender wurde verändert.');

    echo "CalDavCalendarRenameTest: OK\n";
}
