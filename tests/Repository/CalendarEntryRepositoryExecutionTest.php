<?php

declare(strict_types=1);

namespace OCP\DB\QueryBuilder {
    interface IQueryBuilder {
        public const PARAM_NULL = 0;
        public const PARAM_INT = 1;
        public const PARAM_STR = 2;
        public const PARAM_BOOL = 5;
        public const PARAM_DATETIME_IMMUTABLE = 6;
        public const PARAM_STR_ARRAY = 102;
    }
}
namespace OCP {
    interface IDBConnection {
        public function getQueryBuilder();
        public function beginTransaction(): void;
        public function commit(): void;
        public function rollBack(): void;
    }
}

namespace {

    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCP\IDBConnection;

    final class FakeResult {
        public function __construct(
            private array $rows = [],
            private array $column = [],
            private mixed $one = false,
            private array|false $single = false,
        ) {}
        public function fetchAllAssociative(): array { return $this->rows; }
        public function fetchAssociative(): array|false { return $this->single; }
        public function fetchFirstColumn(): array { return $this->column; }
        public function fetchOne(): mixed { return $this->one; }
    }
    final class FakeExpression {
        public function __call(string $name, array $arguments): array { return [$name, $arguments]; }
    }
    final class FakeQueryBuilder {
        public array $calls = [];
        public function __construct(private FakeConnection $db, private FakeResult $result) {}
        public function expr(): FakeExpression { return new FakeExpression(); }
        public function createNamedParameter(mixed $value, mixed $type = null): array { return ['value' => $value, 'type' => $type]; }
        public function executeQuery(): FakeResult { $this->calls[] = ['executeQuery']; return $this->result; }
        public function executeStatement(): int {
            $this->calls[] = ['executeStatement'];
            if ($this->db->failStatements > 0) { $this->db->failStatements--; throw new RuntimeException('statement failed'); }
            return 1;
        }
        public function getLastInsertId(): int { return $this->db->nextId++; }
        public function __call(string $name, array $arguments): self { $this->calls[] = [$name, $arguments]; return $this; }
    }
    final class FakeConnection implements IDBConnection {
        /** @var list<FakeResult> */ public array $results = [];
        /** @var list<FakeQueryBuilder> */ public array $builders = [];
        public int $begun = 0;
        public int $committed = 0;
        public int $rolledBack = 0;
        public int $failStatements = 0;
        public int $nextId = 501;
        public function queue(FakeResult $result): void { $this->results[] = $result; }
        public function getQueryBuilder(): FakeQueryBuilder {
            $builder = new FakeQueryBuilder($this, array_shift($this->results) ?? new FakeResult());
            $this->builders[] = $builder;
            return $builder;
        }
        public function beginTransaction(): void { $this->begun++; }
        public function commit(): void { $this->committed++; }
        public function rollBack(): void { $this->rolledBack++; }
    }

    $row = static fn(int $id = 7, string $type = 'shift'): array => [
        'id' => $id,
        'employee_uid' => 'person-a',
        'start_at' => '2026-07-06T08:00:00+00:00',
        'end_at' => '2026-07-06T16:00:00+00:00',
        'entry_type' => $type,
        'title' => $type === 'appointment' ? 'Planning' : '',
        'parent_entry_id' => $type === 'appointment' ? 7 : null,
        'meeting_uid' => null,
        'series_uid' => $type === 'appointment' ? 'series-a' : null,
        'series_timezone' => $type === 'appointment' ? 'Europe/Berlin' : null,
        'default_date' => $type === 'shift' ? '2026-07-06' : null,
        'default_modified' => 0,
        'default_deleted' => 0,
    ];
    $db = new FakeConnection();
    $repository = new CalendarEntryRepository($db);
    $start = new DateTimeImmutable('2026-07-06T00:00:00Z');
    $end = new DateTimeImmutable('2026-07-13T00:00:00Z');

