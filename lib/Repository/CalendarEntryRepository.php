<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Repository;

use DateTimeImmutable;
use DateTimeZone;
use OCA\FlzCalendar\Model\CalendarEntry;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** Zweck: Kapselt alle gebundenen Datenbankzugriffe auf Kalendereintraege. */
final class CalendarEntryRepository {
    private const COLUMNS = [
        'id', 'employee_uid', 'start_at', 'end_at', 'entry_type', 'title', 'parent_entry_id',
        'meeting_uid', 'series_uid', 'series_timezone', 'default_date', 'default_modified', 'default_deleted',
    ];

    public function __construct(private IDBConnection $db) {}

    /** @return list<CalendarEntry> */
    public function findRange(DateTimeImmutable $start, DateTimeImmutable $end, array $employeeUids): array {
        if ($employeeUids === []) return [];
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->gt('end_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->in('employee_uid', $qb->createNamedParameter($employeeUids, IQueryBuilder::PARAM_STR_ARRAY)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('start_at', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function find(int $id): ?CalendarEntry {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row === false ? null : CalendarEntry::get($this->mapRow($row));
    }

    public function findDefaultOccurrence(string $employeeUid, string $date): ?CalendarEntry {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('default_date', $qb->createNamedParameter($date)));
        $row = $qb->executeQuery()->fetchAssociative();
        return $row === false ? null : CalendarEntry::get($this->mapRow($row));
    }

