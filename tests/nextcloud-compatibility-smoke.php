<?php

declare(strict_types=1);

use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\Service\TemporaryAdminAccessService;

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
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
