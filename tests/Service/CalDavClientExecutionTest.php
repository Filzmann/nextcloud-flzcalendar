<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; public function getHeader(string $key): string; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\AdCalendar\Service { final class CalendarTargetConfig { public function calendarName(): string { return 'AD & Team'; } } }

namespace {

    use OCA\AdCalendar\CalendarSync\CalDavClient;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarUrlValidator;
    use OCA\AdCalendar\CalendarSync\ShiftCalendarEventSerializer;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Service\CalendarTargetConfig;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IL10N;

    $response = static fn(int $status, mixed $body = '', array $headers = []): IResponse => new class($status, $body, $headers) implements IResponse {
        public function __construct(private int $status, private mixed $body, private array $headers) {}
        public function getBody(): mixed { return $this->body; }
        public function getStatusCode(): int { return $this->status; }
        public function getHeader(string $key): string { return (string)($this->headers[$key] ?? ''); }
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
    $davFor = static function (IClient $client) use ($serviceFor): CalDavClient {
        return new CalDavClient(
            $serviceFor($client), new ExternalCalendarUrlValidator(),
            new ShiftCalendarEventSerializer(new class implements IL10N {
                public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); }
            }),
            new CalendarTargetConfig(),
        );
    };
    $connection = [
        'serverUrl' => 'https://calendar.example.test/',
        'username' => 'person-a',
        'password' => 'secret',
    ];
    $multistatus = static fn(string $responses): string => '<?xml version="1.0"?><d:multistatus xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">' . $responses . '</d:multistatus>';
    $resource = static fn(string $href, string $name = '', bool $calendar = false): string => '<d:response><d:href>' . htmlspecialchars($href, ENT_XML1) . '</d:href><d:propstat><d:prop>'
        . '<d:displayname>' . htmlspecialchars($name, ENT_XML1) . '</d:displayname><d:resourcetype>'
        . ($calendar ? '<c:calendar/>' : '') . '</d:resourcetype></d:prop></d:propstat></d:response>';

    $principalDiscovery = $multistatus('<d:response><d:href>/.well-known/caldav</d:href><d:propstat><d:prop>'
        . '<d:current-user-principal><d:href>/principals/person-a/</d:href></d:current-user-principal>'
        . '</d:prop></d:propstat></d:response>');
    $principal = $multistatus('<d:response><d:href>/principals/person-a/</d:href><d:propstat><d:prop>'
        . '<c:calendar-home-set><d:href>https://calendar.example.test/caldav/home/person-a/</d:href></c:calendar-home-set>'
        . '</d:prop></d:propstat></d:response>');
    $listing = $multistatus(
        $resource('/caldav/home/person-a/', 'Home')
        . $resource('team/', 'AD & Team', true)
    );
    $discoverClient = $clientFor([$response(207, $principalDiscovery), $response(207, $principal), $response(207, $listing)]);
    $discovered = $davFor($discoverClient)->connect($connection);
    if ($discovered !== 'https://calendar.example.test/caldav/home/person-a/team/'
        || array_column($discoverClient->calls, 0) !== ['PROPFIND', 'PROPFIND', 'PROPFIND']
        || ($discoverClient->calls[2][2]['headers']['Depth'] ?? '') !== '1') {
        throw new RuntimeException('CalDAV-Discovery folgt Principal und Home-Set nicht same-origin zum Zielkalender.');
    }

    $homeDiscovery = $multistatus('<d:response><d:href>/.well-known/caldav</d:href><d:propstat><d:prop>'
        . '<c:calendar-home-set><d:href>/caldav/home/person-a/</d:href></c:calendar-home-set>'
        . '</d:prop></d:propstat></d:response>');
    $emptyListing = $multistatus($resource('/caldav/home/person-a/', 'Home'));
    $verified = $multistatus($resource('/caldav/home/person-a/ad-dienste/', 'AD & Team', true));
    $createClient = $clientFor([
        $response(207, $homeDiscovery), $response(207, $emptyListing), $response(201), $response(207, $verified),
    ]);
    $created = $davFor($createClient)->connect($connection);
    if ($created !== 'https://calendar.example.test/caldav/home/person-a/ad-dienste/'
        || array_column($createClient->calls, 0) !== ['PROPFIND', 'PROPFIND', 'MKCALENDAR', 'PROPFIND']
        || !str_contains((string)($createClient->calls[2][2]['body'] ?? ''), 'AD &amp; Team')) {
        throw new RuntimeException('Fehlender CalDAV-Zielkalender wird nicht reserviert, escaped und verifiziert.');
    }

    $collisionClient = $clientFor([
        $response(207, $homeDiscovery), $response(207, $emptyListing), $response(405),
        $response(207, $multistatus($resource('/caldav/home/person-a/ad-dienste/', 'Privat', true))),
    ]);
    try {
        $davFor($collisionClient)->connect($connection);
        throw new RuntimeException('Fremd belegte reservierte CalDAV-Adresse wurde akzeptiert.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fremd belegte reservierte CalDAV-Adresse wurde akzeptiert.') throw $error;
    }

    $directConnection = $connection + ['calendarUrl' => 'https://calendar.example.test/caldav/home/person-a/ad-dienste/'];
    $directClient = $clientFor([$response(207)]);
    if ($davFor($directClient)->connect($directConnection) !== $directConnection['calendarUrl']) {
        throw new RuntimeException('Gespeicherte CalDAV-Adresse wird nicht read-only verifiziert.');
    }

    try {
        $davFor($clientFor([]))->renameCalendar($connection);
        throw new RuntimeException('CalDAV-Umbenennung ohne gespeicherte Kalenderadresse wurde akzeptiert.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'CalDAV-Umbenennung ohne gespeicherte Kalenderadresse wurde akzeptiert.') throw $error;
    }
    $notCalendar = $multistatus($resource('/caldav/home/person-a/ad-dienste/', 'AD & Team', false));
    try {
        $davFor($clientFor([$response(207, $notCalendar)]))->renameCalendar($directConnection + ['calendarName' => 'AD & Team']);
        throw new RuntimeException('Nicht-Kalender wurde als CalDAV-Ziel umbenannt.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Nicht-Kalender wurde als CalDAV-Ziel umbenannt.') throw $error;
    }

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $homeDiscovery);
    rewind($stream);
    $probeClient = $clientFor([$response(302, '', ['Location' => '/dav/']), $response(207, $stream)]);
    $probeConnection = $connection;
    $probeConnection['serverUrl'] = 'https://calendar.example.test:8443/';
    if ($davFor($probeClient)->probe($probeConnection) !== 207
        || $probeClient->calls[0][1] !== 'https://calendar.example.test:8443/.well-known/caldav'
        || $probeClient->calls[1][1] !== 'https://calendar.example.test:8443/dav/') {
        throw new RuntimeException('CalDAV-Probe verarbeitet same-origin Weiterleitung oder Stream-Antwort nicht korrekt.');
    }
    fclose($stream);

    foreach ([
        [$response(302), 'ungültige Weiterleitung'],
        [[$response(302, '', ['Location' => '/one']), $response(302, '', ['Location' => '/two']), $response(302, '', ['Location' => '/three']), $response(302, '', ['Location' => '/four'])], 'zu viele Weiterleitungen'],
    ] as [$redirectResponses, $label]) {
        $redirectResponses = is_array($redirectResponses) ? $redirectResponses : [$redirectResponses];
        try {
            $davFor($clientFor($redirectResponses))->probe($connection);
            throw new RuntimeException("CalDAV akzeptiert {$label}.");
        } catch (RuntimeException $error) {
            if ($error->getMessage() === "CalDAV akzeptiert {$label}.") throw $error;
        }
    }

    $objects = $multistatus(
        $resource('/caldav/home/person-a/ad-dienste/', 'AD & Team', true)
        . $resource('/caldav/home/person-a/ad-dienste/adcalendar-shift-72.ics')
        . $resource('/caldav/home/person-a/ad-dienste/private.ics')
    );
    $ownedData = "BEGIN:VCALENDAR\r\nX-AD-CALENDAR-SOURCE:adcalendar\r\nX-AD-CALENDAR-ENTRY-ID:72\r\nEND:VCALENDAR\r\n";
    $removeClient = $clientFor([$response(207), $response(207, $objects), $response(200, $ownedData), $response(204)]);
    $davFor($removeClient)->removeCalendar($directConnection);
    if (array_column($removeClient->calls, 0) !== ['PROPFIND', 'PROPFIND', 'GET', 'DELETE']
        || !str_ends_with($removeClient->calls[3][1], 'adcalendar-shift-72.ics')) {
        throw new RuntimeException('CalDAV-Bereinigung löscht nicht ausschließlich nachgewiesene App-Objekte.');
    }

    $invalidClient = $clientFor([$response(207)]);
    try {
        $davFor($invalidClient)->replaceAll($directConnection, [new stdClass()]);
        throw new RuntimeException('Ungültiges CalDAV-Abgleichsobjekt wurde akzeptiert.');
    } catch (InvalidArgumentException $error) {
        if ($error->getMessage() === 'Ungültiges CalDAV-Abgleichsobjekt wurde akzeptiert.') throw $error;
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
    foreach ([false, true] as $recoverResponse) {
        $failureClient->recoverResponse = $recoverResponse;
        try {
            $davFor($failureClient)->probe($connection);
            throw new RuntimeException('CalDAV-Netzwerkfehler wurde verschluckt.');
        } catch (RuntimeException $error) {
            if ($error->getMessage() === 'CalDAV-Netzwerkfehler wurde verschluckt.') throw $error;
        }
    }

    echo "CalDavClientExecutionTest: OK\n";
}
