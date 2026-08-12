<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use RuntimeException;

/** Veröffentlicht app-eigene Dienste über die Google Calendar REST API in einen sichtbaren Zielkalender. */
final class GoogleCalendarClient {
    private const API = 'https://www.googleapis.com/calendar/v3';

    public function __construct(
        private IClientService $clients,
        private GoogleOAuthService $oauth,
        private ExternalCalendarConnectionStore $connections,
        private CalendarTargetConfig $targets,
        private IL10N $l10n,
    ) {}

    public function connect(string $uid, array $connection): array {
        $calendarId = trim((string)($connection['calendarId'] ?? ''));
        if ($calendarId !== '') return $connection;
        $response = $this->request($uid, $connection, 'POST', self::API . '/calendars', ['summary' => $this->targets->calendarName()]);
        $calendarId = trim((string)($response['id'] ?? ''));
        if ($calendarId === '') throw new RuntimeException('Google hat keinen Zielkalender angelegt.');
        $connection['calendarId'] = $calendarId;
        $connection['calendarName'] = $this->targets->calendarName();
        $this->connections->save($uid, 'google', $connection);
        return $connection;
    }

    /** Benennt ausschließlich den über die gespeicherte Provider-ID und den letzten Namen belegten App-Kalender um. */
    public function renameCalendar(string $uid, array $connection): array {
        if (trim((string)($connection['calendarId'] ?? '')) === '') return $this->connect($uid, $connection);
        $calendarId = (string)$connection['calendarId'];
        $targetName = $this->targets->calendarName();
        $storedName = trim((string)($connection['calendarName'] ?? '')) ?: ShiftCalendarPublisher::CALENDAR_NAME;
        $url = self::API . '/calendars/' . rawurlencode($calendarId);
        $calendar = $this->request($uid, $connection, 'GET', $url);
        $actualId = trim((string)($calendar['id'] ?? ''));
        $actualName = trim((string)($calendar['summary'] ?? ''));
        if (($actualId !== '' && $actualId !== $calendarId) || ($actualName !== $storedName && $actualName !== $targetName)) {
            throw new RuntimeException('Der gespeicherte Google-Kalender kann nicht sicher als App-Kalender bestätigt werden.');
        }
        if ($actualName !== $targetName) {
            $updated = $this->request($uid, $connection, 'PATCH', $url, ['summary' => $targetName]);
            if (trim((string)($updated['summary'] ?? '')) !== $targetName
                || (isset($updated['id']) && (string)$updated['id'] !== $calendarId)) {
                throw new RuntimeException('Der Google-Kalendername wurde nicht übernommen.');
            }
        }
        $connection['calendarName'] = $targetName;
        return $connection;
    }

    /** @param list<CalendarEntry> $shifts */
    public function replaceAll(string $uid, array $connection, array $shifts): void {
        $connection = $this->connect($uid, $connection);
        $expected = [];
        foreach ($shifts as $shift) $expected[$this->eventId($shift)] = true;
        foreach ($this->ownedEvents($uid, $connection) as $eventId) {
            if (!isset($expected[$eventId])) $this->deleteEvent($uid, $connection, $eventId);
        }
        foreach ($shifts as $shift) $this->publish($uid, $connection, $shift);
    }

    public function publish(string $uid, array $connection, CalendarEntry $shift): void {
        $connection = $this->connect($uid, $connection);
        $calendarId = rawurlencode((string)$connection['calendarId']);
        $eventId = $this->eventId($shift);
        $eventUrl = self::API . '/calendars/' . $calendarId . '/events/' . rawurlencode($eventId);
        $existing = $this->request($uid, $connection, 'GET', $eventUrl, null, [404]);
        $payload = $this->event($shift, $eventId);
        if (($existing['_status'] ?? 200) === 404) {
            $this->request($uid, $connection, 'POST', self::API . '/calendars/' . $calendarId . '/events', $payload);
            return;
        }
        if (($existing['extendedProperties']['private']['adcalendarSource'] ?? '') !== 'adcalendar') {
            throw new RuntimeException('Die reservierte Google-Dienstkennung ist bereits fremd belegt.');
        }
        $this->request($uid, $connection, 'PUT', $eventUrl, $payload);
    }

    public function remove(string $uid, array $connection, int $shiftId): void {
        if (trim((string)($connection['calendarId'] ?? '')) === '') return;
        $this->deleteEvent($uid, $connection, 'adcalendarshift' . $shiftId);
    }

