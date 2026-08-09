<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use DateTimeImmutable;
use OCA\LocalBase\Calendar\CalendarContextSettingsService;
use OCP\AppFramework\Utility\ITimeFactory;

/** Zweck: Begrenzt Urlaubsabfragen auf das laufende und die zwei folgenden fachlichen Kalenderjahre. */
final class CalendarSyncHorizon {
    public function __construct(
        private CalendarContextSettingsService $calendarContext,
        private ITimeFactory $time,
    ) {}

    /** @return array{DateTimeImmutable,DateTimeImmutable} */
    public function range(): array {
        $timezone = $this->calendarContext->context()->timezone();
        $now = (new DateTimeImmutable('@' . $this->time->getTime()))->setTimezone($timezone);
        $start = $now->setDate((int)$now->format('Y'), 1, 1)->setTime(0, 0);

        return [$start, $start->modify('+3 years')];
    }
}
