<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\AppInfo;

use OCA\FlzCalendar\CalendarSync\NextcloudDavShiftCalendarPublisher;
use OCA\FlzCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\FlzCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\FlzCalendar\Listener\IntegrationCapabilityQueryListener;
use OCA\FlzCalendar\Listener\ScheduleConflictQueryListener;
use OCA\FlzCalendar\Listener\StandaloneNavigationListener;
use OCA\FlzCalendar\Privacy\CalendarProcessingMetadataProviderListener;
use OCA\FlzCalendar\Privacy\CalendarPrivacyProviderListener;
use OCA\FlzCalendar\Permission\CalendarPermissionProviderListener;
use OCA\FlzCalendar\Permission\CalendarPermissionSourceInterface;
use OCA\FlzCalendar\Permission\NextcloudCalendarPermissionSource;
use OCA\FlzCalendar\Repository\TemporaryAdminAccessRepository;
use OCA\FlzCalendar\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\FlzCalendar\Service\TemporaryAdminAccessChecker;
use OCA\FlzCalendar\Service\TemporaryAdminAccessService;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
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
        $context->registerEventListener(ScheduleConflictQueryEvent::class, ScheduleConflictQueryListener::class);
        $context->registerEventListener(IntegrationCapabilityQueryEvent::class, IntegrationCapabilityQueryListener::class);
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, CalendarPrivacyProviderListener::class);
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, CalendarProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, CalendarPermissionProviderListener::class);
        $context->registerServiceAlias(CalendarPermissionSourceInterface::class, NextcloudCalendarPermissionSource::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
    }
    public function boot(IBootContext $context): void {}
}
