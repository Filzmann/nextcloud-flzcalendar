<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Verknüpft materialisierte Einzelvorkommen einer begrenzten Terminserie. */
final class Version000006Date202607220001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table = $schema->getTable('flz_calendar_entries');
        $changed = false;

        if (!$table->hasColumn('series_uid')) {
            $table->addColumn('series_uid', Types::STRING, ['length' => 64, 'notnull' => false]);
            $changed = true;
        }
        if (!$table->hasColumn('series_timezone')) {
            $table->addColumn('series_timezone', Types::STRING, ['length' => 64, 'notnull' => false]);
            $changed = true;
        }
        if (!$table->hasIndex('flz_calendar_series_uid')) {
            $table->addIndex(['series_uid'], 'flz_calendar_series_uid');
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
