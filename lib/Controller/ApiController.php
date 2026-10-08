<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Controller;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\FlzCalendar\AppInfo\Application;
use OCA\FlzCalendar\Http\LocalizedErrorResponseFactory;
use OCA\FlzCalendar\Service\CalendarAccessService;
use OCA\FlzCalendar\Service\CalendarService;
use OCA\FlzCalendar\Service\CalendarSettingsService;
use OCA\FlzCalendar\Service\CalendarPreferenceService;
use OCA\FlzCalendar\Service\RecurringAppointmentService;
use OCA\FlzCalendar\Service\ShiftCalendarSyncService;
use OCA\LocalBase\Calendar\HolidayCalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class ApiController extends Controller {
    public function __construct(
        IRequest $request,
        private CalendarAccessService $access,
        private CalendarService $calendar,
        private CalendarSettingsService $settingsService,
        private CalendarPreferenceService $preferences,
        private RecurringAppointmentService $recurrences,
        private ShiftCalendarSyncService $shiftSync,
        private HolidayCalendarService $holidays,
        private LoggerInterface $logger,
        private LocalizedErrorResponseFactory $errors,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function week(string $start): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $date = new DateTimeImmutable($start);
            $employees = $this->access->visibleEmployees();
            $response = $this->withViewContext($this->calendar->week($date, $employees), $employees);
            return new JSONResponse($response);
        } catch (\Throwable $error) {
            $this->logger->error('Wochenansicht konnte nicht aufgebaut werden.', ['exception' => $error]);
            return $this->errors->create('week_load_failed', 'The weekly schedule could not be loaded.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function range(string $start, string $end): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $rangeStart = $this->date($start);
            $rangeEnd = $this->date($end);
            $employees = $this->access->visibleEmployees();
            $response = $this->withViewContext($this->calendar->range($rangeStart, $rangeEnd, $employees), $employees);
            return new JSONResponse($response);
        } catch (\Throwable $error) {
            $this->logger->error('Kalenderbereich konnte nicht aufgebaut werden.', ['exception' => $error]);
            return $this->errors->create('range_load_failed', 'The monthly schedule could not be loaded.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function create(
        string $employeeUid,
        string $start,
        string $end,
        string $type,
        string $title = '',
        string $recurrenceFrequency = '',
        int $recurrenceInterval = 1,
        string $recurrenceUntil = '',
        array $recurrenceWeekdays = [],
        string $recurrenceTimezone = '',
    ): JSONResponse {
        $payload = compact('employeeUid', 'start', 'end', 'type', 'title');
        if ($recurrenceFrequency === '') return $this->save(null, $payload);
        if (!$this->access->canManage($employeeUid)) return $this->denied();
        try {
            $ids = $this->recurrences->create($payload, [
                'frequency' => $recurrenceFrequency,
                'interval' => $recurrenceInterval,
                'until' => $recurrenceUntil,
                'weekdays' => $recurrenceWeekdays,
                'timezone' => $recurrenceTimezone,
            ], $this->access->currentUser()?->getUID() ?? '');
            return new JSONResponse(['id' => $ids[0], 'ids' => $ids, 'seriesCount' => count($ids)]);
        } catch (InvalidArgumentException) {
            return $this->errors->create('invalid_recurrence', 'The recurring appointment is invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Terminserie konnte nicht angelegt werden.', ['exception' => $error]);
            return $this->errors->create('recurrence_save_failed', 'The recurring appointment could not be saved.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function update(int $id, string $employeeUid, string $start, string $end, string $type, string $title = '', string $seriesScope = 'occurrence'): JSONResponse {
        $existing = $this->calendar->existing($id);
        if (!$this->access->canManage($existing->employeeUid())) return $this->denied();
        if ($employeeUid !== $existing->employeeUid()) {
            return $this->errors->create('entry_owner_immutable', 'Calendar entries cannot be reassigned to another person.', Http::STATUS_CONFLICT);
        }
        if ($existing->meetingUid() !== null) {
            return $this->errors->create('meeting_managed_together', 'Shared meetings are edited together.', Http::STATUS_CONFLICT);
        }
        if ($seriesScope === 'series') {
            if ($existing->seriesUid() === null) return $this->errors->create('entry_not_recurring', 'The appointment is not part of a series.', Http::STATUS_BAD_REQUEST);
            $seriesEntries = $this->recurrences->seriesEntries($existing->seriesUid());
            foreach ($seriesEntries as $seriesEntry) if (!$this->access->canManage($seriesEntry->employeeUid())) return $this->denied();
            try {
                $ids = $this->recurrences->updateSeries($existing, compact('employeeUid', 'start', 'end', 'type', 'title'), $this->access->currentUser()?->getUID() ?? '');
                return new JSONResponse(['id' => $id, 'ids' => $ids, 'seriesCount' => count($ids)]);
            } catch (InvalidArgumentException) {
                return $this->errors->create('invalid_recurrence', 'The recurring appointment is invalid.', Http::STATUS_BAD_REQUEST);
            } catch (\Throwable $error) {
                $this->logger->error('Terminserie konnte nicht geändert werden.', ['exception' => $error]);
                return $this->errors->create('recurrence_save_failed', 'The recurring appointment could not be saved.', Http::STATUS_BAD_REQUEST);
            }
        }
        if ($seriesScope !== 'occurrence') return $this->errors->create('invalid_series_scope', 'The series scope is invalid.', Http::STATUS_BAD_REQUEST);
        return $this->save($id, compact('employeeUid', 'start', 'end', 'type', 'title'));
    }

    #[NoAdminRequired]
    public function delete(int $id, string $childMode = '', string $seriesScope = 'occurrence'): JSONResponse {
        try {
            $entry = $this->calendar->existing($id);
        } catch (\Throwable) {
            return $this->errors->create('entry_not_found', 'The calendar entry was not found.', Http::STATUS_NOT_FOUND);
        }
        if (!$this->access->canManage($entry->employeeUid())) return $this->denied();
        if ($entry->meetingUid() !== null) {
            return $this->errors->create('meeting_deleted_together', 'Shared meetings are deleted together.', Http::STATUS_CONFLICT);
        }
        if ($seriesScope === 'series') {
            if ($entry->seriesUid() === null) return $this->errors->create('entry_not_recurring', 'The appointment is not part of a series.', Http::STATUS_BAD_REQUEST);
            $seriesEntries = $this->recurrences->seriesEntries($entry->seriesUid());
            foreach ($seriesEntries as $seriesEntry) if (!$this->access->canManage($seriesEntry->employeeUid())) return $this->denied();
            try {
                $this->recurrences->deleteSeries($entry->seriesUid());
                return new JSONResponse(['deleted' => true, 'seriesScope' => 'series']);
            } catch (\Throwable $error) {
                $this->logger->error('Terminserie konnte nicht gelöscht werden.', ['exception' => $error]);
                return $this->errors->create('recurrence_delete_failed', 'The recurring appointment could not be deleted.', Http::STATUS_BAD_REQUEST);
            }
        }
        if ($seriesScope !== 'occurrence') return $this->errors->create('invalid_series_scope', 'The series scope is invalid.', Http::STATUS_BAD_REQUEST);
        try {
            $preview = $this->calendar->deletionPreview($id);
            if ($entry->type() === 'shift' && $preview['children'] !== [] && $childMode === '') {
                return new JSONResponse(['confirmationRequired' => true, 'children' => $preview['children']], Http::STATUS_CONFLICT);
            }
            $this->calendar->delete($id, $childMode);
            return new JSONResponse(['deleted' => true, 'childMode' => $childMode]);
        } catch (\Throwable) {
            return $this->errors->create('entry_delete_failed', 'The calendar entry could not be deleted.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function preferences(): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        $user = $this->access->currentUser();
        return new JSONResponse([
            'filters' => $this->preferencesFor($this->access->visibleEmployees()),
            'shiftDefaults' => $this->preferences->shiftDefaults($user?->getUID() ?? ''),
            'calendarSync' => $this->shiftSync->status($user?->getUID() ?? ''),
        ]);
    }

    #[NoAdminRequired]
    public function savePreferences(array $filters): JSONResponse {
        $user = $this->access->currentUser();
        if ($user === null) return $this->denied();
        $employees = $this->access->visibleEmployees();
        [$uids, $roles, $areas] = $this->filterOptions($employees);
        return new JSONResponse(['filters' => $this->preferences->saveFilterDefault($user->getUID(), $filters, $uids, $roles, $areas)]);
    }

    #[NoAdminRequired]
    public function saveShiftDefaults(array $shiftDefaults): JSONResponse {
        $user = $this->access->currentUser();
        if ($user === null) return $this->denied();
        return new JSONResponse(['shiftDefaults' => $this->preferences->saveShiftDefaults($user->getUID(), $shiftDefaults)]);
    }

    #[NoAdminRequired]
    public function saveCalendarSync(bool $enabled): JSONResponse {
        $user = $this->access->currentUser();
        if ($user === null) return $this->denied();
        try {
            return new JSONResponse(['calendarSync' => $this->shiftSync->configure($user->getUID(), $enabled)]);
        } catch (\Throwable $error) {
            $this->logger->error('Persönliche Kalendersynchronisation konnte nicht geändert werden.', ['exception' => $error]);
            return $this->errors->create('calendar_sync_failed', 'Personal calendar synchronisation could not be changed.', Http::STATUS_BAD_REQUEST);
        }
    }

    private function save(?int $id, array $payload): JSONResponse {
        if (!$this->access->canManage($payload['employeeUid'])) {
            return $this->denied();
        }
        try {
            $user = $this->access->currentUser();
            return new JSONResponse(['id' => $this->calendar->save($payload, $id, $user?->getUID() ?? '')]);
        } catch (InvalidArgumentException) {
            return $this->errors->create('invalid_calendar_entry', 'The calendar entry is invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable) {
            return $this->errors->create('invalid_calendar_entry', 'The calendar entry is invalid.', Http::STATUS_BAD_REQUEST);
        }
    }

    private function denied(): JSONResponse {
        return $this->errors->create('forbidden', 'You are not allowed to perform this action.', Http::STATUS_FORBIDDEN);
    }

    private function date(string $value): DateTimeImmutable {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Ungültiges Kalenderdatum.');
        }
        return $date;
    }

    private function withViewContext(array $response, array $employees): array {
        $uid = $this->access->currentUser()?->getUID() ?? '';
        $response['currentUserProfile'] = $this->access->currentProfile();
        $response['defaultFilters'] = $this->preferencesFor($employees);
        $response['shiftDefaults'] = $this->preferences->shiftDefaults($uid);
        $response['calendarSync'] = $this->shiftSync->status($uid);
        $response['organization'] = $this->settingsService->organization();
        $response['holidayCalendars'] = $this->holidayCalendars($response);
        return $response;
    }

    private function holidayCalendars(array $response): array {
        $start = $this->date((string)($response['start'] ?? ''));
        $end = $this->date((string)($response['end'] ?? ''));
        if ($end <= $start) throw new InvalidArgumentException('Ungültiger Kalenderbereich.');
        $lastVisibleDay = $end->modify('-1 day');
        $calendars = [];
        for ($year = (int)$start->format('Y'); $year <= (int)$lastVisibleDay->format('Y'); $year++) {
            $calendars[] = $this->holidays->forYear($year)->toArray();
        }
        return $calendars;
    }

    private function preferencesFor(array $employees): ?array {
        $user = $this->access->currentUser();
        if ($user === null) return null;
        [$uids, $roles, $areas] = $this->filterOptions($employees);
        return $this->preferences->filterDefault($user->getUID(), $uids, $roles, $areas);
    }

    private function filterOptions(array $employees): array {
        return [
            array_values(array_unique(array_column($employees, 'uid'))),
            array_values(array_unique(array_merge(...array_map(static fn(array $employee): array => $employee['roles'], $employees)))),
            array_values(array_unique(array_merge(...array_map(static fn(array $employee): array => $employee['areas'], $employees)))),
        ];
    }

}
