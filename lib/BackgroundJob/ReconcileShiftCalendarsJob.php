<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\BackgroundJob;

use OCA\FlzCalendar\Service\ShiftCalendarReconciliationService;
use OCA\FlzCalendar\Service\ShiftCalendarReconciliationStatusService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use Override;

/** Zweck: Startet den fehlertoleranten DAV-Konsistenzlauf für alle standardmäßig aktiven persönlichen Dienstkalender alle 15 Minuten. */
final class ReconcileShiftCalendarsJob extends TimedJob {
    public function __construct(
        ITimeFactory $time,
        private ShiftCalendarReconciliationService $reconciliation,
        private ShiftCalendarReconciliationStatusService $status,
    ) {
        parent::__construct($time);
        $this->setInterval(15 * 60);
        $this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
        $this->setAllowParallelRuns(false);
    }

    #[Override]
    protected function run($argument): void {
        $this->status->record($this->reconciliation->reconcileAll());
    }
}
