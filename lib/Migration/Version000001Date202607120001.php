<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000001Date202607120001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        if ($schema->hasTable('flz_calendar_entries')) return null;

        $table = $schema->createTable('flz_calendar_entries');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('employee_uid', Types::STRING, ['length' => 64, 'notnull' => true]);
        $table->addColumn('start_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('end_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('entry_type', Types::STRING, ['length' => 16, 'notnull' => true]);
        $table->addColumn('title', Types::STRING, ['length' => 255, 'notnull' => true, 'default' => '']);
        $table->addColumn('created_by_uid', Types::STRING, ['length' => 64, 'notnull' => true]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['employee_uid', 'start_at', 'end_at'], 'flz_calendar_employee_range');
        $table->addIndex(['start_at', 'end_at'], 'flz_calendar_range');
        return $schema;
    }
}
