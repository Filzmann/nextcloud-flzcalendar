<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;

/**
 * Zweck: Orchestriert Wochenansicht, Persistenz und Dienst-Termin-Zuordnung einzelner Kalendereinträge.
 * Zusammenspiel: ApiController -> CalendarService -> DefaultShiftMaterializer/CalendarEntryRepository.
 */
final class CalendarService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private DefaultShiftMaterializer $defaultShifts,
        private AbsenceService $absences,
        private ContainingShiftAssignment $shiftAssignment,
        private ShiftCalendarSyncService $shiftSync,
    ) {}

    public function week(DateTimeImmutable $start, array $employees): array {
        $end = $start->modify('+7 days');
        return $this->range($start, $end, $employees);
    }

    /**
     * Liefert vollständige Kalenderwochen für Wochen- und Monatsansichten in einem gemeinsamen Lesevorgang.
     * Der Bereich ist auf sechs Wochen begrenzt, damit UI-Parameter keine unbeschränkte Materialisierung auslösen.
     */
    public function range(DateTimeImmutable $start, DateTimeImmutable $end, array $employees): array {
        $days = (int)$start->diff($end)->format('%r%a');
        if ($start->format('N') !== '1' || $end->format('N') !== '1' || $days < 7 || $days > 42 || $days % 7 !== 0) {
            throw new InvalidArgumentException('Der Kalenderbereich muss eine bis sechs vollständige Wochen (maximal 42 Tage) umfassen.');
        }
        $employeeUids = array_column($employees, 'uid');
        $absences = $this->absences->query($start, $end, $employeeUids);
        for ($week = $start; $week < $end; $week = $week->modify('+7 days')) {
            $this->defaultShifts->syncWeek($week, $employeeUids, $absences);
        }
        $entries = $this->entries->findRange($start, $end, $employeeUids);
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'employees' => $employees,
            'entries' => array_map([$this, 'serializeEntry'], $entries),
            'absences' => array_map(static fn($absence): array => $absence->toArray(), $absences),
        ];
    }

    public function save(array $payload, ?int $id, string $actorUid): int {
        $previous = null;
        if ($id !== null) {
            $existing = $previous = $this->existing($id);
            if ($existing->meetingUid() !== null) {
                throw new InvalidArgumentException('Gemeinsame Meetings werden zusammen bearbeitet.');
            }
            if ($existing->seriesUid() !== null) {
                $payload = array_replace($payload, [
                    'seriesUid' => $existing->seriesUid(),
                    'seriesTimezone' => $existing->seriesTimezone(),
                ]);
            }
            if ($existing->defaultDate() !== null) {
                if ($existing->employeeUid() !== (string)($payload['employeeUid'] ?? '')) {
                    throw new InvalidArgumentException('Ein Standarddienst kann nicht einer anderen Person zugeordnet werden.');
                }
                $payload = array_replace($payload, ['defaultDate' => $existing->defaultDate(), 'defaultModified' => true, 'defaultDeleted' => false]);
            }
            $payload['id'] = $id;
        }
        $entry = CalendarEntry::get($payload);
        if ($entry->type() === CalendarEntry::TYPE_SHIFT) {
            $this->absences->assertShiftWritable($entry->employeeUid(), $entry->start(), $entry->end());
        }
        $this->assertTypeUnchanged($entry, $id);
        $this->assertShiftDoesNotOverlap($entry);
        $entry = $this->assignContainingShift($entry);
        $savedId = $this->entries->save($entry, $actorUid);
        $this->detachChildrenOutsideShift($entry, $savedId);
        $saved = CalendarEntry::get(array_replace($entry->toArray(), ['id' => $savedId]));
        if ($previous !== null && $previous->employeeUid() !== $saved->employeeUid()) {
            $this->shiftSync->remove($previous);
        }
        $this->shiftSync->publish($saved);
        return $savedId;
    }

    public function existing(int $id): CalendarEntry {
        return $this->entries->find($id) ?? throw new InvalidArgumentException('Kalendereintrag nicht gefunden.');
    }

    public function deletionPreview(int $id): array {
        $entry = $this->existing($id);
        $children = $entry->type() === CalendarEntry::TYPE_SHIFT ? $this->entries->children($id) : [];
        return ['entry' => $entry->toArray(), 'children' => array_map(static fn(CalendarEntry $child): array => $child->toArray(), $children)];
    }

    public function delete(int $id, string $childMode): void {
        $entry = $this->existing($id);
        if ($entry->meetingUid() !== null) {
            throw new InvalidArgumentException('Gemeinsame Meetings werden zusammen gelöscht.');
        }
        $children = $entry->type() === CalendarEntry::TYPE_SHIFT ? $this->entries->children($id) : [];
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT) {
            $this->entries->delete($id);
            $this->shiftSync->remove($entry);
            return;
        }
        if ($children !== [] && !in_array($childMode, ['delete', 'detach'], true)) {
            throw new InvalidArgumentException('Bitte Behandlung der enthaltenen Termine bestätigen.');
        }
        $childMode = $children === [] ? 'detach' : $childMode;
        if ($entry->defaultDate() !== null) {
            $this->entries->deleteDefaultShift($id, $childMode);
            $this->shiftSync->remove($entry);
            if ($childMode === 'delete') foreach ($children as $child) $this->shiftSync->remove($child);
            return;
        }
        $this->entries->deleteShift($id, $childMode);
        $this->shiftSync->remove($entry);
        if ($childMode === 'delete') foreach ($children as $child) $this->shiftSync->remove($child);
    }

    private function serializeEntry(CalendarEntry $entry): array {
        return $entry->toArray() + [
            'isBlocked' => $entry->type() === CalendarEntry::TYPE_APPOINTMENT && $entry->parentEntryId() === null,
        ];
    }

    private function assertTypeUnchanged(CalendarEntry $entry, ?int $id): void {
        if ($id !== null && $this->existing($id)->type() !== $entry->type()) {
            throw new InvalidArgumentException('Der Typ eines bestehenden Eintrags kann nicht geändert werden.');
        }
    }

    private function assertShiftDoesNotOverlap(CalendarEntry $entry): void {
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT) return;
        if ($this->entries->overlappingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id()) !== []) {
            throw new InvalidArgumentException('Der Dienst überschneidet sich mit einem bestehenden Dienst dieser Person.');
        }
    }

    private function assignContainingShift(CalendarEntry $entry): CalendarEntry {
        if ($entry->type() !== CalendarEntry::TYPE_APPOINTMENT) return $entry;
        $parents = $this->entries->containingShifts($entry->employeeUid(), $entry->start(), $entry->end(), $entry->id());
        return $this->shiftAssignment->assign($entry, $parents);
    }

    private function detachChildrenOutsideShift(CalendarEntry $entry, int $savedId): void {
        if ($entry->type() !== CalendarEntry::TYPE_SHIFT || $entry->id() === null) return;
        foreach ($this->entries->children($savedId) as $child) {
            if (!$child->isWithin($entry)) $this->entries->detachChild((int)$child->id());
        }
    }

}
