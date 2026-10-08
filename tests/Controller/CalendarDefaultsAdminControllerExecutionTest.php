<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(): string; }
    interface IUserSession { public function getUser(): ?IUser; }
    interface IGroupManager { public function isAdmin(string $uid): bool; }
    interface IL10N { public function t(string $text, array $parameters = []): string; }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }
    class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; }
}
namespace OCP\AppFramework\Http {
    class JSONResponse { public function __construct(private array $data = [], private int $status = 200) {} public function getData(): array { return $this->data; } public function getStatus(): int { return $this->status; } }
}
namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }
namespace OCA\FlzCalendar\Service {
    final class CalendarTargetConfig {
        public array $calls = [];
        public string $failure = '';
        public function save(string $kopanoUrl, string $calendarName): array {
            if ($this->failure === 'invalid') throw new \InvalidArgumentException('Interner Validierungstext.');
            if ($this->failure === 'unexpected') throw new \RuntimeException('Vertraulicher interner Fehler.');
            $this->calls[] = [$kopanoUrl, $calendarName];
            return ['kopanoUrl' => 'https://calendar.example.test/', 'calendarName' => $calendarName];
        }
    }
}

namespace {

    use OCA\FlzCalendar\Controller\CalendarDefaultsAdminController;
    use OCA\FlzCalendar\Http\LocalizedErrorResponseFactory;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCP\IGroupManager;
    use OCP\IL10N;
    use OCP\IRequest;
    use OCP\IUser;
    use OCP\IUserSession;
    use Psr\Log\LoggerInterface;

    $user = new class implements IUser { public function getUID(): string { return 'admin-a'; } };
    $session = new class($user) implements IUserSession { public function __construct(public ?IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $groups = new class implements IGroupManager { public bool $admin = false; public function isAdmin(string $uid): bool { return $this->admin; } };
    $targets = new CalendarTargetConfig();
    $logger = new class implements LoggerInterface { public array $errors = []; public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; } };
    $l10n = new class implements IL10N {
        public array $calls = [];
        public function t(string $text, array $parameters = []): string {
            $this->calls[] = [$text, $parameters];
            return "translated:{$text}";
        }
    };
    $controller = new CalendarDefaultsAdminController(new class implements IRequest {}, $session, $groups, $targets, $logger, new LocalizedErrorResponseFactory($l10n));

    $denied = $controller->save('https://calendar.example.test', 'Teamdienste');
    if ($denied->getStatus() !== 403
        || $denied->getData() !== ['code' => 'forbidden', 'error' => 'translated:You are not allowed to perform this action.']
        || $targets->calls !== []) {
        throw new RuntimeException('Nicht-Admins können Kalenderdefaults verändern.');
    }
    $session->user = null;
    if ($controller->save('https://calendar.example.test', 'Teamdienste')->getStatus() !== 403 || $targets->calls !== []) {
        throw new RuntimeException('Anonyme Requests können Kalenderdefaults verändern.');
    }
    $session->user = $user;
    $groups->admin = true;
    $saved = $controller->save('https://calendar.example.test', 'Teamdienste');
    if ($saved->getStatus() !== 200
        || $saved->getData() !== ['calendarDefaults' => ['kopanoUrl' => 'https://calendar.example.test/', 'calendarName' => 'Teamdienste']]
        || $targets->calls !== [['https://calendar.example.test', 'Teamdienste']]) {
        throw new RuntimeException('Admin kann validierte Kalenderdefaults nicht speichern.');
    }
    $targets->failure = 'invalid';
    $invalid = $controller->save('http://unsicher.example.test', '');
    if ($invalid->getStatus() !== 400
        || $invalid->getData() !== ['code' => 'invalid_calendar_defaults', 'error' => 'translated:The calendar defaults are invalid.']
        || count($targets->calls) !== 1
        || str_contains(json_encode($invalid->getData()), 'Interner Validierungstext')) {
        throw new RuntimeException('Validierungsfehler wird nicht ohne Zustandsänderung behandelt.');
    }
    $targets->failure = 'unexpected';
    $failed = $controller->save('https://calendar.example.test', 'Teamdienste');
    if ($failed->getStatus() !== 400
        || $failed->getData() !== ['code' => 'calendar_defaults_save_failed', 'error' => 'translated:The calendar defaults could not be saved.']
        || $logger->errors === []
        || str_contains(json_encode($failed->getData()), 'Vertraulicher interner Fehler')) {
        throw new RuntimeException('Unerwarteter Speicherfehler wird nicht stabil codiert, lokalisiert und sicher protokolliert.');
    }

    echo "CalendarDefaultsAdminControllerExecutionTest: OK\n";
}
