<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Migration;

use Closure;
use OCA\AdCalendar\BackgroundJob\ReconcileShiftCalendarsJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Registriert den DAV-Konsistenzjob auch bei Updates bestehender Installationen. */
final class Version000007Date202607220002 extends SimpleMigrationStep {
    public function __construct(private IJobList $jobs) {}

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        if (!$this->jobs->has(ReconcileShiftCalendarsJob::class, null)) {
            $this->jobs->add(ReconcileShiftCalendarsJob::class);
        }
    }
}