    /** @return list<CalendarEntry> */
    public function findShiftsForEmployee(string $employeeUid): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_SHIFT)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('id', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    /** @return list<CalendarEntry> */
    public function findEntriesForEmployee(string $employeeUid): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('id', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    /** @return list<CalendarEntry> */
    public function findByEmployeeUid(string $employeeUid, int $limit): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('start_at', 'ASC')
            ->setMaxResults($limit);
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    /** @param list<string> $meetingUids @return list<string> */
    public function findMeetingUidsWithOtherParticipants(string $subjectUid, array $meetingUids): array {
        if ($meetingUids === []) return [];
        $qb = $this->db->getQueryBuilder();
        $qb->select('meeting_uid')->from('flz_calendar_entries')
            ->where($qb->expr()->in('meeting_uid', $qb->createNamedParameter($meetingUids, IQueryBuilder::PARAM_STR_ARRAY)))
            ->andWhere($qb->expr()->neq('employee_uid', $qb->createNamedParameter($subjectUid, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('meeting_uid', 'ASC');
        return array_values(array_unique(array_map('strval', $qb->executeQuery()->fetchFirstColumn())));
    }

    /** @return list<string> */
    public function findEmployeeUidsWithShifts(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('employee_uid')->from('flz_calendar_entries')
            ->where($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_SHIFT)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('employee_uid', 'ASC');
        return array_values(array_unique(array_map('strval', $qb->executeQuery()->fetchFirstColumn())));
    }

    /** @return list<string> */
    public function findEmployeeUidsWithEntries(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('employee_uid')->from('flz_calendar_entries')
            ->where($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('employee_uid', 'ASC');
        return array_values(array_unique(array_map('strval', $qb->executeQuery()->fetchFirstColumn())));
    }

    /** @return list<CalendarEntry> */
    public function findMeeting(string $meetingUid): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('meeting_uid', $qb->createNamedParameter($meetingUid)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('employee_uid', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    /** @return list<CalendarEntry> */
    public function findSeries(string $seriesUid): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('series_uid', $qb->createNamedParameter($seriesUid)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('start_at', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function save(CalendarEntry $entry, string $actorUid): int {
        $utc = new DateTimeZone('UTC');
        $now = new DateTimeImmutable('now', $utc);
        $values = [
            'employee_uid' => $entry->employeeUid(),
            'start_at' => $entry->start()->setTimezone($utc),
            'end_at' => $entry->end()->setTimezone($utc),
            'entry_type' => $entry->type(), 'title' => $entry->title(), 'parent_entry_id' => $entry->parentEntryId(),
            'meeting_uid' => $entry->meetingUid(), 'default_date' => $entry->defaultDate(), 'default_modified' => $entry->defaultModified(),
            'series_uid' => $entry->seriesUid(), 'series_timezone' => $entry->seriesTimezone(),
            'default_deleted' => $entry->defaultDeleted(), 'updated_at' => $now,
        ];
        $types = [
            'employee_uid' => IQueryBuilder::PARAM_STR, 'start_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'end_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE, 'entry_type' => IQueryBuilder::PARAM_STR,
            'title' => IQueryBuilder::PARAM_STR, 'parent_entry_id' => IQueryBuilder::PARAM_INT,
            'meeting_uid' => IQueryBuilder::PARAM_STR, 'default_date' => IQueryBuilder::PARAM_STR, 'default_modified' => IQueryBuilder::PARAM_BOOL,
            'series_uid' => IQueryBuilder::PARAM_STR, 'series_timezone' => IQueryBuilder::PARAM_STR,
            'default_deleted' => IQueryBuilder::PARAM_BOOL, 'updated_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
        ];
        $qb = $this->db->getQueryBuilder();
        $insert = $entry->id() === null;
        if ($insert) {
            $values += ['created_by_uid' => $actorUid, 'created_at' => $now];
            $types += ['created_by_uid' => IQueryBuilder::PARAM_STR, 'created_at' => IQueryBuilder::PARAM_DATETIME_IMMUTABLE];
            $qb->insert('flz_calendar_entries');
        } else {
            $qb->update('flz_calendar_entries')->where($qb->expr()->eq('id', $qb->createNamedParameter($entry->id(), IQueryBuilder::PARAM_INT)));
        }
        foreach ($values as $field => $value) {
            $parameter = $qb->createNamedParameter($value, $value === null ? IQueryBuilder::PARAM_NULL : $types[$field]);
            if ($insert) $qb->setValue($field, $parameter);
            else $qb->set($field, $parameter);
        }
        $qb->executeStatement();
        return $entry->id() ?? $qb->getLastInsertId();
    }

    /** Vertrag: Gemeinsame Meetingtermine werden vollständig oder gar nicht gespeichert. */
    public function saveMany(array $entries, string $actorUid): array {
        $this->db->beginTransaction();
        try {
            $ids = array_map(fn(CalendarEntry $entry): int => $this->save($entry, $actorUid), $entries);
            $this->db->commit();
            return $ids;
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function delete(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('flz_calendar_entries')->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    public function deleteMeeting(string $meetingUid): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('flz_calendar_entries')
            ->where($qb->expr()->eq('meeting_uid', $qb->createNamedParameter($meetingUid)))
            ->executeStatement();
    }

    public function deleteSeries(string $seriesUid): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('flz_calendar_entries')
            ->where($qb->expr()->eq('series_uid', $qb->createNamedParameter($seriesUid)))
            ->executeStatement();
    }

    /** @return list<CalendarEntry> */
    public function children(int $parentId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('parent_entry_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('start_at', 'ASC');
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function detachChildren(int $parentId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('flz_calendar_entries')->set('parent_entry_id', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
            ->where($qb->expr()->eq('parent_entry_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    public function detachChild(int $childId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('flz_calendar_entries')->set('parent_entry_id', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($childId, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    public function deleteChildren(int $parentId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('flz_calendar_entries')->where($qb->expr()->eq('parent_entry_id', $qb->createNamedParameter($parentId, IQueryBuilder::PARAM_INT)))->executeStatement();
    }

    /** Vertrag: Kindbehandlung und Dienstloeschung sind atomar; ein Fehler laesst alles unveraendert. */
    public function deleteShift(int $shiftId, string $childMode): void {
        $this->db->beginTransaction();
        try {
            if ($childMode === 'delete') $this->deleteChildren($shiftId);
            else $this->detachChildren($shiftId);
            $this->delete($shiftId);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** Vertrag: Das ausgeblendete Vorkommen bleibt als Tombstone erhalten und wird nicht erneut materialisiert. */
    public function deleteDefaultShift(int $shiftId, string $childMode): void {
        $this->db->beginTransaction();
        try {
            if ($childMode === 'delete') $this->deleteChildren($shiftId);
            else $this->detachChildren($shiftId);
            $qb = $this->db->getQueryBuilder();
            $qb->update('flz_calendar_entries')
                ->set('default_deleted', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
                ->set('default_modified', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
                ->set('updated_at', $qb->createNamedParameter(new DateTimeImmutable('now', new DateTimeZone('UTC')), IQueryBuilder::PARAM_DATETIME_IMMUTABLE))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($shiftId, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    /** Entfernt nur eine unveraenderte, aus einer inzwischen deaktivierten Vorlage erzeugte Instanz. */
    public function removeGeneratedDefault(int $shiftId): void {
        $this->db->beginTransaction();
        try {
            $this->detachChildren($shiftId);
            $this->delete($shiftId);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function attachContainedAppointments(int $shiftId, string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('flz_calendar_entries')
            ->set('parent_entry_id', $qb->createNamedParameter($shiftId, IQueryBuilder::PARAM_INT))
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_APPOINTMENT)))
            ->andWhere($qb->expr()->isNull('parent_entry_id'))
            ->andWhere($qb->expr()->gte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->lte('end_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->executeStatement();
    }

    /** @return list<CalendarEntry> */
    public function containingShifts(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_SHIFT)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->lte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->gte('end_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
        if ($excludeId !== null) $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId, IQueryBuilder::PARAM_INT)));
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    /** @return list<CalendarEntry> */
    public function overlappingShifts(string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end, ?int $excludeId = null): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(...self::COLUMNS)->from('flz_calendar_entries')
            ->where($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->eq('entry_type', $qb->createNamedParameter(CalendarEntry::TYPE_SHIFT)))
            ->andWhere($qb->expr()->eq('default_deleted', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->gt('end_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)));
        if ($excludeId !== null) $qb->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($excludeId, IQueryBuilder::PARAM_INT)));
        return CalendarEntry::get_all(array_map([$this, 'mapRow'], $qb->executeQuery()->fetchAllAssociative()));
    }

    public function existsCreatedBy(string $actorUid, DateTimeImmutable $start, DateTimeImmutable $end): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('flz_calendar_entries')
            ->where($qb->expr()->eq('created_by_uid', $qb->createNamedParameter($actorUid)))
            ->andWhere($qb->expr()->gte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    public function existsCreatedByForEmployee(string $actorUid, string $employeeUid, DateTimeImmutable $start, DateTimeImmutable $end): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('flz_calendar_entries')
            ->where($qb->expr()->eq('created_by_uid', $qb->createNamedParameter($actorUid)))
            ->andWhere($qb->expr()->eq('employee_uid', $qb->createNamedParameter($employeeUid)))
            ->andWhere($qb->expr()->gte('start_at', $qb->createNamedParameter($start, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->andWhere($qb->expr()->lt('start_at', $qb->createNamedParameter($end, IQueryBuilder::PARAM_DATETIME_IMMUTABLE)))
            ->setMaxResults(1);
        return $qb->executeQuery()->fetchOne() !== false;
    }

    private function mapRow(array $row): array {
        $utc = new DateTimeZone('UTC');
        return [
            'id' => (int)$row['id'], 'employeeUid' => $row['employee_uid'],
            'start' => new DateTimeImmutable((string)$row['start_at'], $utc),
            'end' => new DateTimeImmutable((string)$row['end_at'], $utc),
            'type' => $row['entry_type'], 'title' => $row['title'],
            'parentEntryId' => $row['parent_entry_id'] === null ? null : (int)$row['parent_entry_id'],
            'meetingUid' => $row['meeting_uid'] === null ? null : (string)$row['meeting_uid'],
            'seriesUid' => $row['series_uid'] === null ? null : (string)$row['series_uid'],
            'seriesTimezone' => $row['series_timezone'] === null ? null : (string)$row['series_timezone'],
            'defaultDate' => $row['default_date'] === null ? null : (string)$row['default_date'],
            'defaultModified' => (bool)$row['default_modified'], 'defaultDeleted' => (bool)$row['default_deleted'],
        ];
    }
}
