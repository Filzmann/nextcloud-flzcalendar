<?php

declare(strict_types=1);


use OCA\AdCalendar\Service\CalendarGroupProfile;

$profiles = new CalendarGroupProfile();
$pfk = $profiles->get(['ad-PFK', 'ad-Bereich-Sued']);
if ($pfk['areas'] !== [] || $pfk['clusters'] !== ['ad-PFK']) throw new RuntimeException('PFK darf keinem Buerobereich zugeordnet werden.');
$care = $profiles->get(['ad-PFK', 'ad-Bueroorganisation-Pflege', 'ad-StvPDL']);
if ($care['roles'] !== ['ad-StvPDL', 'ad-Bueroorganisation-Pflege', 'ad-PFK'] || $care['areas'] !== []) throw new RuntimeException('Stv. PDL steht im globalen Pflegeblock nicht an erster Stelle.');
$staff = $profiles->get(['ad-Stab-HR', 'ad-Bereich-West']);
if ($staff['areas'] !== [] || $staff['clusters'] !== ['ad-Stab-HR']) throw new RuntimeException('Stabsstellen duerfen keinem Buerobereich zugeordnet werden.');
$office = $profiles->get(['ad-Buero', 'ad-Bereich-Nordost']);
if ($office['areas'] !== ['ad-Bereich-Nordost'] || $office['clusters'] !== ['ad-Buero#ad-Bereich-Nordost']) throw new RuntimeException('BO-Bereich wurde nicht dynamisch kombiniert.');
$eb = $profiles->get(['ad-EB', 'ad-Bereich-West']);
if ($eb['areas'] !== ['ad-Bereich-West']) throw new RuntimeException('EB-Bereich wurde nicht uebernommen.');
$blNow = $profiles->get(['ad-BL', 'ad-Bereich-Nordost', 'ad-Bereich-West']);
if ($blNow['roles'] !== ['ad-BL'] || $blNow['clusters'] !== ['ad-BL#ad-Bereich-Nordost', 'ad-BL#ad-Bereich-West']) throw new RuntimeException('BL NOW muss dynamisch in BL-NO und BL-W gefunden werden.');
$blSouth = $profiles->get(['ad-BL', 'ad-Bereich-Sued']);
if ($blSouth['clusters'] !== ['ad-BL#ad-Bereich-Sued']) throw new RuntimeException('BL Sued muss ihrem eigenen Buerobereich zugeordnet bleiben.');
$deputyNortheast = $profiles->get(['ad-StvBL', 'ad-EB', 'ad-Bereich-Nordost']);
if ($deputyNortheast['roles'] !== ['ad-StvBL', 'ad-EB'] || $deputyNortheast['clusters'] !== ['ad-StvBL#ad-Bereich-Nordost', 'ad-EB#ad-Bereich-Nordost']) {
    throw new RuntimeException('Stellvertretende BL muss mit beiden Rollen dem passenden Buerobereich zugeordnet werden.');
}
$officeEb = $profiles->get(['ad-Buero', 'ad-EB', 'ad-Bereich-West']);
if ($officeEb['roles'] !== ['ad-EB', 'ad-Buero'] || $officeEb['clusters'] !== ['ad-EB#ad-Bereich-West', 'ad-Buero#ad-Bereich-West']) {
    throw new RuntimeException('Kalenderprofile muessen der administrativen Organisationsreihenfolge folgen.');
}

echo "CalendarGroupProfileTest: OK\n";
