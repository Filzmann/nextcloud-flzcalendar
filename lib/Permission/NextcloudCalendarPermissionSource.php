<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Permission;

use OCA\FlzCalendar\Service\CalendarOrganization;
use OCA\FlzCalendar\Service\OrganizationRuntime;

final class NextcloudCalendarPermissionSource implements CalendarPermissionSourceInterface {
    public function __construct(
        private OrganizationRuntime $organization,
    )
    {
    }

    public function definition(): ?CalendarOrganization
    {
        return $this->organization->organization();
    }

    public function peerGroups(): array
    {
        return $this->organization->peerGroups();
    }
}
