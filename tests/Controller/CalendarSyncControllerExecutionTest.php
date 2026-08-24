<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IUser { public function getUID(): string; }
    interface IL10N { public function t(string $text, array $parameters = []): string; }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }
    class Http {
        public const STATUS_BAD_REQUEST = 400;
        public const STATUS_FORBIDDEN = 403;
        public const STATUS_NOT_FOUND = 404;
        public const STATUS_CONFLICT = 409;
    }
}
namespace OCP\AppFramework\Http {
    class JSONResponse {
        public function __construct(private array $data = [], private int $status = 200) {}
        public function getData(): array { return $this->data; }
        public function getStatus(): int { return $this->status; }
    }
}
namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\LocalBase\Calendar { class HolidayCalendarService {} }
namespace OCA\AdCalendar\Service {
    class CalendarAccessService {
        public ?\OCP\IUser $user = null;
        public function currentUser(): ?\OCP\IUser { return $this->user; }
    }
    class CalendarService {}
    class CalendarSettingsService {}
    class CalendarPreferenceService {}
    class RecurringAppointmentService {}
    class ShiftCalendarSyncService {
        public bool $fail = false;
        public array $configured = [];
        public function configure(string $uid, bool $enabled): array {
            if ($this->fail) throw new \RuntimeException('intern');
            $this->configured[] = [$uid, $enabled];
            return ['enabled' => $enabled, 'calendarName' => 'AD Dienste'];
        }
    }
}

namespace {

    use OCA\AdCalendar\Controller\ApiController;
    use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
    use OCA\AdCalendar\Service\CalendarAccessService;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\AdCalendar\Service\CalendarService;
    use OCA\AdCalendar\Service\CalendarSettingsService;
    use OCA\AdCalendar\Service\RecurringAppointmentService;
    use OCA\AdCalendar\Service\ShiftCalendarSyncService;
    use OCA\LocalBase\Calendar\HolidayCalendarService;
    use OCP\IRequest;
    use OCP\IL10N;
    use OCP\IUser;
    use Psr\Log\LoggerInterface;

    $request = new class implements IRequest {};
    $access = new CalendarAccessService();
    $sync = new ShiftCalendarSyncService();
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    };
    $errors = new LocalizedErrorResponseFactory(new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } });
    $controller = new ApiController($request, $access, new CalendarService(), new CalendarSettingsService(), new CalendarPreferenceService(), new RecurringAppointmentService(), $sync, new HolidayCalendarService(), $logger, $errors);

    if ($controller->saveCalendarSync(true)->getStatus() !== 403) throw new RuntimeException('Nicht angemeldete Person kann die Synchronisation aktivieren.');
    $access->user = new class implements IUser { public function getUID(): string { return 'sync-person'; } };
    $response = $controller->saveCalendarSync(true);
    if ($response->getStatus() !== 200 || ($response->getData()['calendarSync']['enabled'] ?? null) !== true || $sync->configured !== [['sync-person', true]]) {
        throw new RuntimeException('Persönliche Kalenderaktivierung wird nicht dem angemeldeten Konto zugeordnet.');
    }
    $sync->fail = true;
    $failure = $controller->saveCalendarSync(false);
    if ($failure->getStatus() !== 400 || $logger->errors === [] || str_contains(json_encode($failure->getData()), 'intern')) {
        throw new RuntimeException('DAV-Fehler wird nicht sicher behandelt und protokolliert.');
    }

    echo "CalendarSyncControllerExecutionTest: OK\n";
}
