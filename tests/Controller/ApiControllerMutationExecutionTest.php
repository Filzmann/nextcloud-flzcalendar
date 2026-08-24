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
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCP\IUser;

    final class CalendarAccessService {
        public bool $view = false;
        public array $manageable = [];
        public array $visible = [
            ['uid' => 'person-a', 'roles' => ['role-a'], 'areas' => ['area-a']],
            ['uid' => 'person-b', 'roles' => ['role-a', 'role-b'], 'areas' => ['area-b']],
        ];
        public ?IUser $user = null;
        public function canView(): bool { return $this->view; }
        public function canManage(string $uid): bool { return in_array($uid, $this->manageable, true); }
        public function visibleEmployees(): array { return $this->visible; }
        public function currentUser(): ?IUser { return $this->user; }
        public function currentProfile(): array { return ['roles' => ['role-a'], 'areas' => ['area-a']]; }
    }

    final class CalendarService {
        public ?CalendarEntry $entry = null;
        public ?\Throwable $existingFailure = null;
        public ?\Throwable $saveFailure = null;
        public ?\Throwable $deleteFailure = null;
        public array $preview = ['children' => []];
        public array $saved = [];
        public array $deleted = [];
        public function existing(int $id): CalendarEntry {
            if ($this->existingFailure !== null) throw $this->existingFailure;
            if ($this->entry === null) throw new \RuntimeException('missing');
            return $this->entry;
        }
        public function save(array $payload, ?int $id, string $actor): int {
            if ($this->saveFailure !== null) throw $this->saveFailure;
            $this->saved[] = [$payload, $id, $actor];
            return $id ?? 71;
        }
        public function deletionPreview(int $id): array { return $this->preview; }
        public function delete(int $id, string $childMode): void {
            if ($this->deleteFailure !== null) throw $this->deleteFailure;
            $this->deleted[] = [$id, $childMode];
        }
    }

    final class CalendarSettingsService {}

    final class CalendarPreferenceService {
        public array $savedFilters = [];
        public array $savedDefaults = [];
        public function filterDefault(string $uid, array $uids, array $roles, array $areas): ?array {
            return ['uid' => $uid, 'uids' => $uids, 'roles' => $roles, 'areas' => $areas];
        }
        public function shiftDefaults(string $uid): array { return ['uid' => $uid]; }
        public function saveFilterDefault(string $uid, array $filters, array $uids, array $roles, array $areas): array {
            $this->savedFilters[] = [$uid, $filters, $uids, $roles, $areas];
            return $filters;
        }
        public function saveShiftDefaults(string $uid, array $defaults): array {
            $this->savedDefaults[] = [$uid, $defaults];
            return $defaults;
        }
    }

    final class RecurringAppointmentService {
        public array $series = [];
        public array $calls = [];
        public ?\Throwable $createFailure = null;
        public ?\Throwable $updateFailure = null;
        public ?\Throwable $deleteFailure = null;
        public function create(array $payload, array $recurrence, string $actor): array {
            if ($this->createFailure !== null) throw $this->createFailure;
            $this->calls[] = ['create', $payload, $recurrence, $actor];
            return [81, 82];
        }
        public function seriesEntries(string $seriesUid): array { $this->calls[] = ['entries', $seriesUid]; return $this->series; }
        public function updateSeries(CalendarEntry $entry, array $payload, string $actor): array {
            if ($this->updateFailure !== null) throw $this->updateFailure;
            $this->calls[] = ['update', $payload, $actor];
            return [91, 92];
        }
        public function deleteSeries(string $seriesUid): void {
            if ($this->deleteFailure !== null) throw $this->deleteFailure;
            $this->calls[] = ['delete', $seriesUid];
        }
    }

    final class ShiftCalendarSyncService {
        public function status(string $uid): array { return ['enabled' => false, 'uid' => $uid]; }
        public function configure(string $uid, bool $enabled): array { return ['enabled' => $enabled, 'uid' => $uid]; }
    }
}

