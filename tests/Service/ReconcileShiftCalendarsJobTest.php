<?php

declare(strict_types=1);

namespace OCP\AppFramework\Utility { interface ITimeFactory {} }
namespace OCP\BackgroundJob {
    interface IJob { public const TIME_SENSITIVE = 1; public const TIME_INSENSITIVE = 0; }
    abstract class TimedJob implements IJob {
        public int $interval = 0;
        public int $sensitivity = self::TIME_SENSITIVE;
        public bool $allowParallel = true;
        public function __construct(protected \OCP\AppFramework\Utility\ITimeFactory $time) {}
        public function setInterval(int $seconds): void { $this->interval = $seconds; }
        public function setTimeSensitivity(int $sensitivity): void { $this->sensitivity = $sensitivity; }
        public function setAllowParallelRuns(bool $allow): void { $this->allowParallel = $allow; }
        public function trigger(): void { $this->run(null); }
        abstract protected function run($argument): void;
    }
}
namespace OCA\AdCalendar\Service {
    final class ShiftCalendarReconciliationService {
        public int $calls = 0;
        public function reconcileAll(): array { $this->calls++; return ['attempted' => 2, 'succeeded' => 1, 'failed' => 1]; }
    }
    final class ShiftCalendarReconciliationStatusService {
        public array $recorded = [];
        public function record(array $result): void { $this->recorded[] = $result; }
    }
}

namespace {

    use OCA\AdCalendar\BackgroundJob\ReconcileShiftCalendarsJob;
    use OCA\AdCalendar\Service\ShiftCalendarReconciliationService;
    use OCA\AdCalendar\Service\ShiftCalendarReconciliationStatusService;
    use OCP\AppFramework\Utility\ITimeFactory;
    use OCP\BackgroundJob\IJob;

    $time = new class implements ITimeFactory {};
    $reconciliation = new ShiftCalendarReconciliationService();
    $status = new ShiftCalendarReconciliationStatusService();
    $job = new ReconcileShiftCalendarsJob($time, $reconciliation, $status);
    if ($job->interval !== 15 * 60 || $job->sensitivity !== IJob::TIME_INSENSITIVE || $job->allowParallel) {
        throw new RuntimeException('DAV-Abgleich ist nicht als nicht-paralleler, zeitunkritischer 15-Minuten-Job konfiguriert.');
    }
    $job->trigger();
    if ($reconciliation->calls !== 1) throw new RuntimeException('Background-Job startet den vollständigen DAV-Abgleich nicht.');
    if ($status->recorded !== [['attempted' => 2, 'succeeded' => 1, 'failed' => 1]]) throw new RuntimeException('Background-Job schreibt keinen aggregierten DAV-Laufstatus.');

    $info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
    if ($info === false || !str_contains($info, '<job>OCA\AdCalendar\BackgroundJob\ReconcileShiftCalendarsJob</job>')) {
        throw new RuntimeException('DAV-Abgleich ist nicht im Nextcloud-App-Manifest registriert.');
    }

    echo "ReconcileShiftCalendarsJobTest: OK\n";
}
