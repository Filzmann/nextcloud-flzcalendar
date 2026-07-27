<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(): string; }
    interface IUserSession { public function getUser(): ?IUser; }
    interface IGroupManager { public function isAdmin(string $uid): bool; }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }
    class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; }
}
namespace OCP\AppFramework\Http {
    class JSONResponse { public function __construct(private array $data = [], private int $status = 200) {} public function getData(): array { return $this->data; } public function getStatus(): int { return $this->status; } }
}
namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionException extends \RuntimeException {
        public function userMessage(string $provider): string { return $provider === 'kopano' ? 'Der Kopano-Betreiber erlaubt an dieser Adresse keine CalDAV-Verbindung (HTTP 405). Bitte wende dich an dessen Administration.' : $this->getMessage(); }
    }
}
namespace OCA\AdCalendar\Service {
    final class ExternalCalendarService {
        public array $calls = [];
        public bool $blocked = false;
        public function testCalDavConnection(string $provider, string $serverUrl, string $username, string $password): int {
            $this->calls[] = [$provider, $serverUrl, $username, $password];
            if ($this->blocked) throw new \OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionException('Blockiert.', 405);
            return 207;
        }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Controller/ExternalCalendarAdminController.php';

    use OCA\AdCalendar\Controller\ExternalCalendarAdminController;
    use OCA\AdCalendar\Service\ExternalCalendarService;
    use OCP\IGroupManager;
    use OCP\IRequest;
    use OCP\IUser;
    use OCP\IUserSession;
    use Psr\Log\LoggerInterface;

    $user = new class implements IUser { public function getUID(): string { return 'admin-a'; } };
    $session = new class($user) implements IUserSession { public function __construct(public ?IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $groups = new class implements IGroupManager { public bool $admin = false; public function isAdmin(string $uid): bool { return $this->admin; } };
    $calendars = new ExternalCalendarService();
    $logger = new class implements LoggerInterface { public array $entries = []; public function error(string|\Stringable $message, array $context = []): void { $this->entries[] = [(string)$message, $context]; } };
    $controller = new ExternalCalendarAdminController(new class implements IRequest {}, $session, $groups, $calendars, $logger);

    if ($controller->testCalDav('https://calendar.example.test', 'person-a', 'secret')->getStatus() !== 403 || $calendars->calls !== []) {
        throw new RuntimeException('Nicht-Admins können externe CalDAV-Zugangsdaten testen.');
    }
    $groups->admin = true;
    $result = $controller->testCalDav('https://calendar.example.test', 'person-a', 'secret');
    if ($result->getStatus() !== 200 || ($result->getData()['message'] ?? '') !== 'Kopano-CalDAV-Verbindung erfolgreich geprüft (HTTP 207).' || $calendars->calls !== [['kopano', 'https://calendar.example.test', 'person-a', 'secret']] || str_contains(json_encode($result->getData()), 'secret')) {
        throw new RuntimeException('Administrativer Kopano-Test ist fehlerhaft oder gibt Zugangsdaten zurück.');
    }
    $calendars->blocked = true;
    $blocked = $controller->testCalDav('https://calendar.example.test', 'person-a', 'secret');
    if ($blocked->getStatus() !== 400 || ($blocked->getData()['error'] ?? '') !== 'Der Kopano-Betreiber erlaubt an dieser Adresse keine CalDAV-Verbindung (HTTP 405). Bitte wende dich an dessen Administration.') {
        throw new RuntimeException('Administrativer Kopano-Test erklärt HTTP 405 nicht sicher.');
    }
    if (($logger->entries[0][1] ?? null) !== ['provider' => 'kopano', 'status' => 405]) {
        throw new RuntimeException('Administrativer Verbindungstest protokolliert sensible Verbindungsdetails.');
    }

    echo "ExternalCalendarAdminControllerExecutionTest: OK\n";
}