    if ($repository->findRange($start, $end, []) !== [] || $db->builders !== []) {
        throw new RuntimeException('Leere Mitarbeitendenauswahl erzeugt unnötige oder unbeschränkte Query.');
    }
    $db->queue(new FakeResult(rows: [$row(7), $row(8, 'appointment')]));
    $range = $repository->findRange($start, $end, ['person-a']);
    if (count($range) !== 2 || $range[1]->parentEntryId() !== 7 || $range[1]->seriesUid() !== 'series-a') {
        throw new RuntimeException('Bereichsabfrage mappt Kalenderzeilen nicht vollständig.');
    }
    $rangeCalls = $db->builders[0]->calls;
    if (!in_array(['orderBy', ['start_at', 'ASC']], $rangeCalls, true)) throw new RuntimeException('Bereichsabfrage ist nicht deterministisch sortiert.');

    $db->queue(new FakeResult(single: $row(7)));
    $db->queue(new FakeResult(single: false));
    if ($repository->find(7)?->id() !== 7 || $repository->find(999) !== null) throw new RuntimeException('Einzelsuche unterscheidet Treffer und Fehlen nicht.');
    $db->queue(new FakeResult(single: $row(7)));
    if ($repository->findDefaultOccurrence('person-a', '2026-07-06')?->defaultDate() !== '2026-07-06') throw new RuntimeException('Standardvorkommen wird nicht gefunden.');
    $db->queue(new FakeResult(rows: [$row(7)]));
    if (array_column(array_map(static fn(CalendarEntry $entry): array => $entry->toArray(), $repository->findShiftsForEmployee('person-a')), 'id') !== [7]) throw new RuntimeException('Dienstbestand einer Person wird nicht gelesen.');
    $db->queue(new FakeResult(rows: [$row(7), $row(8, 'appointment')]));
    if (array_map(static fn(CalendarEntry $entry): string => $entry->type(), $repository->findEntriesForEmployee('person-a')) !== ['shift', 'appointment']) {
        throw new RuntimeException('Der vollständige persönliche Bestand enthält nicht Dienste und Termine.');
    }
    $db->queue(new FakeResult(column: ['person-b', 'person-a', 'person-a']));
    if ($repository->findEmployeeUidsWithShifts() !== ['person-b', 'person-a']) throw new RuntimeException('Dienstkonten werden nicht typisiert und dedupliziert.');
    $db->queue(new FakeResult(column: ['person-c', 'person-a', 'person-c']));
    if ($repository->findEmployeeUidsWithEntries() !== ['person-c', 'person-a']) throw new RuntimeException('Konten mit eigenen Terminen fehlen im persönlichen Abgleich.');
    $meetingRow = array_replace($row(8, 'appointment'), ['meeting_uid' => 'meeting-a', 'series_uid' => null, 'series_timezone' => null]);
    $db->queue(new FakeResult(rows: [$meetingRow]));
    if ($repository->findMeeting('meeting-a')[0]->meetingUid() !== 'meeting-a') throw new RuntimeException('Meetingbestand wird nicht gelesen.');
    $db->queue(new FakeResult(rows: [$row(8, 'appointment')]));
    if ($repository->findSeries('series-a')[0]->seriesUid() !== 'series-a') throw new RuntimeException('Serienbestand wird nicht gelesen.');

    $newShift = CalendarEntry::get([
        'employeeUid' => 'person-a', 'start' => '2026-07-07T09:00:00+02:00', 'end' => '2026-07-07T17:00:00+02:00',
        'type' => 'shift', 'defaultDate' => '2026-07-07',
    ]);
    if ($repository->save($newShift, 'planner') !== 501) throw new RuntimeException('Insert liefert nicht die neue Datenbank-ID.');
    $insertCalls = $db->builders[array_key_last($db->builders)]->calls;
    if (!in_array(['insert', ['adc_entries']], $insertCalls, true)
        || !array_filter($insertCalls, static fn(array $call): bool => $call[0] === 'setValue' && $call[1][0] === 'created_by_uid')) {
        throw new RuntimeException('Insert bindet Auditfelder nicht explizit.');
    }
    $startBinding = array_values(array_filter($insertCalls, static fn(array $call): bool => $call[0] === 'setValue' && $call[1][0] === 'start_at'))[0][1][1]['value'] ?? null;
    if (!$startBinding instanceof DateTimeImmutable || $startBinding->getTimezone()->getName() !== 'UTC' || $startBinding->format('H:i') !== '07:00') {
        throw new RuntimeException('Kalenderzeiten werden nicht vor der Datenbankbindung nach UTC normalisiert.');
    }
    $persistedShift = CalendarEntry::get(array_replace($newShift->toArray(), ['id' => 42]));
    if ($repository->save($persistedShift, 'planner') !== 42) throw new RuntimeException('Update verliert die vorhandene ID.');
    $updateCalls = $db->builders[array_key_last($db->builders)]->calls;
    if (!in_array(['update', ['adc_entries']], $updateCalls, true)
        || array_filter($updateCalls, static fn(array $call): bool => $call[0] === 'set' && $call[1][0] === 'created_by_uid')) {
        throw new RuntimeException('Update verändert unveränderliche Erstellungsfelder.');
    }

