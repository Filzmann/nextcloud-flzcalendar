<?php

declare(strict_types=1);


use OCA\FlzCalendar\Service\CalendarHierarchyPolicy;
use OCA\FlzCalendar\Service\CalendarPermissionPolicy;

$policy = new CalendarPermissionPolicy(new CalendarHierarchyPolicy());
$assert = static function (bool $expected, bool $actual, string $message): void { if ($expected !== $actual) throw new RuntimeException($message); };

$assert(true, $policy->canManage('a', false, [], 'a', []), 'Eigene Eintraege muessen bearbeitbar sein.');
$assert(true, $policy->canManage('pdl', false, ['flz-PDL'], 'pfk', ['flz-PFK']), 'PDL muss PFK bearbeiten duerfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['flz-StvPDL'], 'pfk', ['flz-PFK']), 'Stv. PDL muss PFK bearbeiten dürfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['flz-StvPDL'], 'pflegebuero', ['flz-Bueroorganisation-Pflege']), 'Stv. PDL muss Büroorganisation Pflege bearbeiten dürfen.');
$assert(true, $policy->canManage('gf-digi', false, ['flz-GF-Digi'], 'fuhrpark', ['flz-Fahrzeugverwaltung']), 'GF-Digi muss Fahrzeugverwaltung bearbeiten dürfen.');
$assert(true, $policy->canManage('sekretariat', false, ['flz-Sekretariat'], 'empfang', ['flz-Empfang']), 'Sekretariat muss Empfang bearbeiten dürfen.');
$assert(false, $policy->canManage('pdl', false, ['flz-PDL'], 'eb', ['flz-EB', 'flz-Bereich-West']), 'PDL darf EB nicht bearbeiten.');
$assert(true, $policy->canManage('bl', false, ['flz-BL', 'flz-Bereich-Nordost', 'flz-Bereich-West'], 'eb', ['flz-EB', 'flz-Bereich-West']), 'Gemeinsame BL muss West bearbeiten duerfen.');
$assert(true, $policy->canManage('stv', false, ['flz-StvBL', 'flz-Bereich-Nordost'], 'buero', ['flz-Buero', 'flz-Bereich-Nordost']), 'StvBL muss eigenen Bereich bearbeiten duerfen.');
$assert(false, $policy->canManage('stv', false, ['flz-StvBL', 'flz-Bereich-Nordost'], 'buero', ['flz-Buero', 'flz-Bereich-West']), 'StvBL darf fremden Bereich nicht bearbeiten.');
$assert(false, $policy->canManage('bl', false, ['flz-BL', 'flz-Bereich-Sued'], 'hr', ['flz-Stab-HR']), 'BL darf Stab ohne Delegation nicht bearbeiten.');
$assert(true, $policy->canManage('admin', true, [], 'hr', ['flz-Stab-HR']), 'Admin muss alle bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['flz-Buero'], 'bo-b', ['flz-Buero']), 'Peer-Bearbeitung muss standardmaessig aus sein.');
$assert(true, $policy->canManage('bo-a', false, ['flz-Buero', 'flz-Bereich-Sued'], 'bo-b', ['flz-Buero', 'flz-Bereich-Sued'], ['flz-Buero']), 'Aktivierte BO-Peers im selben Buero muessen einander bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['flz-Buero', 'flz-Bereich-Sued'], 'bo-b', ['flz-Buero', 'flz-Bereich-Nordost'], ['flz-Buero']), 'BO-Peer-Recht darf kein anderes Buero oeffnen.');
$assert(false, $policy->canManage('eb-a', false, ['flz-EB'], 'eb-b', ['flz-EB'], ['flz-EB']), 'Bereichsgebundene Peers ohne gemeinsames Buero duerfen einander nicht bearbeiten.');
$assert(true, $policy->canManage('pfk-a', false, ['flz-PFK'], 'pfk-b', ['flz-PFK'], ['flz-PFK']), 'PFK-Peer-Recht bleibt mangels Buerobereich fachgruppenweit.');
$assert(false, $policy->canManage('pfk', false, ['flz-PFK'], 'bo', ['flz-Buero'], ['flz-PFK']), 'Peer-Recht darf keine andere Zielgruppe oeffnen.');
$assert(false, $policy->canManage('eb', false, ['flz-EB', 'flz-Bereich-Sued'], 'stv', ['flz-EB', 'flz-StvBL', 'flz-Bereich-Sued'], ['flz-EB']), 'EB darf StvBL trotz gemeinsamer EB-Rolle nicht bearbeiten.');
$assert(false, $policy->canManage('pfk', false, ['flz-PFK'], 'pdl', ['flz-PDL']), 'PFK darf PDL nicht bearbeiten.');
$assert(false, $policy->canManage('hr', false, ['flz-Stab-HR'], 'gf', ['flz-GF-AS']), 'HR darf GF-AS nicht bearbeiten.');
$assert(true, $policy->canManage('gf', false, ['flz-GF-AS'], 'pfk', ['flz-PFK']), 'GF-AS muss indirekt PFK bearbeiten duerfen.');
$assert(true, $policy->canManage('gf', false, ['flz-GF-Digi'], 'it', ['flz-IT']), 'GF-Digi muss indirekt IT bearbeiten duerfen.');
$assert(false, $policy->canManage('gf-as', false, ['flz-GF-AS'], 'it', ['flz-IT']), 'GF-AS darf IT nicht ohne Zuordnung bearbeiten.');
$assert(true, $policy->canManage('asdgf', false, ['flz-AsdGF-Digi'], 'it', ['flz-IT']), 'Assistenz GF-Digi muss IT bearbeiten duerfen.');
$assert(true, $policy->canManage('finlead', false, ['flz-Leitung-Finanzen-Lohn'], 'fin', ['flz-Finanzen-Lohn']), 'Leitung Finanzen und Lohn muss den Bereich bearbeiten duerfen.');
$assert(true, $policy->canManage('gf-as', false, ['flz-GF-AS'], 'sek', ['flz-Sekretariat']), 'GF-AS muss Sekretariat bearbeiten duerfen.');
$assert(true, $policy->canManage('gf-digi', false, ['flz-GF-Digi'], 'sek', ['flz-Sekretariat']), 'GF-Digi muss Sekretariat bearbeiten duerfen.');

echo "CalendarPermissionPolicyTest: OK\n";
