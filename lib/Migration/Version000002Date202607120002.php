<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Macht die fachliche Dienst-Termin-Zuordnung dauerhaft und abfragbar. */
final class Version000002Date202607120002 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table = $schema->getTable('flz_calendar_entries');
        if (!$table->hasColumn('parent_entry_id')) {
            $table->addColumn('parent_entry_id', Types::BIGINT, ['notnull' => false]);
            $table->addIndex(['parent_entry_id'], 'flz_calendar_parent_entry');
        }
        return $schema;
    }
}
