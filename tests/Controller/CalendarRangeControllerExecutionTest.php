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
namespace OCA\LocalBase\Calendar {
    class HolidayCalendar { public function __construct(private int $year) {} public function toArray(): array { return ['year' => $this->year, 'publicHolidays' => []]; } }
    class HolidayCalendarService {
        public array $calls = [];
        public function forYear(int $year): HolidayCalendar { $this->calls[] = $year; return new HolidayCalendar($year); }
    }
}
namespace OCA\AdCalendar\Service {
    class CalendarAccessService {
        public bool $view = false;
        public function canView(): bool { return $this->view; }
        public function visibleEmployees(): array { return [['uid' => 'person-a', 'roles' => [], 'areas' => []]]; }
        public function currentUser(): ?\OCP\IUser { return new class implements \OCP\IUser { public function getUID(): string { return 'viewer'; } }; }
        public function currentProfile(): array { return ['roles' => [], 'areas' => []]; }
    }
    class CalendarService {
        public array $calls = [];
        public function range(\DateTimeImmutable $start, \DateTimeImmutable $end, array $employees): array {
            $this->calls[] = [$start->format('Y-m-d'), $end->format('Y-m-d')];
            return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'employees' => $employees, 'entries' => [], 'absences' => []];
        }
    }
    class CalendarSettingsService { public function organization(): array { return []; } }
    class CalendarPreferenceService {
        public function filterDefault(string $uid, array $employees, array $roles, array $areas): ?array { return ['period' => 'month']; }
        public function shiftDefaults(string $uid): array { return []; }
    }
    class RecurringAppointmentService {}
    class ShiftCalendarSyncService { public function status(string $uid): array { return ['enabled' => true]; } }
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
    use Psr\Log\LoggerInterface;

    $access = new CalendarAccessService();
    $calendar = new CalendarService();
    $holidays = new HolidayCalendarService();
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = (string)$message; }
    };
    $errors = new LocalizedErrorResponseFactory(new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } });
    $controller = new ApiController(
        new class implements IRequest {}, $access, $calendar, new CalendarSettingsService(),
        new CalendarPreferenceService(), new RecurringAppointmentService(), new ShiftCalendarSyncService(), $holidays, $logger, $errors,
    );

    if ($controller->range('2026-06-29', '2026-08-03')->getStatus() !== 403 || $calendar->calls !== []) {
        throw new RuntimeException('Nicht berechtigte Monatsabfrage erreicht den Kalenderdienst.');
    }
    $access->view = true;
    $response = $controller->range('2026-06-29', '2026-08-03');
    if ($response->getStatus() !== 200 || $calendar->calls !== [['2026-06-29', '2026-08-03']] || $holidays->calls !== [2026] || ($response->getData()['holidayCalendars'][0]['year'] ?? null) !== 2026 || ($response->getData()['defaultFilters']['period'] ?? '') !== 'month') {
        throw new RuntimeException('Berechtigte Monatsabfrage liefert nicht den vollständigen persönlichen Ansichtskontext.');
    }
    $controller->range('2026-12-28', '2027-01-11');
    if ($holidays->calls !== [2026, 2026, 2027]) throw new RuntimeException('Ein Kalenderbereich über den Jahreswechsel lädt nicht beide gemeinsamen Jahresstände.');
    if ($controller->range('kein-datum', '2026-08-03')->getStatus() !== 400 || $logger->errors === []) {
        throw new RuntimeException('Ungültiger Monatsbereich wird nicht sicher behandelt und protokolliert.');
    }

    echo "CalendarRangeControllerExecutionTest: OK\n";
}
