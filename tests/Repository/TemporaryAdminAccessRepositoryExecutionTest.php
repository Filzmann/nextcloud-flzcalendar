<?php

declare(strict_types=1);

namespace OCP\DB\QueryBuilder {
    interface IQueryBuilder {
        public const PARAM_NULL = 0;
        public const PARAM_STR = 2;
        public const PARAM_DATETIME_IMMUTABLE = 6;
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
    use OCA\FlzCalendar\Repository\TemporaryAdminAccessRepository;
    use OCP\IDBConnection;

    final class TemporaryAdminResult {
        public function __construct(
            private array|false $single = false,
            private array $rows = [],
        ) {}

        public function fetchAssociative(): array|false { return $this->single; }
        public function fetchAllAssociative(): array { return $this->rows; }
    }

    final class TemporaryAdminExpression {
        public function __call(string $name, array $arguments): array { return [$name, $arguments]; }
    }

    final class TemporaryAdminQueryBuilder {
        public array $calls = [];

        public function __construct(
            private TemporaryAdminResult $result,
            private int|Throwable $statementResult = 1,
            private int $lastInsertId = 91,
        ) {}

        public function expr(): TemporaryAdminExpression { return new TemporaryAdminExpression(); }
        public function createNamedParameter(mixed $value, mixed $type = null): array { return ['value' => $value, 'type' => $type]; }
        public function executeQuery(): TemporaryAdminResult { return $this->result; }
        public function executeStatement(): int {
            if ($this->statementResult instanceof Throwable) throw $this->statementResult;
            return $this->statementResult;
        }
        public function getLastInsertId(): int { return $this->lastInsertId; }
        public function __call(string $name, array $arguments): self {
            $this->calls[] = [$name, $arguments];
            return $this;
        }
    }

    final class TemporaryAdminConnection implements IDBConnection {
        /** @var list<TemporaryAdminQueryBuilder> */
        public array $builders = [];
        public int $begun = 0;
        public int $committed = 0;
        public int $rolledBack = 0;

        public function queue(TemporaryAdminQueryBuilder $builder): void { $this->builders[] = $builder; }
        public function getQueryBuilder(): TemporaryAdminQueryBuilder {
            return array_shift($this->builders) ?? new TemporaryAdminQueryBuilder(new TemporaryAdminResult());
        }
        public function beginTransaction(): void { $this->begun++; }
        public function commit(): void { $this->committed++; }
        public function rollBack(): void { $this->rolledBack++; }
    }

    $row = static fn(array $overrides = []): array => array_replace([
        'id' => '17',
        'target_uid' => 'admin-target',
        'granted_by' => 'privacy-officer',
        'starts_at' => '2026-10-08T10:00:00+00:00',
        'ends_at' => new DateTime('2026-10-08T12:00:00+00:00'),
        'revoked_at' => null,
        'revoked_by' => null,
    ], $overrides);
    $startsAt = new DateTimeImmutable('2026-10-08T10:00:00+00:00');
    $endsAt = new DateTimeImmutable('2026-10-08T12:00:00+00:00');

    $db = new TemporaryAdminConnection();
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult()));
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(), 1, 91));
    $repository = new TemporaryAdminAccessRepository($db);
    $grant = $repository->replaceActive('admin-target', 'privacy-officer', $startsAt, $endsAt);
    if ($grant['id'] !== 91 || $grant['targetUid'] !== 'admin-target' || $grant['revokedAt'] !== null
        || $db->begun !== 1 || $db->committed !== 1 || $db->rolledBack !== 0) {
        throw new RuntimeException('Das atomare Ersetzen einer Adminfreigabe verliert Zustand oder Auditdaten.');
    }

    $failingDb = new TemporaryAdminConnection();
    $failingDb->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(), new RuntimeException('write failed')));
    try {
        (new TemporaryAdminAccessRepository($failingDb))->replaceActive('admin-target', 'privacy-officer', $startsAt, $endsAt);
        throw new RuntimeException('Ein fehlgeschlagenes Ersetzen wurde bestätigt.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() !== 'write failed' || $failingDb->rolledBack !== 1 || $failingDb->committed !== 0) throw $error;
    }

    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(), 1));
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(), 0));
    if (!$repository->revokeActive('admin-target', 'privacy-officer', $endsAt)
        || $repository->revokeActive('missing-admin', 'privacy-officer', $endsAt)) {
        throw new RuntimeException('Widerruf unterscheidet aktive und fehlende Freigaben nicht.');
    }

    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(single: false)));
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(single: $row())));
    if ($repository->activeFor('missing-admin', $startsAt) !== null) {
        throw new RuntimeException('Eine fehlende Freigabe wird nicht als fehlend ausgewiesen.');
    }
    $active = $repository->activeFor('admin-target', $startsAt);
    if ($active === null || $active['id'] !== 17 || $active['startsAt']->getTimezone()->getName() !== '+00:00'
        || !$active['endsAt'] instanceof DateTimeImmutable) {
        throw new RuntimeException('Eine aktive Freigabe wird nicht stabil typisiert.');
    }

    $revoked = $row(['id' => 18, 'revoked_at' => '2026-10-08T11:00:00+00:00', 'revoked_by' => 'privacy-officer']);
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(rows: [$revoked, $row()])));
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(rows: [$revoked])));
    $history = $repository->history();
    $subjectHistory = $repository->historyForUid('privacy-officer', 25);
    if (count($history) !== 2 || $history[0]['revokedAt']?->format(DATE_ATOM) !== '2026-10-08T11:00:00+00:00'
        || $history[0]['revokedBy'] !== 'privacy-officer' || count($subjectHistory) !== 1) {
        throw new RuntimeException('Freigabehistorie verliert Widerruf oder Betroffenenbezug.');
    }

    echo "TemporaryAdminAccessRepositoryExecutionTest: OK\n";
}
