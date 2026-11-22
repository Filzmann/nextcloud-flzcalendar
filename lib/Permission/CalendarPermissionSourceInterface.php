<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Permission;

use OCA\FlzCalendar\Service\CalendarOrganization;

interface CalendarPermissionSourceInterface {
    public function definition(): ?CalendarOrganization;
    public function peerGroups(): array;
}
