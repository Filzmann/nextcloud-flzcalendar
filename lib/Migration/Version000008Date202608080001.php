<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Korrigiert vor 0.14.0-rc.2 als lokale Wandzeit gespeicherte Serienvorkommen nach UTC. */
final class Version000008Date202608080001 extends SimpleMigrationStep {
    public function __construct(private IDBConnection $db) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'start_at', 'end_at', 'series_timezone')
            ->from('flz_calendar_entries')
            ->where($qb->expr()->isNotNull('series_uid'))
            ->andWhere($qb->expr()->isNotNull('series_timezone'));

        $corrected = 0;
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
            try {
                $timezone = new DateTimeZone((string)$row['series_timezone']);
            } catch (\Throwable) {
                $output->warning(sprintf('Serienvorkommen %s wegen ungültiger Zeitzone übersprungen.', (string)$row['id']));
                continue;
            }

            $start = $this->wallTime($row['start_at'], $timezone);
            $end = $this->wallTime($row['end_at'], $timezone);
            if ($start === null || $end === null) {
                $output->warning(sprintf('Serienvorkommen %s wegen ungültiger Zeitangabe übersprungen.', (string)$row['id']));
                continue;
            }

            $utc = new DateTimeZone('UTC');
            $update = $this->db->getQueryBuilder();
            $update->update('flz_calendar_entries')
                ->set('start_at', $update->createNamedParameter($start->setTimezone($utc), IQueryBuilder::PARAM_DATETIME_IMMUTABLE))
                ->set('end_at', $update->createNamedParameter($end->setTimezone($utc), IQueryBuilder::PARAM_DATETIME_IMMUTABLE))
                ->where($update->expr()->eq('id', $update->createNamedParameter((int)$row['id'], IQueryBuilder::PARAM_INT)))
                ->executeStatement();
            ++$corrected;
        }

        $output->info(sprintf('%d bestehende Serienvorkommen nach UTC korrigiert.', $corrected));
    }

    private function wallTime(mixed $value, DateTimeZone $timezone): ?DateTimeImmutable {
        $raw = $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : substr(str_replace('T', ' ', trim((string)$value)), 0, 19);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw, $timezone);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }
        return $date->format('Y-m-d H:i:s') === $raw ? $date : null;
    }
}
