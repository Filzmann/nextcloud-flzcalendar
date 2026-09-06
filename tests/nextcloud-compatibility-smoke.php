<?php

declare(strict_types=1);

use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\Service\TemporaryAdminAccessService;

return [
    'uiPath' => '/index.php/apps/adcalendar/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => TemporaryAdminAccessService::class,
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(CalendarAccessService::class)
        ->canManage($uid),
    'apiSmokes' => [
        ['/index.php/apps/adcalendar/api/week?start=2035-01-01', [200]],
    ],
];
