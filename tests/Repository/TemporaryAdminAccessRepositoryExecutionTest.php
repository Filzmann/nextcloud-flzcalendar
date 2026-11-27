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
    $db = new TemporaryAdminConnection();
    $repository = new TemporaryAdminAccessRepository($db);

    $revoked = $row(['id' => 18, 'revoked_at' => '2026-10-08T11:00:00+00:00', 'revoked_by' => 'privacy-officer']);
    $db->queue(new TemporaryAdminQueryBuilder(new TemporaryAdminResult(rows: [$revoked])));
    $subjectHistory = $repository->historyForUid('privacy-officer', 25);
    if (count($subjectHistory) !== 1 || $subjectHistory[0]['revokedAt']?->format(DATE_ATOM) !== '2026-10-08T11:00:00+00:00'
        || $subjectHistory[0]['revokedBy'] !== 'privacy-officer') {
        throw new RuntimeException('Read-only-Freigabehistorie verliert Widerruf oder Betroffenenbezug.');
    }

    echo "TemporaryAdminAccessRepositoryExecutionTest: OK\n";
}
