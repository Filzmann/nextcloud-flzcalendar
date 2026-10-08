<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Kennzeichnet materialisierte Standarddienste und bewahrt geloeschte Einzelvorkommen als Ausnahme. */
final class Version000004Date202607130001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table = $schema->getTable('flz_calendar_entries');
        $changed = false;

        if (!$table->hasColumn('default_date')) {
            $table->addColumn('default_date', Types::STRING, ['length' => 10, 'notnull' => false]);
            $changed = true;
        }
        if (!$table->hasColumn('default_modified')) {
            $table->addColumn('default_modified', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $changed = true;
        }
        if (!$table->hasColumn('default_deleted')) {
            $table->addColumn('default_deleted', Types::BOOLEAN, ['notnull' => true, 'default' => false]);
            $changed = true;
        }
        if (!$table->hasIndex('flz_calendar_default_date')) {
            $table->addUniqueIndex(['employee_uid', 'default_date'], 'flz_calendar_default_date');
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