    $ids = $repository->saveMany([$newShift, $persistedShift], 'planner');
    if ($ids !== [502, 42] || $db->begun !== 1 || $db->committed !== 1) throw new RuntimeException('Mehrfachspeicherung ist nicht atomar erfolgreich.');
    $db->failStatements = 1;
    try {
        $repository->saveMany([$newShift], 'planner');
        throw new RuntimeException('Fehlgeschlagene Mehrfachspeicherung wurde bestätigt.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() !== 'statement failed' || $db->rolledBack !== 1) throw $error;
    }

    $repository->delete(7);
    $repository->deleteMeeting('meeting-a');
    $repository->deleteSeries('series-a');
    $db->queue(new FakeResult(rows: [$row(8, 'appointment')]));
    if ($repository->children(7)[0]->parentEntryId() !== 7) throw new RuntimeException('Dienstkinder werden nicht gelesen.');
    $repository->detachChildren(7);
    $repository->detachChild(8);
    $repository->deleteChildren(7);

    $repository->deleteShift(7, 'delete');
    $repository->deleteShift(7, 'detach');
    if ($db->begun !== 4 || $db->committed !== 3) throw new RuntimeException('Dienstlöschung kapselt Kindbehandlung nicht in Transaktionen.');
    $db->failStatements = 1;
    try { $repository->deleteShift(7, 'delete'); throw new RuntimeException('Fehlgeschlagene Dienstlöschung wurde bestätigt.'); }
    catch (RuntimeException $error) { if ($error->getMessage() !== 'statement failed' || $db->rolledBack !== 2) throw $error; }

    $repository->deleteDefaultShift(7, 'detach');
    $defaultBuilder = $db->builders[array_key_last($db->builders)];
    if (!array_filter($defaultBuilder->calls, static fn(array $call): bool => $call[0] === 'set' && $call[1][0] === 'default_deleted')) {
        throw new RuntimeException('Standarddienstlöschung erzeugt keinen dauerhaften Tombstone.');
    }
    $db->failStatements = 1;
    try { $repository->deleteDefaultShift(7, 'delete'); throw new RuntimeException('Fehlgeschlagener Tombstone wurde bestätigt.'); }
    catch (RuntimeException $error) { if ($error->getMessage() !== 'statement failed' || $db->rolledBack !== 3) throw $error; }
    $repository->removeGeneratedDefault(7);

    $repository->attachContainedAppointments(7, 'person-a', $start, $end);
    $db->queue(new FakeResult(rows: [$row(7)]));
    if ($repository->containingShifts('person-a', $start, $end, 9)[0]->id() !== 7) throw new RuntimeException('Enthaltende Dienste werden nicht gelesen.');
    $db->queue(new FakeResult(rows: [$row(7)]));
    if ($repository->overlappingShifts('person-a', $start, $end)[0]->id() !== 7) throw new RuntimeException('Überschneidende Dienste werden nicht gelesen.');
    $db->queue(new FakeResult(one: '7'));
    $db->queue(new FakeResult(one: false));
    if (!$repository->existsCreatedBy('planner', $start, $end)
        || $repository->existsCreatedByForEmployee('planner', 'person-a', $start, $end)) {
        throw new RuntimeException('Demo-Existenzprüfungen unterscheiden Treffer und Fehlen nicht.');
    }

    echo "CalendarEntryRepositoryExecutionTest: OK\n";
}