namespace {

    use OCA\AdCalendar\Controller\ApiController;
    use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Service\CalendarAccessService;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\AdCalendar\Service\CalendarService;
    use OCA\AdCalendar\Service\CalendarSettingsService;
    use OCA\AdCalendar\Service\RecurringAppointmentService;
    use OCA\AdCalendar\Service\ShiftCalendarSyncService;
    use OCA\LocalBase\Calendar\HolidayCalendarService;
    use OCP\IL10N;
    use OCP\IRequest;
    use OCP\IUser;
    use Psr\Log\LoggerInterface;

    $assertResponse = static function (object $response, int $status, ?string $code = null): void {
        if ($response->getStatus() !== $status || ($code !== null && ($response->getData()['code'] ?? '') !== $code)) {
            throw new RuntimeException("Unerwartete API-Antwort: {$response->getStatus()} " . json_encode($response->getData()));
        }
    };
    $entry = static function (array $changes = []): CalendarEntry {
        return CalendarEntry::get(array_merge([
            'id' => 41,
            'employeeUid' => 'person-a',
            'start' => '2026-07-06T08:00:00+02:00',
            'end' => '2026-07-06T16:00:00+02:00',
            'type' => 'shift',
            'title' => '',
        ], $changes));
    };

    $access = new CalendarAccessService();
    $calendar = new CalendarService();
    $preferences = new CalendarPreferenceService();
    $recurrences = new RecurringAppointmentService();
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; }
    };
    $errors = new LocalizedErrorResponseFactory(new class implements IL10N {
        public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); }
    });
    $controller = new ApiController(
        new class implements IRequest {}, $access, $calendar, new CalendarSettingsService(), $preferences,
        $recurrences, new ShiftCalendarSyncService(), new HolidayCalendarService(), $logger, $errors,
    );

    $createArguments = ['person-a', '2026-07-06T08:00:00+02:00', '2026-07-06T16:00:00+02:00', 'shift'];
    $assertResponse($controller->create(...$createArguments), 403, 'forbidden');
    if ($calendar->saved !== []) throw new RuntimeException('Nicht berechtigtes Anlegen erreicht den Kalenderdienst.');

    $access->manageable = ['person-a'];
    $access->user = new class implements IUser { public function getUID(): string { return 'planner'; } };
    $created = $controller->create(...$createArguments);
    if ($created->getData() !== ['id' => 71] || $calendar->saved[0][2] !== 'planner') {
        throw new RuntimeException('Einzelner Kalendereintrag wird nicht mit Akteur gespeichert.');
    }
    foreach ([new InvalidArgumentException('invalid'), new RuntimeException('storage')] as $failure) {
        $calendar->saveFailure = $failure;
        $assertResponse($controller->create(...$createArguments), 400, 'invalid_calendar_entry');
    }
    $calendar->saveFailure = null;

    $access->manageable = [];
    $assertResponse($controller->create(...array_merge($createArguments, ['', 'weekly'])), 403, 'forbidden');
    $access->manageable = ['person-a'];
    $recurring = $controller->create(...array_merge($createArguments, ['Serie', 'weekly', 2, '2026-08-31', ['MO'], 'Europe/Berlin']));
    if ($recurring->getData() !== ['id' => 81, 'ids' => [81, 82], 'seriesCount' => 2]
        || $recurrences->calls[0][3] !== 'planner' || $recurrences->calls[0][2]['interval'] !== 2) {
        throw new RuntimeException('Terminserie wird nicht vollständig an den Seriendienst übergeben.');
    }
    foreach ([
        [new InvalidArgumentException('invalid'), 'invalid_recurrence'],
        [new RuntimeException('storage'), 'recurrence_save_failed'],
    ] as [$failure, $code]) {
        $recurrences->createFailure = $failure;
        $assertResponse($controller->create(...array_merge($createArguments, ['Serie', 'weekly'])), 400, $code);
    }
    $recurrences->createFailure = null;

    $calendar->entry = $entry();
    $access->manageable = [];
    $assertResponse($controller->update(41, ...$createArguments), 403, 'forbidden');
    $access->manageable = ['person-a', 'person-b'];
    $savedBeforeOwnerChange = count($calendar->saved);
    $assertResponse($controller->update(41, 'person-b', '2026-07-06T08:00:00+02:00', '2026-07-06T16:00:00+02:00', 'shift'), 409, 'entry_owner_immutable');
    if (count($calendar->saved) !== $savedBeforeOwnerChange) {
        throw new RuntimeException('Manipulierte Mitarbeiter*innen-Zuordnung erreicht trotz unveränderlicher Eigentümerschaft den Kalenderdienst.');
    }
    $access->manageable = ['person-a'];
    $meeting = $entry(['type' => 'appointment', 'title' => 'Meeting', 'meetingUid' => 'meeting-a']);
    $calendar->entry = $meeting;
    $assertResponse($controller->update(41, 'person-a', '2026-07-06T10:00:00+02:00', '2026-07-06T11:00:00+02:00', 'appointment', 'Meeting'), 409, 'meeting_managed_together');

    $calendar->entry = $entry();
    $assertResponse($controller->update(41, ...array_merge($createArguments, ['', 'series'])), 400, 'entry_not_recurring');
    $seriesEntry = $entry([
        'type' => 'appointment', 'title' => 'Serie', 'seriesUid' => 'series-a', 'seriesTimezone' => 'Europe/Berlin',
    ]);
    $calendar->entry = $seriesEntry;
    $recurrences->series = [$seriesEntry, $entry(['id' => 42, 'employeeUid' => 'person-b'])];
    $assertResponse($controller->update(41, 'person-a', '2026-07-06T10:00:00+02:00', '2026-07-06T11:00:00+02:00', 'appointment', 'Serie', 'series'), 403, 'forbidden');
    $access->manageable = ['person-a', 'person-b'];
    $updatedSeries = $controller->update(41, 'person-a', '2026-07-06T10:00:00+02:00', '2026-07-06T11:00:00+02:00', 'appointment', 'Serie', 'series');
    if ($updatedSeries->getData() !== ['id' => 41, 'ids' => [91, 92], 'seriesCount' => 2]) {
        throw new RuntimeException('Berechtigte Terminserie wird nicht gemeinsam geändert.');
    }
    foreach ([
        [new InvalidArgumentException('invalid'), 'invalid_recurrence'],
        [new RuntimeException('storage'), 'recurrence_save_failed'],
    ] as [$failure, $code]) {
        $recurrences->updateFailure = $failure;
        $assertResponse($controller->update(41, 'person-a', '2026-07-06T10:00:00+02:00', '2026-07-06T11:00:00+02:00', 'appointment', 'Serie', 'series'), 400, $code);
    }
    $recurrences->updateFailure = null;
    $assertResponse($controller->update(41, 'person-a', '2026-07-06T10:00:00+02:00', '2026-07-06T11:00:00+02:00', 'appointment', 'Serie', 'invalid'), 400, 'invalid_series_scope');
    $calendar->entry = $entry();
    if ($controller->update(41, ...$createArguments)->getData() !== ['id' => 41]) {
        throw new RuntimeException('Einzelnes Update wird nicht an den Kalenderdienst übergeben.');
    }

    $calendar->existingFailure = new RuntimeException('missing');
    $assertResponse($controller->delete(404), 404, 'entry_not_found');
    $calendar->existingFailure = null;
    $calendar->entry = $entry();
    $access->manageable = [];
    $assertResponse($controller->delete(41), 403, 'forbidden');
    $access->manageable = ['person-a'];
    $calendar->entry = $meeting;
    $assertResponse($controller->delete(41), 409, 'meeting_deleted_together');
    $calendar->entry = $entry();
    $assertResponse($controller->delete(41, '', 'series'), 400, 'entry_not_recurring');

    $calendar->entry = $seriesEntry;
    $recurrences->series = [$seriesEntry, $entry(['id' => 42, 'employeeUid' => 'person-b'])];
    $assertResponse($controller->delete(41, '', 'series'), 403, 'forbidden');
    $access->manageable = ['person-a', 'person-b'];
    $deletedSeries = $controller->delete(41, '', 'series');
    if ($deletedSeries->getData() !== ['deleted' => true, 'seriesScope' => 'series']) {
        throw new RuntimeException('Berechtigte Terminserie wird nicht gemeinsam gelöscht.');
    }
    $recurrences->deleteFailure = new RuntimeException('storage');
    $assertResponse($controller->delete(41, '', 'series'), 400, 'recurrence_delete_failed');
    $recurrences->deleteFailure = null;
    $assertResponse($controller->delete(41, '', 'invalid'), 400, 'invalid_series_scope');

    $calendar->entry = $entry();
    $calendar->preview = ['children' => [['id' => 51]]];
    $confirmation = $controller->delete(41);
    if ($confirmation->getStatus() !== 409 || $confirmation->getData() !== ['confirmationRequired' => true, 'children' => [['id' => 51]]]) {
        throw new RuntimeException('Dienst mit Kindtermin verlangt keine explizite Löschentscheidung.');
    }
    $deleted = $controller->delete(41, 'detach');
    if ($deleted->getData() !== ['deleted' => true, 'childMode' => 'detach'] || $calendar->deleted !== [[41, 'detach']]) {
        throw new RuntimeException('Bestätigte Löschentscheidung wird nicht exakt weitergereicht.');
    }
    $calendar->deleteFailure = new RuntimeException('storage');
    $assertResponse($controller->delete(41, 'delete'), 400, 'entry_delete_failed');

    $access->view = false;
    $access->user = null;
    $assertResponse($controller->preferences(), 403, 'forbidden');
    $assertResponse($controller->savePreferences([]), 403, 'forbidden');
    $assertResponse($controller->saveShiftDefaults([]), 403, 'forbidden');
    $access->view = true;
    $access->user = new class implements IUser { public function getUID(): string { return 'planner'; } };
    $preferenceResponse = $controller->preferences()->getData();
    if (($preferenceResponse['filters']['roles'] ?? []) !== ['role-a', 'role-b']
        || ($preferenceResponse['calendarSync']['uid'] ?? '') !== 'planner') {
        throw new RuntimeException('Persönliche Einstellungen berücksichtigen nicht den sichtbaren Organisationskontext.');
    }
    $filters = ['period' => 'month', 'uids' => ['person-a']];
    if ($controller->savePreferences($filters)->getData() !== ['filters' => $filters]
        || $preferences->savedFilters[0][2] !== ['person-a', 'person-b']
        || $preferences->savedFilters[0][3] !== ['role-a', 'role-b']
        || $preferences->savedFilters[0][4] !== ['area-a', 'area-b']) {
        throw new RuntimeException('Filtervorgaben werden nicht gegen erlaubte Optionen gespeichert.');
    }
    $defaults = ['monday' => ['enabled' => true, 'start' => '08:00', 'end' => '16:30']];
    if ($controller->saveShiftDefaults($defaults)->getData() !== ['shiftDefaults' => $defaults]
        || $preferences->savedDefaults !== [['planner', $defaults]]) {
        throw new RuntimeException('Standarddienste werden nicht dem angemeldeten Konto zugeordnet.');
    }

    if (count($logger->errors) !== 3 || array_filter($logger->errors, static fn(array $error): bool => !isset($error[1]['exception'])) !== []) {
        throw new RuntimeException('Unerwartete Serienfehler werden nicht sicher und strukturiert protokolliert.');
    }

    echo "ApiControllerMutationExecutionTest: OK\n";
}
