<?php

declare(strict_types=1);


use OCA\FlzCalendar\Service\DemoFixtureCatalog;
use OCA\FlzCalendar\Service\CalendarOrganization;
use OCA\FlzCalendar\Service\OrganizationRuntime;
use OCA\FlzOrganization\Organization\OrganizationDefinition;

$runtime = static function (CalendarOrganization $definition): OrganizationRuntime {
    return new class($definition) extends OrganizationRuntime {
        public function __construct(private CalendarOrganization $value) {}
        public function organization(): ?CalendarOrganization { return $this->value; }
    };
};
$definition = OrganizationDefinition::defaults();
$fixtures = (new DemoFixtureCatalog($runtime(new CalendarOrganization($definition->toArray()))))->all();
if (count($fixtures) < 20) throw new RuntimeException('Der Demokatalog deckt die Organisationsstruktur nicht ausreichend ab.');
$requiredGroups = array_merge($definition->roleGroupIds(), $definition->areaGroupIds());
$covered = array_fill_keys(array_merge(...array_column($fixtures, 'groups')), true);
foreach ($requiredGroups as $group) {
    if (!isset($covered[$group])) throw new RuntimeException("Demodatensatz fuer {$group} fehlt.");
}
$custom = $definition->toArray();
$custom['roles']['office']['groupId'] = 'custom-office';
$customDefinition = new CalendarOrganization(OrganizationDefinition::get($custom)->toArray());
$customFixtures = (new DemoFixtureCatalog($runtime($customDefinition), $customDefinition))->all();
if (!in_array('custom-office', array_merge(...array_column($customFixtures, 'groups')), true)) throw new RuntimeException('Demodaten verwenden nicht die konfigurierte Gruppen-ID.');
$uids = [];
foreach ($fixtures as $fixture) {
    if (isset($uids[$fixture['uid']])) throw new RuntimeException("Doppelte Demo-UID: {$fixture['uid']}");
    $uids[$fixture['uid']] = true;
    if (preg_match('/^[^()]+ \([^)]+\)$/', $fixture['name']) !== 1) throw new RuntimeException("Demoperson ohne Name und Gruppenklammer: {$fixture['uid']}");
}

$source = file_get_contents(__DIR__ . '/../../lib/Command/SeedDemoCommand.php');
$service = file_get_contents(__DIR__ . '/../../lib/Service/CalendarDemoPackService.php');
if ($source === false || $service === false) throw new RuntimeException('Demo-Pack-Code konnte nicht gelesen werden.');
foreach (['CalendarDemoPackService', '->install()', 'IL10N', "l10n->t('Erzeugt vollständige Filzmann-Kalender-Demokonten und Demodaten.')", "l10n->t('%s Demokonten synchronisiert; Kalendereinträge für %s erzeugt, %s bereits vorhanden.'"] as $contract) {
    if (!str_contains($source, $contract)) throw new RuntimeException("Demo-Command delegiert nicht sicher: {$contract}");
}
foreach (['DemoAccountProvisioningService', '->provision(', 'existsCreatedByForEmployee', "'parentEntryId' => \$shiftId"] as $contract) {
    if (!str_contains($service, $contract)) throw new RuntimeException("Demo-Pack-Vertrag fehlt: {$contract}");
}
foreach (['IUserManager', 'ensureUser(', 'createGroup(', 'setDisplayName('] as $unsafeContract) {
    if (str_contains($source, $unsafeContract)) throw new RuntimeException("Demo-Command umgeht das sichere Provisioning: {$unsafeContract}");
}
echo "SeedDemoCommandSmokeTest: OK\n";
