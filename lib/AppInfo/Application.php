<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\AppInfo;

use OCA\FlzCalendar\CalendarSync\NextcloudDavShiftCalendarPublisher;
use OCA\FlzCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\FlzCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\FlzCalendar\Listener\IntegrationCapabilityQueryListener;
use OCA\FlzCalendar\Listener\ScheduleConflictQueryListener;
use OCA\FlzCalendar\Listener\StandaloneNavigationListener;
use OCA\FlzCalendar\Permission\CalendarPermissionProviderListener;
use OCA\FlzCalendar\Permission\CalendarPermissionSourceInterface;
use OCA\FlzCalendar\Permission\NextcloudCalendarPermissionSource;
use OCA\FlzCalendar\Service\OrganizationHolidayCalendarService;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCA\LocalBase\Calendar\HolidayCalendarService;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** Zweck: Registriert Kalender-, Capability- und Standalone-Navigationsverträge im Nextcloud-Bootstrap. */
class Application extends App implements IBootstrap {
    public const APP_ID = AppId::VALUE;

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }
    public function register(IRegistrationContext $context): void {
        $context->registerServiceAlias(ShiftCalendarPublisher::class, NextcloudDavShiftCalendarPublisher::class);
        $context->registerServiceAlias(PersonalCalendarPublisher::class, NextcloudDavShiftCalendarPublisher::class);
        
        // Register OrganizationHolidayCalendarService as primary source for holidays
        // This adapts flz_organization as the canonical source with localbase fallback
        $context->registerService(
            OrganizationHolidayCalendarService::class,
            function ($c) {
                return new OrganizationHolidayCalendarService(
                    $c->get(IAppManager::class),
                    $c->get(HolidayCalendarService::class)
                );
            }
        );
        
        // Override the LocalBase HolidayCalendarService with our adapter
        // This ensures existing code that depends on HolidayCalendarService
        // will get the organization-aware version
        $context->registerServiceAlias(
            HolidayCalendarService::class,
            OrganizationHolidayCalendarService::class
        );
        
        $context->registerEventListener(ScheduleConflictQueryEvent::class, ScheduleConflictQueryListener::class);
        $context->registerEventListener(IntegrationCapabilityQueryEvent::class, IntegrationCapabilityQueryListener::class);
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, CalendarPrivacyProviderListener::class);
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, CalendarProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, CalendarPermissionProviderListener::class);
        $context->registerServiceAlias(CalendarPermissionSourceInterface::class, NextcloudCalendarPermissionSource::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
    }
    public function boot(IBootContext $context): void {}
}
