<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\FlzCalendar\Exception\MeetingSlotUnavailableException;
use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\FlzCalendar\Repository\CalendarEntryRepository;

/**
 * Zweck: Findet gemeinsame Dienstlücken und verwaltet einen verknüpften Termin atomar für alle Beteiligten.
 * Zusammenspiel: MeetingController -> MeetingService -> DefaultShiftMaterializer/MeetingAvailabilityService/CalendarEntryRepository.
 * Vertrag: Vor dem Schreiben wird die Verfügbarkeit erneut geprüft; Teilnehmendenrechte prüft der Controller serverseitig.
 */
final class MeetingService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private MeetingAvailabilityService $availability,
        private DefaultShiftMaterializer $defaultShifts,
        private AbsenceService $absences,
    ) {}

    public function gaps(DateTimeImmutable $start, array $employeeUids, int $durationMinutes): array {
        $end = $start->modify('+7 days');
        return $this->findGaps($start, $end, $employeeUids, $durationMinutes);
    }

    /** Vertrag: Gemeinsame Termine werden vollständig oder gar nicht angelegt. */
    public function block(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids, string $title, string $actorUid): array {
        $title = $this->validTitle($title);
        $this->assertActor($actorUid);
        $this->assertAvailable($start, $end, $employeeUids);
        $meetingUid = bin2hex(random_bytes(16));

        $entries = [];
        foreach ($employeeUids as $employeeUid) {
            $entry = CalendarEntry::get(['employeeUid' => $employeeUid, 'start' => $start, 'end' => $end, 'type' => CalendarEntry::TYPE_APPOINTMENT, 'title' => $title, 'meetingUid' => $meetingUid]);
            $entries[] = $this->assignContainingShift($entry);
        }
        return $this->entries->saveMany($entries, $actorUid);
    }

    /** @return list<CalendarEntry> */
    public function entries(string $meetingUid): array {
        return $this->entries->findMeeting($this->validMeetingUid($meetingUid));
    }

    /** Vertrag: Zeitraum und Titel ändern sich für alle Beteiligten oder für niemanden. */
    public function update(string $meetingUid, DateTimeImmutable $start, DateTimeImmutable $end, string $title, string $actorUid): array {
        $meetingUid = $this->validMeetingUid($meetingUid);
        $title = $this->validTitle($title);
        $this->assertActor($actorUid);
        $existing = $this->entries->findMeeting($meetingUid);
        if ($existing === []) throw new InvalidArgumentException('Meeting nicht gefunden.');

        $employeeUids = array_values(array_unique(array_map(static fn(CalendarEntry $entry): string => $entry->employeeUid(), $existing)));
        $this->assertAvailable($start, $end, $employeeUids, $meetingUid);
        $updated = [];
        foreach ($existing as $entry) {
            $next = CalendarEntry::get([
                'id' => $entry->id(),
                'employeeUid' => $entry->employeeUid(),
                'start' => $start,
                'end' => $end,
                'type' => CalendarEntry::TYPE_APPOINTMENT,
                'title' => $title,
                'meetingUid' => $meetingUid,
            ]);
            $updated[] = $this->assignContainingShift($next);
        }
        return $this->entries->saveMany($updated, $actorUid);
    }

    public function delete(string $meetingUid): void {
        $meetingUid = $this->validMeetingUid($meetingUid);
        if ($this->entries->findMeeting($meetingUid) === []) throw new InvalidArgumentException('Meeting nicht gefunden.');
        $this->entries->deleteMeeting($meetingUid);
    }

    private function assignContainingShift(CalendarEntry $entry): CalendarEntry {
        $parents = $this->entries->containingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id());
        if (count($parents) > 1) throw new InvalidArgumentException('Der Termin liegt in mehreren Diensten. Bitte Dienste zuerst korrigieren.');
        return CalendarEntry::get(array_replace($entry->toArray(), ['parentEntryId' => $parents[0]->id() ?? null]));
    }

    private function assertAvailable(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids, ?string $excludedMeetingUid = null): void {
        $durationMinutes = (int)(($end->getTimestamp() - $start->getTimestamp()) / 60);
        $weekStart = $start->modify('monday this week')->setTime(0, 0);
        $weekEnd = $weekStart->modify('+7 days');
        if ($durationMinutes < 15 || $durationMinutes > 480 || $end <= $start || $start < $weekStart || $end > $weekEnd) {
            throw new InvalidArgumentException('Der Meetingzeitraum ist ungültig.');
        }

        foreach ($this->findGaps($weekStart, $weekEnd, $employeeUids, $durationMinutes, $excludedMeetingUid) as $gap) {
            if ($start >= new DateTimeImmutable($gap['start']) && $end <= new DateTimeImmutable($gap['end'])) return;
        }
        throw new MeetingSlotUnavailableException('Die Lücke ist inzwischen nicht mehr für alle verfügbar.');
    }

    private function findGaps(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids, int $durationMinutes, ?string $excludedMeetingUid = null): array {
        $absences = $this->absences->query($start, $end, $employeeUids);
        $this->defaultShifts->syncWeek($start, $employeeUids, $absences);
        $entries = $this->entries->findRange($start, $end, $employeeUids);
        if ($excludedMeetingUid !== null) {
            $entries = array_values(array_filter($entries, static fn(CalendarEntry $entry): bool => $entry->meetingUid() !== $excludedMeetingUid));
        }
        return $this->availability->find($entries, $employeeUids, $start, $end, $durationMinutes, $absences);
    }

    private function validTitle(string $title): string {
        $title = trim($title);
        if ($title === '' || $this->length($title) > 255) throw new InvalidArgumentException('Ein Meetingtitel ist erforderlich.');
        return $title;
    }

    private function validMeetingUid(string $meetingUid): string {
        $meetingUid = trim($meetingUid);
        if (preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $meetingUid) !== 1) throw new InvalidArgumentException('Die Meeting-Kennung ist ungültig.');
        return $meetingUid;
    }

    private function assertActor(string $actorUid): void {
        if ($actorUid === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
    }

    private function length(string $value): int { return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value); }
}
