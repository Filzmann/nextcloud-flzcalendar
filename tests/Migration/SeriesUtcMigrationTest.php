<?php

declare(strict_types=1);

namespace OCP\DB\QueryBuilder {
    interface IQueryBuilder { public const PARAM_INT = 1; public const PARAM_DATETIME_IMMUTABLE = 6; }
}
namespace OCP\Migration {
    interface IOutput {
        public function debug(string $message): void;
        public function info($message): void;
        public function warning($message): void;
        public function startProgress($max = 0): void;
        public function advance($step = 1, $description = ''): void;
        public function finishProgress(): void;
    }
    class SimpleMigrationStep {}
}
namespace OCP {
    interface IDBConnection { public function getQueryBuilder(); }
}
namespace {
    require_once __DIR__ . '/../../lib/Migration/Version000008Date202608080001.php';

    use OCA\AdCalendar\Migration\Version000008Date202608080001;
    use OCP\IDBConnection;
    use OCP\Migration\IOutput;

    final class Result {
        public function __construct(private array $rows) {}
        public function fetchAllAssociative(): array { return $this->rows; }
    }
    final class Expression {
        public function __call(string $name, array $arguments): array { return [$name, $arguments]; }
    }
    final class Builder {
        public array $calls = [];
        public function __construct(private array $rows = []) {}
        public function expr(): Expression { return new Expression(); }
        public function createNamedParameter(mixed $value, mixed $type = null): array { return ['value' => $value, 'type' => $type]; }
        public function executeQuery(): Result { return new Result($this->rows); }
        public function executeStatement(): int { return 1; }
        public function __call(string $name, array $arguments): self { $this->calls[] = [$name, $arguments]; return $this; }
    }
    final class Connection implements IDBConnection {
        public array $builders = [];
        public function __construct(private array $rows) {}
        public function getQueryBuilder(): Builder {
            $builder = new Builder($this->builders === [] ? $this->rows : []);
            $this->builders[] = $builder;
            return $builder;
        }
    }
    final class Output implements IOutput {
        public array $messages = [];
        public function debug(string $message): void {}
        public function info($message): void { $this->messages[] = (string)$message; }
        public function warning($message): void { $this->messages[] = (string)$message; }
        public function startProgress($max = 0): void {}
        public function advance($step = 1, $description = ''): void {}
        public function finishProgress(): void {}
    }

    $db = new Connection([
        ['id' => '7', 'start_at' => '2026-03-23 09:00:00', 'end_at' => '2026-03-23 10:00:00', 'series_timezone' => 'Europe/Berlin'],
        ['id' => '8', 'start_at' => '2026-03-30 09:00:00', 'end_at' => '2026-03-30 10:00:00', 'series_timezone' => 'Europe/Berlin'],
        ['id' => '9', 'start_at' => '2026-04-06 09:00:00', 'end_at' => '2026-04-06 10:00:00', 'series_timezone' => 'invalid/timezone'],
    ]);
    $output = new Output();
    (new Version000008Date202608080001($db))->postSchemaChange($output, static fn() => null, []);

    $updates = array_slice($db->builders, 1);
    if (count($updates) !== 2) throw new RuntimeException('Ungültige Serienzeitzonen werden nicht isoliert übersprungen.');
    $starts = [];
    foreach ($updates as $builder) {
        foreach ($builder->calls as $call) {
            if ($call[0] === 'set' && $call[1][0] === 'start_at') $starts[] = $call[1][1]['value'];
        }
    }
    if (array_map(static fn(DateTimeImmutable $date): string => $date->format('Y-m-d H:i T'), $starts) !== [
        '2026-03-23 08:00 UTC',
        '2026-03-30 07:00 UTC',
    ]) throw new RuntimeException('Bestehende Serien-Wandzeiten werden nicht DST-sicher nach UTC korrigiert.');
    if (!in_array('2 bestehende Serienvorkommen nach UTC korrigiert.', $output->messages, true)) {
        throw new RuntimeException('Migrationsergebnis bleibt unsichtbar.');
    }

    echo "SeriesUtcMigrationTest: OK\n";
}
