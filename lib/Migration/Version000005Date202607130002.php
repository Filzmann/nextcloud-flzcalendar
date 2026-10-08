<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Verknüpft die pro Person gespeicherten Termine eines gemeinsamen Meetings. */
final class Version000005Date202607130002 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table = $schema->getTable('flz_calendar_entries');
        $changed = false;

        if (!$table->hasColumn('meeting_uid')) {
            $table->addColumn('meeting_uid', Types::STRING, ['length' => 64, 'notnull' => false]);
            $changed = true;
        }
        if (!$table->hasIndex('flz_calendar_meeting_uid')) {
            $table->addIndex(['meeting_uid'], 'flz_calendar_meeting_uid');
            $changed = true;
        }

        return $changed ? $schema : null;
    }
}
