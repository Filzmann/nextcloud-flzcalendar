<?php

declare(strict_types=1);

namespace OCP { interface IL10N { public function t(string $text, array $parameters = []): string; } }
namespace OCP\Http\Client {
    interface IResponse { public function getBody(); public function getStatusCode(): int; }
    interface IClient { public function request(string $method, string $uri, array $options = []): IResponse; public function getResponseFromThrowable(\Throwable $error): IResponse; }
    interface IClientService { public function newClient(): IClient; }
}
namespace OCA\FlzCalendar\CalendarSync {
    final class GoogleOAuthService { public function accessToken(string $uid, array $connection): string { return 'access-token'; } }
    final class ExternalCalendarConnectionStore { public function save(string $uid, string $provider, array $connection): void {} }
}
namespace OCA\FlzCalendar\Service {
    final class CalendarTargetConfig { public function calendarName(): string { return 'Team & Dienst'; } }
}

namespace {

    use OCA\FlzCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\FlzCalendar\CalendarSync\GoogleCalendarClient;
    use OCA\FlzCalendar\CalendarSync\GoogleOAuthService;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCP\Http\Client\IClient;
    use OCP\Http\Client\IClientService;
    use OCP\Http\Client\IResponse;
    use OCP\IL10N;

    $l10n = new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } };

    $response = static fn(int $status, array $body = []): IResponse => new class($status, $body) implements IResponse {
        public function __construct(private int $status, private array $body) {}
        public function getBody(): string { return $this->body === [] ? '' : json_encode($this->body, JSON_THROW_ON_ERROR); }
        public function getStatusCode(): int { return $this->status; }
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
    $connection = ['calendarId' => 'calendar@example.test', 'calendarName' => 'Filzmann Dienste', 'refreshToken' => 'refresh'];

    $client = $clientFor([$response(200, ['id' => 'calendar@example.test', 'summary' => 'Filzmann Dienste']), $response(200, ['id' => 'calendar@example.test', 'summary' => 'Team & Dienst'])]);
    $google = new GoogleCalendarClient($serviceFor($client), new GoogleOAuthService(), new ExternalCalendarConnectionStore(), new CalendarTargetConfig(), $l10n);
    $renamed = $google->renameCalendar('person-a', $connection);
    $body = json_decode((string)($client->calls[1][2]['body'] ?? ''), true);
    if (($renamed['calendarName'] ?? '') !== 'Team & Dienst' || ($renamed['calendarId'] ?? '') !== 'calendar@example.test'
        || array_column($client->calls, 0) !== ['GET', 'PATCH'] || ($body['summary'] ?? '') !== 'Team & Dienst') {
        throw new RuntimeException('Vorhandener Google-Kalender wird nicht bei stabiler Provider-ID umbenannt.');
    }

    $stableClient = $clientFor([$response(200, ['id' => 'calendar@example.test', 'summary' => 'Team & Dienst'])]);
    $stable = new GoogleCalendarClient($serviceFor($stableClient), new GoogleOAuthService(), new ExternalCalendarConnectionStore(), new CalendarTargetConfig(), $l10n);
    $stable->renameCalendar('person-a', $renamed);
    if (array_column($stableClient->calls, 0) !== ['GET']) throw new RuntimeException('Wiederholte Google-Umbenennung ist nicht idempotent.');

    $foreignClient = $clientFor([$response(200, ['id' => 'calendar@example.test', 'summary' => 'Privat'])]);
    $foreign = new GoogleCalendarClient($serviceFor($foreignClient), new GoogleOAuthService(), new ExternalCalendarConnectionStore(), new CalendarTargetConfig(), $l10n);
    try {
        $foreign->renameCalendar('person-a', $connection);
        throw new RuntimeException('Fremder Google-Kalender wurde umbenannt.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Fremder Google-Kalender wurde umbenannt.') throw $error;
    }
    if (array_column($foreignClient->calls, 0) !== ['GET']) throw new RuntimeException('Abgewiesener Google-Kalender wurde verändert.');

    echo "GoogleCalendarRenameTest: OK\n";
}
