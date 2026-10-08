<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Permission;

use OCA\FlzCalendar\Service\CalendarSettingsService;
use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;

final class NextcloudCalendarPermissionSource implements CalendarPermissionSourceInterface {
    public function __construct(
        private FlzOrganizationSettingsService $organization,
        private CalendarSettingsService $settings,
    ) {}

    public function definition(): FlzOrganizationDefinition { return $this->organization->definition(); }
    public function peerGroups(): array { return $this->settings->enabledPeerGroups(); }
}
