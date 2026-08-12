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
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
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
        $context->registerEventListener(PersonalDataProviderRegistryEvent::class, CalendarPrivacyProviderListener::class);
        $context->registerEventListener(LoadAdditionalEntriesEvent::class, StandaloneNavigationListener::class);
    }
    public function boot(IBootContext $context): void {}
}
