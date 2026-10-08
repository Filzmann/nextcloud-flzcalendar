<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Zweck: Verknuepft bestehende Termine nur dann, wenn genau ein Dienst sie vollstaendig enthaelt.
 * Vertrag: Mehrdeutige oder externe Termine bleiben unveraendert als Sperrtermine erhalten.
 */
final class Version000003Date202607120003 extends SimpleMigrationStep {
    public function __construct(private IDBConnection $db) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $qb = $this->db->getQueryBuilder();
        $appointments = $qb->select('id', 'employee_uid', 'start_at', 'end_at')->from('flz_calendar_entries')
            ->where($qb->expr()->eq('entry_type', $qb->createNamedParameter('appointment')))
            ->andWhere($qb->expr()->isNull('parent_entry_id'))->executeQuery()->fetchAllAssociative();
        $linked = 0;
        foreach ($appointments as $appointment) {
            $find = $this->db->getQueryBuilder();
            $shifts = $find->select('id')->from('flz_calendar_entries')
                ->where($find->expr()->eq('employee_uid', $find->createNamedParameter($appointment['employee_uid'])))
                ->andWhere($find->expr()->eq('entry_type', $find->createNamedParameter('shift')))
                ->andWhere($find->expr()->lte('start_at', $find->createNamedParameter($appointment['start_at'])))
                ->andWhere($find->expr()->gte('end_at', $find->createNamedParameter($appointment['end_at'])))
                ->executeQuery()->fetchAllAssociative();
            if (count($shifts) !== 1) continue;
            $update = $this->db->getQueryBuilder();
            $update->update('flz_calendar_entries')->set('parent_entry_id', $update->createNamedParameter((int)$shifts[0]['id'], IQueryBuilder::PARAM_INT))
                ->where($update->expr()->eq('id', $update->createNamedParameter((int)$appointment['id'], IQueryBuilder::PARAM_INT)))->executeStatement();
            $linked++;
        }
        $output->info("{$linked} bestehende Termine eindeutig einem Dienst zugeordnet.");
    }
}
