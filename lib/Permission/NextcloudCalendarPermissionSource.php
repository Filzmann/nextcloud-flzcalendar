<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Permission;

use OCA\AdCalendar\Service\CalendarSettingsService;
use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;

final class NextcloudCalendarPermissionSource implements CalendarPermissionSourceInterface {
    public function __construct(
        private AdOrganizationSettingsService $organization,
        private CalendarSettingsService $settings,
    ) {}

    public function definition(): AdOrganizationDefinition { return $this->organization->definition(); }
    public function peerGroups(): array { return $this->settings->enabledPeerGroups(); }
}
