<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Permission;

use OCA\LocalBase\Organization\AdOrganizationDefinition;

interface CalendarPermissionSourceInterface {
    public function definition(): AdOrganizationDefinition;
    public function peerGroups(): array;
}
