<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
use OCA\LocalBase\Organization\FlzSuiteAdminSettingsService;

/** Zweck: Stellt dem Kalender den gemeinsamen Organisations- und Rechtevertrag read-only bereit. */
final class CalendarSettingsService {
    public function __construct(
        private FlzSuiteAdminSettingsService $adminSettings,
        private ?FlzOrganizationSettingsService $organization = null,
    ) {}

    /** @return array<string,bool> */
    public function peerEditing(): array {
        return $this->adminSettings->calendarPeerEditing();
    }

    /** @return list<string> */
    public function enabledPeerGroups(): array { return $this->adminSettings->enabledCalendarPeerGroups(); }

    public function organization(): array { return $this->definition()->toArray(); }

    private function definition(): FlzOrganizationDefinition { return $this->organization?->definition() ?? FlzOrganizationDefinition::defaults(); }
}
