<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use OCA\AdCalendar\Model\CalendarEntry;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use RuntimeException;

/** Kleiner CalDAV-Adapter für app-eigene Dienstobjekte ohne neue Produktionsabhängigkeit. */
final class CalDavClient {
    private const OBJECT_PREFIX = 'adcalendar-shift-';

    public function __construct(
        private IClientService $clients,
        private ExternalCalendarUrlValidator $urls,
        private ShiftCalendarEventSerializer $serializer,
    ) {}

    /** Prüft Zugang und liefert die konkrete URL des sichtbaren Zielkalenders. */
    public function connect(array $connection): string {
        $this->assertCredentials($connection);
        $calendarUrl = trim((string)($connection['calendarUrl'] ?? ''));
        if ($calendarUrl !== '') {
            $calendarUrl = $this->urls->sameOrigin((string)$connection['serverUrl'], $calendarUrl);
            $this->expect($this->request('PROPFIND', $calendarUrl, $connection, $this->properties(['displayname']), ['Depth' => '0']), [200, 207]);
            return $calendarUrl;
        }
        return $this->discoverOrCreateCalendar($connection);
    }

    /** Prüft Adresse und Anmeldung ausschließlich lesend und gibt den erfolgreichen HTTP-Status zurück. */
    public function probe(array $connection): int {
        $this->assertCredentials($connection);
        $baseUrl = $this->urls->normalize((string)$connection['serverUrl']);
        $parts = parse_url($baseUrl);
        $probeUrl = (($parts['path'] ?? '/') === '/')
            ? 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . '/.well-known/caldav'
            : rtrim($baseUrl, '/');
        $response = $this->request('PROPFIND', $probeUrl, $connection, $this->properties(['current-user-principal', 'calendar-home-set']), ['Depth' => '0']);
        $this->expect($response, [200, 207]);
        return $response['status'];
    }

    /** @param list<CalendarEntry> $shifts */
    public function replaceAll(array $connection, array $shifts): void {
        $calendarUrl = $this->connect($connection);
        $expected = [];
        foreach ($shifts as $shift) {
            if (!$shift instanceof CalendarEntry) throw new InvalidArgumentException('Der externe Dienstabgleich enthält ungültige Daten.');
            $expected[$this->serializer->objectUri($shift)] = true;
        }
        foreach ($this->objects($calendarUrl, $connection) as $objectUri) {
            if ($this->isOwnedUri($objectUri) && !isset($expected[$objectUri])) {
                $this->deleteOwnedObject($calendarUrl, $objectUri, $connection);
            }
        }
        foreach ($shifts as $shift) $this->publishTo($calendarUrl, $connection, $shift);
    }

    public function publish(array $connection, CalendarEntry $shift): void {
        $this->publishTo($this->connect($connection), $connection, $shift);
    }

    public function remove(array $connection, int $shiftId): void {
        $calendarUrl = $this->connect($connection);
        $uri = self::OBJECT_PREFIX . $shiftId . '.ics';
        $existing = $this->request('GET', $this->objectUrl($calendarUrl, $uri), $connection, null, [], [404]);
        if ($existing['status'] === 404) return;
        $this->expect($existing, [200]);
        $this->assertOwnedData($uri, $existing['body']);
        $this->deleteObject($calendarUrl, $uri, $connection);
    }

    /** Entfernt nur App-Objekte; einen danach leeren externen Kalender lassen wir als sichtbaren, harmlosen Container bestehen. */
    public function removeCalendar(array $connection): void {
        $calendarUrl = $this->connect($connection);
        foreach ($this->objects($calendarUrl, $connection) as $objectUri) {
            if ($this->isOwnedUri($objectUri)) $this->deleteOwnedObject($calendarUrl, $objectUri, $connection);
        }
    }