    public function removeCalendar(string $uid, array $connection): void {
        if (trim((string)($connection['calendarId'] ?? '')) === '') return;
        foreach ($this->ownedEvents($uid, $connection) as $eventId) $this->deleteEvent($uid, $connection, $eventId);
    }

    /** @return list<string> */
    private function ownedEvents(string $uid, array $connection): array {
        $calendarId = rawurlencode((string)$connection['calendarId']);
        $url = self::API . '/calendars/' . $calendarId . '/events?' . http_build_query([
            'privateExtendedProperty' => 'adcalendarSource=adcalendar',
            'showDeleted' => 'false',
            'maxResults' => 2500,
        ], '', '&', PHP_QUERY_RFC3986);
        $ids = [];
        do {
            $response = $this->request($uid, $connection, 'GET', $url);
            foreach (($response['items'] ?? []) as $event) {
                if (is_array($event) && is_string($event['id'] ?? null)) $ids[] = $event['id'];
            }
            $page = trim((string)($response['nextPageToken'] ?? ''));
            $url = $page === '' ? '' : self::API . '/calendars/' . $calendarId . '/events?' . http_build_query([
                'privateExtendedProperty' => 'adcalendarSource=adcalendar', 'showDeleted' => 'false', 'maxResults' => 2500, 'pageToken' => $page,
            ], '', '&', PHP_QUERY_RFC3986);
        } while ($url !== '');
        return array_values(array_unique($ids));
    }

    private function deleteEvent(string $uid, array $connection, string $eventId): void {
        $url = self::API . '/calendars/' . rawurlencode((string)$connection['calendarId']) . '/events/' . rawurlencode($eventId);
        $existing = $this->request($uid, $connection, 'GET', $url, null, [404]);
        if (($existing['_status'] ?? 200) === 404) return;
        if (($existing['extendedProperties']['private']['adcalendarSource'] ?? '') !== 'adcalendar') {
            throw new RuntimeException('Die reservierte Google-Dienstkennung ist bereits fremd belegt.');
        }
        $this->request($uid, $connection, 'DELETE', $url, null, [404]);
    }

    private function request(string $uid, array $connection, string $method, string $url, ?array $payload = null, array $acceptedErrors = []): array {
        $client = $this->clients->newClient();
        $options = [
            'headers' => ['Authorization' => 'Bearer ' . $this->oauth->accessToken($uid, $connection), 'Accept' => 'application/json'],
            'timeout' => 20,
        ];
        if ($payload !== null) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        try {
            $response = $client->request($method, $url, $options);
        } catch (\Throwable $error) {
            try { $response = $client->getResponseFromThrowable($error); }
            catch (\Throwable) { throw new RuntimeException('Google Calendar ist nicht erreichbar.', 0, $error); }
            if (!in_array($response->getStatusCode(), $acceptedErrors, true)) throw new RuntimeException('Google Calendar hat die Anfrage abgewiesen.', 0, $error);
        }
        $status = $response->getStatusCode();
        if ($status >= 300 && !in_array($status, $acceptedErrors, true)) throw new RuntimeException('Google Calendar hat unerwartet geantwortet.');
        $body = $response->getBody();
        if (is_resource($body)) $body = stream_get_contents($body) ?: '';
        $decoded = is_string($body) && trim($body) !== '' ? json_decode($body, true) : [];
        if (!is_array($decoded)) $decoded = [];
        $decoded['_status'] = $status;
        return $decoded;
    }

    private function event(CalendarEntry $shift, string $eventId): array {
        return [
            'id' => $eventId,
            'summary' => $shift->title() !== '' ? $shift->title() : $this->l10n->t('Shift'),
            'description' => $this->l10n->t('Automatically synchronised from AD Calendar. Please make changes there.'),
            'start' => ['dateTime' => $shift->start()->format(DATE_RFC3339)],
            'end' => ['dateTime' => $shift->end()->format(DATE_RFC3339)],
            'transparency' => 'opaque',
            'visibility' => 'private',
            'extendedProperties' => ['private' => ['adcalendarSource' => 'adcalendar', 'adcalendarEntryId' => (string)$shift->id()]],
        ];
    }

    private function eventId(CalendarEntry $shift): string {
        if ($shift->type() !== CalendarEntry::TYPE_SHIFT || $shift->id() === null) throw new RuntimeException('Nur persistierte Dienste können zu Google übertragen werden.');
        return 'adcalendarshift' . $shift->id();
    }
}
