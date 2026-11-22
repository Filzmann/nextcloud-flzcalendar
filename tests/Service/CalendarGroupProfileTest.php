<?php

declare(strict_types=1);


use OCA\FlzCalendar\Service\CalendarGroupProfile;
use OCA\FlzCalendar\Service\CalendarOrganization;
use OCA\FlzCalendar\Service\OrganizationRuntime;
use OCA\LocalBase\Organization\FlzOrganizationDefinition;

$definition = new CalendarOrganization(FlzOrganizationDefinition::defaults()->toArray());
$organization = new class($definition) extends OrganizationRuntime {
    public function __construct(private CalendarOrganization $value) {}
    public function organization(): ?CalendarOrganization { return $this->value; }
};
$profiles = new CalendarGroupProfile($organization);
$pfk = $profiles->get(['PFK', 'Bereich-Sued']);
if ($pfk['areas'] !== [] || $pfk['clusters'] !== ['PFK']) throw new RuntimeException('PFK darf keinem Buerobereich zugeordnet werden.');
$care = $profiles->get(['PFK', 'Bueroorganisation-Pflege', 'StvPDL']);
if ($care['roles'] !== ['StvPDL', 'Bueroorganisation-Pflege', 'PFK'] || $care['areas'] !== []) throw new RuntimeException('Stv. PDL steht im globalen Pflegeblock nicht an erster Stelle.');
$staff = $profiles->get(['Stab-HR', 'Bereich-West']);
if ($staff['areas'] !== [] || $staff['clusters'] !== ['Stab-HR']) throw new RuntimeException('Stabsstellen duerfen keinem Buerobereich zugeordnet werden.');
$office = $profiles->get(['Buero', 'Bereich-Nordost']);
if ($office['areas'] !== ['Bereich-Nordost'] || $office['clusters'] !== ['Buero#Bereich-Nordost']) throw new RuntimeException('BO-Bereich wurde nicht dynamisch kombiniert.');
$eb = $profiles->get(['EB', 'Bereich-West']);
if ($eb['areas'] !== ['Bereich-West']) throw new RuntimeException('EB-Bereich wurde nicht uebernommen.');
$blNow = $profiles->get(['BL', 'Bereich-Nordost', 'Bereich-West']);
if ($blNow['roles'] !== ['BL'] || $blNow['clusters'] !== ['BL#Bereich-Nordost', 'BL#Bereich-West']) throw new RuntimeException('BL NOW muss dynamisch in BL-NO und BL-W gefunden werden.');
$blSouth = $profiles->get(['BL', 'Bereich-Sued']);
if ($blSouth['clusters'] !== ['BL#Bereich-Sued']) throw new RuntimeException('BL Sued muss ihrem eigenen Buerobereich zugeordnet bleiben.');
$deputyNortheast = $profiles->get(['StvBL', 'EB', 'Bereich-Nordost']);
if ($deputyNortheast['roles'] !== ['StvBL', 'EB'] || $deputyNortheast['clusters'] !== ['StvBL#Bereich-Nordost', 'EB#Bereich-Nordost']) {
    throw new RuntimeException('Stellvertretende BL muss mit beiden Rollen dem passenden Buerobereich zugeordnet werden.');
}
$officeEb = $profiles->get(['Buero', 'EB', 'Bereich-West']);
if ($officeEb['roles'] !== ['EB', 'Buero'] || $officeEb['clusters'] !== ['EB#Bereich-West', 'Buero#Bereich-West']) {
    throw new RuntimeException('Kalenderprofile muessen der administrativen Organisationsreihenfolge folgen.');
}

echo "CalendarGroupProfileTest: OK\n";