    private function discoverOrCreateCalendar(array $connection): string {
        $baseUrl = $this->urls->normalize((string)$connection['serverUrl']);
        $parts = parse_url($baseUrl);
        $discoveryUrl = (($parts['path'] ?? '/') === '/')
            ? 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . '/.well-known/caldav'
            : rtrim($baseUrl, '/');
        $response = $this->request('PROPFIND', $discoveryUrl, $connection, $this->properties(['current-user-principal', 'calendar-home-set']), ['Depth' => '0']);
        $this->expect($response, [200, 207]);
        $principalHref = $this->propertyHref($response['body'], 'current-user-principal');
        $homeHref = $this->propertyHref($response['body'], 'calendar-home-set');
        if ($homeHref === null && $principalHref !== null) {
            $principalUrl = $this->sameOriginHref($baseUrl, $discoveryUrl, $principalHref);
            $principal = $this->request('PROPFIND', $principalUrl, $connection, $this->properties(['calendar-home-set']), ['Depth' => '0']);
            $this->expect($principal, [200, 207]);
            $homeHref = $this->propertyHref($principal['body'], 'calendar-home-set');
            $discoveryUrl = $principalUrl;
        }
        $homeUrl = $homeHref === null ? $baseUrl : $this->sameOriginHref($baseUrl, $discoveryUrl, $homeHref);
        $listing = $this->request('PROPFIND', $homeUrl, $connection, $this->properties(['displayname', 'resourcetype']), ['Depth' => '1']);
        $this->expect($listing, [200, 207]);
        foreach ($this->resources($listing['body']) as $resource) {
            if ($resource['calendar'] && $resource['displayName'] === ShiftCalendarPublisher::CALENDAR_NAME) {
                return $this->sameOriginHref($baseUrl, $homeUrl, $resource['href']);
            }
        }
        $calendarUrl = $this->sameOriginHref($baseUrl, $homeUrl, 'ad-dienste/');
        $created = $this->request('MKCALENDAR', $calendarUrl, $connection, '<?xml version="1.0" encoding="utf-8" ?>'
            . '<c:mkcalendar xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:set><d:prop>'
            . '<d:displayname>' . ShiftCalendarPublisher::CALENDAR_NAME . '</d:displayname><c:supported-calendar-component-set>'
            . '<c:comp name="VEVENT"/></c:supported-calendar-component-set></d:prop></d:set></c:mkcalendar>',
            ['Content-Type' => 'application/xml; charset=utf-8'], [405]);
        $this->expect($created, [201, 204, 405]);
        $verified = $this->request('PROPFIND', $calendarUrl, $connection, $this->properties(['displayname', 'resourcetype']), ['Depth' => '0']);
        $this->expect($verified, [200, 207]);
        $resources = $this->resources($verified['body']);
        if ($resources === [] || !$resources[0]['calendar'] || $resources[0]['displayName'] !== ShiftCalendarPublisher::CALENDAR_NAME) {
            throw new RuntimeException('Die reservierte CalDAV-Kalenderadresse ist bereits fremd belegt.');
        }
        return $calendarUrl;
    }

    private function publishTo(string $calendarUrl, array $connection, CalendarEntry $shift): void {
        $uri = $this->serializer->objectUri($shift);
        $objectUrl = $this->objectUrl($calendarUrl, $uri);
        $existing = $this->request('GET', $objectUrl, $connection, null, [], [404]);
        $stamp = null;
        if ($existing['status'] !== 404) {
            $this->expect($existing, [200]);
            $this->assertOwnedData($uri, $existing['body']);
            if (preg_match('/(?:^|\r?\n)DTSTAMP:(\d{8}T\d{6}Z)(?:\r?\n|$)/', $existing['body'], $match) === 1) $stamp = $match[1];
        }
        $data = $this->serializer->serialize($shift, $stamp);
        if ($existing['status'] !== 404 && $existing['body'] === $data) return;
        $saved = $this->request('PUT', $objectUrl, $connection, $data, ['Content-Type' => 'text/calendar; charset=utf-8']);
        $this->expect($saved, [200, 201, 204]);
    }

    /** @return list<string> */
    private function objects(string $calendarUrl, array $connection): array {
        $response = $this->request('PROPFIND', $calendarUrl, $connection, $this->properties(['getetag']), ['Depth' => '1']);
        $this->expect($response, [200, 207]);
        $objects = [];
        foreach ($this->resources($response['body']) as $resource) {
            $uri = rawurldecode(basename(rtrim(parse_url($resource['href'], PHP_URL_PATH) ?: '', '/')));
            if ($uri !== '' && $uri !== rawurldecode(basename(rtrim(parse_url($calendarUrl, PHP_URL_PATH) ?: '', '/')))) $objects[] = $uri;
        }
        return array_values(array_unique($objects));
    }

    private function deleteObject(string $calendarUrl, string $uri, array $connection): void {
        $deleted = $this->request('DELETE', $this->objectUrl($calendarUrl, $uri), $connection, null, [], [404]);
        $this->expect($deleted, [200, 204, 404]);
    }

    private function deleteOwnedObject(string $calendarUrl, string $uri, array $connection): void {
        $existing = $this->request('GET', $this->objectUrl($calendarUrl, $uri), $connection, null, [], [404]);
        if ($existing['status'] === 404) return;
        $this->expect($existing, [200]);
        $this->assertOwnedData($uri, $existing['body']);
        $this->deleteObject($calendarUrl, $uri, $connection);
    }

    private function request(string $method, string $url, array $connection, ?string $body = null, array $headers = [], array $acceptedErrors = []): array {
        $client = $this->clients->newClient();
        $options = [
            'auth' => [(string)$connection['username'], (string)$connection['password']],
            'headers' => $headers,
            'timeout' => 20,
            'allow_redirects' => false,
        ];
        if ($body !== null) $options['body'] = $body;
        $requestUrl = $url;
        for ($redirects = 0; ; $redirects++) {
            try {
                $response = $client->request($method, $requestUrl, $options);
            } catch (\Throwable $error) {
                try {
                    $response = $client->getResponseFromThrowable($error);
                } catch (\Throwable) {
                    throw new RuntimeException('Der Kalenderanbieter ist nicht erreichbar.', 0, $error);
                }
                if (!in_array($response->getStatusCode(), $acceptedErrors, true)) throw $this->rejectedResponse($response->getStatusCode(), $error);
            }
            if (!in_array($response->getStatusCode(), [301, 302, 307, 308], true)) break;
            if ($redirects >= 3) throw new RuntimeException('Der Kalenderanbieter leitet zu häufig weiter.');
            $location = trim($response->getHeader('Location'));
            if ($location === '') throw new RuntimeException('Der Kalenderanbieter hat eine ungültige Weiterleitung geliefert.');
            $requestUrl = $this->sameOriginHref((string)$connection['serverUrl'], $requestUrl, $location);
        }
        return ['status' => $response->getStatusCode(), 'body' => $this->body($response->getBody())];
    }

