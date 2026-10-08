<?php

declare(strict_types=1);


use OCA\FlzCalendar\Service\CalendarGroupProfile;

$profiles = new CalendarGroupProfile();
$pfk = $profiles->get(['flz-PFK', 'flz-Bereich-Sued']);
if ($pfk['areas'] !== [] || $pfk['clusters'] !== ['flz-PFK']) throw new RuntimeException('PFK darf keinem Buerobereich zugeordnet werden.');
$care = $profiles->get(['flz-PFK', 'flz-Bueroorganisation-Pflege', 'flz-StvPDL']);
if ($care['roles'] !== ['flz-StvPDL', 'flz-Bueroorganisation-Pflege', 'flz-PFK'] || $care['areas'] !== []) throw new RuntimeException('Stv. PDL steht im globalen Pflegeblock nicht an erster Stelle.');
$staff = $profiles->get(['flz-Stab-HR', 'flz-Bereich-West']);
if ($staff['areas'] !== [] || $staff['clusters'] !== ['flz-Stab-HR']) throw new RuntimeException('Stabsstellen duerfen keinem Buerobereich zugeordnet werden.');
$office = $profiles->get(['flz-Buero', 'flz-Bereich-Nordost']);
if ($office['areas'] !== ['flz-Bereich-Nordost'] || $office['clusters'] !== ['flz-Buero#flz-Bereich-Nordost']) throw new RuntimeException('BO-Bereich wurde nicht dynamisch kombiniert.');
$eb = $profiles->get(['flz-EB', 'flz-Bereich-West']);
if ($eb['areas'] !== ['flz-Bereich-West']) throw new RuntimeException('EB-Bereich wurde nicht uebernommen.');
$blNow = $profiles->get(['flz-BL', 'flz-Bereich-Nordost', 'flz-Bereich-West']);
if ($blNow['roles'] !== ['flz-BL'] || $blNow['clusters'] !== ['flz-BL#flz-Bereich-Nordost', 'flz-BL#flz-Bereich-West']) throw new RuntimeException('BL NOW muss dynamisch in BL-NO und BL-W gefunden werden.');
$blSouth = $profiles->get(['flz-BL', 'flz-Bereich-Sued']);
if ($blSouth['clusters'] !== ['flz-BL#flz-Bereich-Sued']) throw new RuntimeException('BL Sued muss ihrem eigenen Buerobereich zugeordnet bleiben.');
$deputyNortheast = $profiles->get(['flz-StvBL', 'flz-EB', 'flz-Bereich-Nordost']);
if ($deputyNortheast['roles'] !== ['flz-StvBL', 'flz-EB'] || $deputyNortheast['clusters'] !== ['flz-StvBL#flz-Bereich-Nordost', 'flz-EB#flz-Bereich-Nordost']) {
    throw new RuntimeException('Stellvertretende BL muss mit beiden Rollen dem passenden Buerobereich zugeordnet werden.');
}
$officeEb = $profiles->get(['flz-Buero', 'flz-EB', 'flz-Bereich-West']);
if ($officeEb['roles'] !== ['flz-EB', 'flz-Buero'] || $officeEb['clusters'] !== ['flz-EB#flz-Bereich-West', 'flz-Buero#flz-Bereich-West']) {
    throw new RuntimeException('Kalenderprofile muessen der administrativen Organisationsreihenfolge folgen.');
}

echo "CalendarGroupProfileTest: OK\n";
