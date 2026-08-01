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
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\AdCalendar\CalendarSync {
    final class GoogleOAuthService {
        public array $calls = [];
        public bool $fail = false;
        public function saveConfiguration(string $clientId, string $clientSecret): array { if ($this->fail) throw new \InvalidArgumentException('Ungültig.'); $this->calls[] = ['save', $clientId, $clientSecret]; return ['configured' => true, 'clientId' => $clientId, 'secretConfigured' => true, 'redirectUri' => 'https://cloud.example.test/callback']; }
        public function removeConfiguration(): array { $this->calls[] = ['remove']; return ['configured' => false, 'clientId' => '', 'secretConfigured' => false, 'redirectUri' => 'https://cloud.example.test/callback']; }
    }
}

namespace {
    require_once __DIR__ . '/../../lib/Http/LocalizedErrorResponseFactory.php';
    require_once __DIR__ . '/../../lib/Controller/GoogleOAuthAdminController.php';

    use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
    use OCA\AdCalendar\Controller\GoogleOAuthAdminController;
    use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
    use OCP\IGroupManager;
    use OCP\IL10N;
    use OCP\IRequest;
    use OCP\IUser;
    use OCP\IUserSession;
    use Psr\Log\LoggerInterface;

    $user = new class implements IUser { public function getUID(): string { return 'admin-a'; } };
    $session = new class($user) implements IUserSession { public function __construct(public ?IUser $user) {} public function getUser(): ?IUser { return $this->user; } };
    $groups = new class implements IGroupManager { public bool $admin = false; public function isAdmin(string $uid): bool { return $this->admin; } };
    $oauth = new GoogleOAuthService();
    $logger = new class implements LoggerInterface { public array $errors = []; public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; } };
    $errors = new LocalizedErrorResponseFactory(new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } });
    $controller = new GoogleOAuthAdminController(new class implements IRequest {}, $session, $groups, $oauth, $logger, $errors);

    if ($controller->save('client-id', 'secret')->getStatus() !== 403 || $controller->remove()->getStatus() !== 403 || $oauth->calls !== []) {
        throw new RuntimeException('Nicht-Admins können die Google-OAuth-Konfiguration verändern.');
    }
    $groups->admin = true;
    $saved = $controller->save('client-id', 'secret');
    if ($saved->getStatus() !== 200 || $oauth->calls !== [['save', 'client-id', 'secret']] || str_contains(json_encode($saved->getData()), '"secret"')) {
        throw new RuntimeException('Adminspeicherung ist fehlerhaft oder gibt das Secret zurück.');
    }
    $oauth->fail = true;
    $invalid = $controller->save('', '');
    if ($invalid->getStatus() !== 400 || ($invalid->getData()['code'] ?? '') !== 'invalid_google_oauth_configuration') throw new RuntimeException('Validierungsfehler wird nicht sicher und stabil codiert behandelt.');
    $oauth->fail = false;
    if ($controller->remove()->getStatus() !== 200 || $oauth->calls[array_key_last($oauth->calls)] !== ['remove']) throw new RuntimeException('Admin kann Google-Konfiguration nicht entfernen.');

    echo "GoogleOAuthAdminControllerExecutionTest: OK\n";
}