    private function expect(array $response, array $statuses): void {
        if (!in_array($response['status'], $statuses, true)) throw $this->rejectedResponse($response['status']);
    }

    private function rejectedResponse(int $status, ?\Throwable $previous = null): RuntimeException {
        if ($status === 405) {
            return new ExternalCalendarConnectionException(
                'Der Kalenderanbieter erlaubt an dieser Adresse keine CalDAV-Verbindung (HTTP 405). Bitte wende dich an die Administration des Anbieters.',
                $status,
                $previous,
            );
        }
        return new RuntimeException('Der Kalenderanbieter hat die Anfrage abgewiesen.', $status, $previous);
    }

    private function body(mixed $body): string {
        if (is_resource($body)) return stream_get_contents($body) ?: '';
        return is_string($body) ? $body : '';
    }

    private function properties(array $names): string {
        $properties = '';
        foreach ($names as $name) {
            $namespace = $name === 'calendar-home-set' ? 'c' : 'd';
            $properties .= '<' . $namespace . ':' . $name . '/>';
        }
        return '<?xml version="1.0" encoding="utf-8" ?><d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav"><d:prop>' . $properties . '</d:prop></d:propfind>';
    }

    private function propertyHref(string $xml, string $property): ?string {
        $xpath = $this->xpath($xml);
        $nodes = $xpath?->query('//*[local-name()="' . $property . '"]/*[local-name()="href"]');
        $value = $nodes !== false && $nodes->length > 0 ? trim($nodes->item(0)?->textContent ?? '') : '';
        return $value === '' ? null : $value;
    }

    /** @return list<array{href:string,displayName:string,calendar:bool}> */
    private function resources(string $xml): array {
        $xpath = $this->xpath($xml);
        if ($xpath === null) return [];
        $responses = $xpath->query('//*[local-name()="response"]');
        if ($responses === false) return [];
        $result = [];
        foreach ($responses as $response) {
            if (!$response instanceof DOMElement) continue;
            $href = trim($xpath->evaluate('string(./*[local-name()="href"][1])', $response));
            if ($href === '') continue;
            $result[] = [
                'href' => $href,
                'displayName' => trim($xpath->evaluate('string(.//*[local-name()="displayname"][1])', $response)),
                'calendar' => (bool)$xpath->evaluate('boolean(.//*[local-name()="resourcetype"]/*[local-name()="calendar"])', $response),
            ];
        }
        return $result;
    }

    private function xpath(string $xml): ?DOMXPath {
        if (trim($xml) === '') return null;
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) return null;
            return new DOMXPath($document);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function sameOriginHref(string $baseUrl, string $contextUrl, string $href): string {
        if (preg_match('#^https://#i', $href) === 1) return $this->urls->sameOrigin($baseUrl, $href);
        $context = parse_url($contextUrl);
        $origin = 'https://' . $context['host'] . (isset($context['port']) ? ':' . $context['port'] : '');
        if (str_starts_with($href, '/')) return $this->urls->sameOrigin($baseUrl, $origin . $href);
        return $this->urls->sameOrigin($baseUrl, rtrim($contextUrl, '/') . '/' . $href);
    }

    private function objectUrl(string $calendarUrl, string $uri): string {
        return rtrim($calendarUrl, '/') . '/' . rawurlencode($uri);
    }

    private function isOwnedUri(string $uri): bool {
        return preg_match('/^' . preg_quote(self::OBJECT_PREFIX, '/') . '\d+\.ics$/', $uri) === 1;
    }

    private function assertOwnedData(string $uri, string $data): void {
        if (!$this->isOwnedUri($uri)
            || preg_match('/(?:^|\r?\n)X-AD-CALENDAR-SOURCE:adcalendar(?:\r?\n|$)/', $data) !== 1
            || preg_match('/(?:^|\r?\n)X-AD-CALENDAR-ENTRY-ID:(\d+)(?:\r?\n|$)/', $data, $match) !== 1
            || $uri !== self::OBJECT_PREFIX . $match[1] . '.ics') {
            throw new RuntimeException('Die reservierte Dienstkennung wird bereits von einem fremden Kalenderobjekt verwendet.');
        }
    }

    private function assertCredentials(array $connection): void {
        $this->urls->normalize((string)($connection['serverUrl'] ?? ''));
        if (trim((string)($connection['username'] ?? '')) === '' || (string)($connection['password'] ?? '') === '') {
            throw new InvalidArgumentException('Benutzername und Passwort sind erforderlich.');
        }
    }
}
