<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Permission;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;

interface CalendarPermissionSourceInterface {
    public function definition(): FlzOrganizationDefinition;
    public function peerGroups(): array;
}
