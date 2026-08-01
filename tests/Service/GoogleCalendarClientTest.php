<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\AdCalendar\CalendarSync {
    final class GoogleOAuthService { public function accessToken(string $uid, array $connection): string { return 'access-token'; } }
    final class ExternalCalendarConnectionStore { public array $saved = []; public function save(string $uid, string $provider, array $connection): void { $this->saved[] = [$uid, $provider, $connection]; } }
}
namespace OCA\AdCalendar\Service { final class CalendarTargetConfig { public function calendarName(): string { return 'AD Dienste'; } } }

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
    $owned = ['id' => 'adcalendarshift51', 'extendedProperties' => ['private' => ['adcalendarSource' => 'adcalendar']]];
    $client = new class([$response(404), $response(200), $response(200, $owned), $response(200), $response(200, $owned), $response(204)]) implements IClient {
        public array $calls = [];
        public function __construct(private array $responses) {}
        public function request(string $method, string $uri, array $options = []): IResponse { $this->calls[] = [$method, $uri, $options]; return array_shift($this->responses); }
        public function getResponseFromThrowable(\Throwable $error): IResponse { throw $error; }
    };
    $clients = new class($client) implements IClientService { public function __construct(private IClient $client) {} public function newClient(): IClient { return $this->client; } };
    $l10n = new class implements IL10N {
        public function t(string $text, array $parameters = []): string {
            return $text === 'Automatically synchronised from AD Calendar. Please make changes there.'
                ? 'Translated provider description.'
                : strtr($text, $parameters);
        }
    };
    $google = new GoogleCalendarClient($clients, new GoogleOAuthService(), new ExternalCalendarConnectionStore(), new CalendarTargetConfig(), $l10n);
    $connection = ['calendarId' => 'calendar@example.test', 'refreshToken' => 'refresh'];
    $shift = CalendarEntry::get(['id' => 51, 'employeeUid' => 'person-a', 'start' => '2026-07-22T08:00:00+02:00', 'end' => '2026-07-22T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => 'Frühdienst']);

    $google->publish('person-a', $connection, $shift);
    $google->publish('person-a', $connection, $shift);
    $google->remove('person-a', $connection, 51);

    if (array_column($client->calls, 0) !== ['GET', 'POST', 'GET', 'PUT', 'GET', 'DELETE']) throw new RuntimeException('Google-Ereignisse werden nicht idempotent erstellt, aktualisiert und gelöscht.');
    $created = json_decode((string)($client->calls[1][2]['body'] ?? ''), true);
    if (($created['id'] ?? '') !== 'adcalendarshift51'
        || ($created['extendedProperties']['private']['adcalendarSource'] ?? '') !== 'adcalendar'
        || ($created['summary'] ?? '') !== 'Frühdienst'
        || ($created['description'] ?? '') !== 'Translated provider description.') {
        throw new RuntimeException('Google-Ereignis ist nicht stabil oder als App-Eigentum markiert.');
    }
    foreach ($client->calls as [, $url, $options]) {
        if (str_contains($url, 'refresh') || ($options['headers']['Authorization'] ?? '') !== 'Bearer access-token') throw new RuntimeException('Google-Token wird nicht ausschließlich im Autorisierungsheader verwendet.');
    }

    echo "GoogleCalendarClientTest: OK\n";
}
