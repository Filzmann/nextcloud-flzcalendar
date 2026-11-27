<?php

declare(strict_types=1);

namespace OCP\AppFramework\Utility {
    interface ITimeFactory { public function getTime(): int; }
}

namespace {
    use OCA\FlzCalendar\Service\CalendarSyncHorizon;
    use OCA\FlzCalendar\Service\OrganizationRuntime;
    use OCP\AppFramework\Utility\ITimeFactory;

    $organization = new class extends OrganizationRuntime {
        public function __construct() {}
        public function calendarContext(): ?object {
            return new class { public function timezone(): string { return 'Europe/Berlin'; } };
        }
    };
    $time = new class implements ITimeFactory {
        public function getTime(): int { return 1786276800; } // 2026-08-09T12:00:00Z
    };

    [$start, $end] = (new CalendarSyncHorizon($organization, $time))->range();
    if ($start->format(DATE_ATOM) !== '2026-01-01T00:00:00+01:00'
        || $end->format(DATE_ATOM) !== '2029-01-01T00:00:00+01:00') {
        throw new RuntimeException('Der Urlaubsabgleich umfasst nicht das laufende und die zwei folgenden Kalenderjahre.');
    }

    echo "CalendarSyncHorizonTest: OK\n";
}
