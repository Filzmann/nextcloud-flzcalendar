<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCP\AppFramework\Utility\ITimeFactory;
use RuntimeException;

/** Zweck: Begrenzt Urlaubsabfragen auf das laufende und die zwei folgenden fachlichen Kalenderjahre. */
final class CalendarSyncHorizon {
    public function __construct(
        private OrganizationRuntime $organization,
        private ITimeFactory $time,
    ) {}

    /** @return array{DateTimeImmutable,DateTimeImmutable} */
    public function range(): array {
        $timezone = $this->organization->calendarContext()?->timezone();
        if (!is_string($timezone) || $timezone === '')
        {
            throw new RuntimeException('Der Organisations-Kalenderkontext ist nicht verfügbar.');
        }
        $now = (new DateTimeImmutable('@' . $this->time->getTime()))->setTimezone(new DateTimeZone($timezone));
        $start = $now->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0);

        return [$start, $start->modify('+3 years')];
    }
}
