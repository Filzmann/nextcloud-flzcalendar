<?php

declare(strict_types=1);

use OCA\FlzCalendar\Permission\NextcloudCalendarPermissionSource;
use OCA\FlzCalendar\Service\CalendarOrganization;
use OCA\FlzCalendar\Service\CalendarSettingsService;
use OCA\FlzCalendar\Service\OrganizationRuntime;
use OCA\LocalBase\Organization\FlzOrganizationDefinition;

$definition = new CalendarOrganization(FlzOrganizationDefinition::defaults()->toArray());
$organization = new class($definition) extends OrganizationRuntime {
    /** @var list<string> */
    public array $groups = ['Buero', 'EB', 'PFK'];
    public function __construct(private CalendarOrganization $value) {}
    public function organization(): ?CalendarOrganization { return $this->value; }
    public function peerGroups(): array { return $this->groups; }
};
$calendarSettings = new CalendarSettingsService($organization);
$source = new NextcloudCalendarPermissionSource($organization);

$defaults = $source->peerGroups();
foreach (['Buero', 'EB', 'PFK'] as $enabledByDefault) {
    if (!in_array($enabledByDefault, $defaults, true)) {
        throw new RuntimeException('Der Kalender konsumiert den freigegebenen Peerdefault nicht: ' . $enabledByDefault);
    }
}
foreach (['Stab-HR', 'Stab-QMB'] as $disabledByDefault) {
    if (in_array($disabledByDefault, $defaults, true)) {
        throw new RuntimeException('Der Kalender aktiviert eine standardmäßig ausgeschaltete Peerrolle: ' . $disabledByDefault);
    }
}

$organization->groups = ['EB', 'PFK', 'Stab-HR'];
$configured = $source->peerGroups();
if (in_array('Buero', $configured, true)
    || !in_array('EB', $configured, true)
    || !in_array('PFK', $configured, true)
    || !in_array('Stab-HR', $configured, true)
    || in_array('Stab-QMB', $configured, true)) {
    throw new RuntimeException('Der Kalender respektiert explizite Peerentscheidungen aus flz_organization V1 nicht vollständig.');
}
$projection = $calendarSettings->peerEditing();
if (($projection['Buero'] ?? null) !== false
    || ($projection['EB'] ?? null) !== true
    || ($projection['PFK'] ?? null) !== true
    || ($projection['Stab-HR'] ?? null) !== true
    || ($projection['Stab-QMB'] ?? null) !== false) {
    throw new RuntimeException('Die read-only Peerprojektion entspricht nicht dem öffentlichen Autorisierungsvertrag.');
}

echo "CalendarPeerSettingsConsumerContractTest: OK\n";
