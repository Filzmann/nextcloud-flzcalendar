<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\Exception\MeetingSlotUnavailableException;
use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\Service\MeetingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Stellt Suche sowie gemeinsame Anlage, Bearbeitung und Löschung von Meetings als abgesicherten API-Vertrag bereit.
 * Zusammenspiel: MeetingFinder -> MeetingController -> CalendarAccessService/MeetingService.
 * Vertrag: Sichtbare Kalender dürfen durchsucht werden; Schreiben erfordert Bearbeitungsrecht für jede Zielperson.
 */
final class MeetingController extends Controller {
    public function __construct(IRequest $request, private CalendarAccessService $access, private MeetingService $meetings, private LoggerInterface $logger, private LocalizedErrorResponseFactory $errors) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function gaps(string $start, array $employeeUids, int $durationMinutes = 60): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $uids = $this->validUids($employeeUids, $durationMinutes);
            $canBlockAll = array_reduce($uids, fn(bool $allowed, string $uid): bool => $allowed && $this->access->canManage($uid), true);
            return new JSONResponse(['gaps' => $this->meetings->gaps(new DateTimeImmutable($start), $uids, $durationMinutes), 'canBlockAll' => $canBlockAll]);
        } catch (\Throwable) {
            return $this->errors->create('invalid_meeting_search', 'Participants, calendar week or duration are invalid.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function block(string $start, string $end, array $employeeUids, string $title): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $startsAt = new DateTimeImmutable($start); $endsAt = new DateTimeImmutable($end);
            $durationMinutes = (int)(($endsAt->getTimestamp() - $startsAt->getTimestamp()) / 60);
            $uids = $this->validUids($employeeUids, $durationMinutes);
            foreach ($uids as $uid) if (!$this->access->canManage($uid)) return $this->denied();
            $actorUid = $this->access->currentUser()?->getUID() ?? '';
            return new JSONResponse(['ids' => $this->meetings->block($startsAt, $endsAt, $uids, $title, $actorUid)], Http::STATUS_CREATED);
        } catch (MeetingSlotUnavailableException) {
            return $this->errors->create('meeting_slot_unavailable', 'The selected meeting slot is no longer available.', Http::STATUS_CONFLICT);
        } catch (InvalidArgumentException) {
            return $this->errors->create('invalid_meeting', 'Participants, time range or title are invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Meeting konnte nicht für alle blockiert werden.', ['exception' => $error]);
            return $this->errors->create('meeting_block_failed', 'The meeting could not be blocked for everyone.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function update(string $meetingUid, string $start, string $end, string $title): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $entries = $this->meetings->entries($meetingUid);
            if ($entries === []) return $this->errors->create('meeting_not_found', 'The meeting was not found.', Http::STATUS_NOT_FOUND);
            foreach ($entries as $entry) if (!$this->access->canManage($entry->employeeUid())) return $this->denied();
            $actorUid = $this->access->currentUser()?->getUID() ?? '';
            return new JSONResponse(['ids' => $this->meetings->update($meetingUid, new DateTimeImmutable($start), new DateTimeImmutable($end), $title, $actorUid)]);
        } catch (MeetingSlotUnavailableException) {
            return $this->errors->create('meeting_slot_unavailable', 'The selected meeting slot is no longer available.', Http::STATUS_CONFLICT);
        } catch (InvalidArgumentException) {
            return $this->errors->create('invalid_meeting', 'Meeting, time range or title are invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Meeting konnte nicht gemeinsam bearbeitet werden.', ['exception' => $error]);
            return $this->errors->create('meeting_update_failed', 'The meeting could not be updated for everyone.', Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function delete(string $meetingUid): JSONResponse {
        if (!$this->access->canView()) return $this->denied();
        try {
            $entries = $this->meetings->entries($meetingUid);
            if ($entries === []) return $this->errors->create('meeting_not_found', 'The meeting was not found.', Http::STATUS_NOT_FOUND);
            foreach ($entries as $entry) if (!$this->access->canManage($entry->employeeUid())) return $this->denied();
            $this->meetings->delete($meetingUid);
            return new JSONResponse(['deleted' => true]);
        } catch (InvalidArgumentException) {
            return $this->errors->create('invalid_meeting', 'The meeting identifier is invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Meeting konnte nicht gemeinsam gelöscht werden.', ['exception' => $error]);
            return $this->errors->create('meeting_delete_failed', 'The meeting could not be deleted for everyone.', Http::STATUS_BAD_REQUEST);
        }
    }

    /** @return list<string> */
    private function validUids(array $employeeUids, int $durationMinutes): array {
        $uids = array_values(array_unique(array_map('strval', $employeeUids)));
        if (count($uids) < 2 || count($uids) > 20 || $durationMinutes < 15 || $durationMinutes > 480) throw new InvalidArgumentException();
        $visible = array_fill_keys(array_column($this->access->visibleEmployees(), 'uid'), true);
        foreach ($uids as $uid) if (!isset($visible[$uid])) throw new InvalidArgumentException();
        return $uids;
    }

    private function denied(): JSONResponse { return $this->errors->create('forbidden', 'You are not allowed to perform this action.', Http::STATUS_FORBIDDEN); }
}
