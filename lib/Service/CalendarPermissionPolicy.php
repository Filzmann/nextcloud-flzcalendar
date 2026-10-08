<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\LocalBase\Organization\FlzOrganizationPermissionPolicy;

/** Zweck: Bewertet die fachliche Schreibmatrix ohne Nextcloud-Infrastruktur. */
final class CalendarPermissionPolicy extends FlzOrganizationPermissionPolicy {
    public function __construct(CalendarHierarchyPolicy $hierarchy) { parent::__construct($hierarchy); }
}
