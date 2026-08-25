<?php

declare(strict_types=1);

namespace OCA\AdCalendar\AppInfo;

use OCA\AdCalendar\CalendarSync\NextcloudDavShiftCalendarPublisher;
use OCA\AdCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\AdCalendar\CalendarSync\ShiftCalendarPublisher;
use OCA\AdCalendar\Listener\IntegrationCapabilityQueryListener;
use OCA\AdCalendar\Listener\ScheduleConflictQueryListener;
use OCA\AdCalendar\Listener\StandaloneNavigationListener;
use OCA\AdCalendar\Privacy\CalendarPrivacyProviderListener;
use OCA\AdCalendar\Permission\CalendarPermissionProviderListener;
use OCA\AdCalendar\Permission\CalendarPermissionSourceInterface;
use OCA\AdCalendar\Permission\NextcloudCalendarPermissionSource;
use OCA\AdCalendar\Repository\TemporaryAdminAccessRepository;
use OCA\AdCalendar\Repository\TemporaryAdminAccessRepositoryInterface;
use OCA\AdCalendar\Service\TemporaryAdminAccessChecker;
use OCA\AdCalendar\Service\TemporaryAdminAccessService;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
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
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, CalendarPermissionProviderListener::class);
        $context->registerServiceAlias(CalendarPermissionSourceInterface::class, NextcloudCalendarPermissionSource::class);
        $context->registerServiceAlias(TemporaryAdminAccessChecker::class, TemporaryAdminAccessService::class);
        $context->registerServiceAlias(TemporaryAdminAccessRepositoryInterface::class, TemporaryAdminAccessRepository::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
    }
    public function boot(IBootContext $context): void {}
}
