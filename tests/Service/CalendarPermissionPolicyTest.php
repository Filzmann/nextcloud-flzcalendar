<?php

declare(strict_types=1);


use OCA\AdCalendar\Service\CalendarHierarchyPolicy;
use OCA\AdCalendar\Service\CalendarPermissionPolicy;

$policy = new CalendarPermissionPolicy(new CalendarHierarchyPolicy());
$assert = static function (bool $expected, bool $actual, string $message): void { if ($expected !== $actual) throw new RuntimeException($message); };

$assert(true, $policy->canManage('a', false, [], 'a', []), 'Eigene Eintraege muessen bearbeitbar sein.');
$assert(true, $policy->canManage('pdl', false, ['ad-PDL'], 'pfk', ['ad-PFK']), 'PDL muss PFK bearbeiten duerfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['ad-StvPDL'], 'pfk', ['ad-PFK']), 'Stv. PDL muss PFK bearbeiten dürfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['ad-StvPDL'], 'pflegebuero', ['ad-Bueroorganisation-Pflege']), 'Stv. PDL muss Büroorganisation Pflege bearbeiten dürfen.');
$assert(true, $policy->canManage('gf-digi', false, ['ad-GF-Digi'], 'fuhrpark', ['ad-Fahrzeugverwaltung']), 'GF-Digi muss Fahrzeugverwaltung bearbeiten dürfen.');
$assert(true, $policy->canManage('sekretariat', false, ['ad-Sekretariat'], 'empfang', ['ad-Empfang']), 'Sekretariat muss Empfang bearbeiten dürfen.');
$assert(false, $policy->canManage('pdl', false, ['ad-PDL'], 'eb', ['ad-EB', 'ad-Bereich-West']), 'PDL darf EB nicht bearbeiten.');
$assert(true, $policy->canManage('bl', false, ['ad-BL', 'ad-Bereich-Nordost', 'ad-Bereich-West'], 'eb', ['ad-EB', 'ad-Bereich-West']), 'Gemeinsame BL muss West bearbeiten duerfen.');
$assert(true, $policy->canManage('stv', false, ['ad-StvBL', 'ad-Bereich-Nordost'], 'buero', ['ad-Buero', 'ad-Bereich-Nordost']), 'StvBL muss eigenen Bereich bearbeiten duerfen.');
$assert(false, $policy->canManage('stv', false, ['ad-StvBL', 'ad-Bereich-Nordost'], 'buero', ['ad-Buero', 'ad-Bereich-West']), 'StvBL darf fremden Bereich nicht bearbeiten.');
$assert(false, $policy->canManage('bl', false, ['ad-BL', 'ad-Bereich-Sued'], 'hr', ['ad-Stab-HR']), 'BL darf Stab ohne Delegation nicht bearbeiten.');
$assert(true, $policy->canManage('admin', true, [], 'hr', ['ad-Stab-HR']), 'Admin muss alle bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['ad-Buero'], 'bo-b', ['ad-Buero']), 'Peer-Bearbeitung muss standardmaessig aus sein.');
$assert(true, $policy->canManage('bo-a', false, ['ad-Buero', 'ad-Bereich-Sued'], 'bo-b', ['ad-Buero', 'ad-Bereich-Sued'], ['ad-Buero']), 'Aktivierte BO-Peers im selben Buero muessen einander bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['ad-Buero', 'ad-Bereich-Sued'], 'bo-b', ['ad-Buero', 'ad-Bereich-Nordost'], ['ad-Buero']), 'BO-Peer-Recht darf kein anderes Buero oeffnen.');
$assert(false, $policy->canManage('eb-a', false, ['ad-EB'], 'eb-b', ['ad-EB'], ['ad-EB']), 'Bereichsgebundene Peers ohne gemeinsames Buero duerfen einander nicht bearbeiten.');
$assert(true, $policy->canManage('pfk-a', false, ['ad-PFK'], 'pfk-b', ['ad-PFK'], ['ad-PFK']), 'PFK-Peer-Recht bleibt mangels Buerobereich fachgruppenweit.');
$assert(false, $policy->canManage('pfk', false, ['ad-PFK'], 'bo', ['ad-Buero'], ['ad-PFK']), 'Peer-Recht darf keine andere Zielgruppe oeffnen.');
$assert(false, $policy->canManage('eb', false, ['ad-EB', 'ad-Bereich-Sued'], 'stv', ['ad-EB', 'ad-StvBL', 'ad-Bereich-Sued'], ['ad-EB']), 'EB darf StvBL trotz gemeinsamer EB-Rolle nicht bearbeiten.');
$assert(false, $policy->canManage('pfk', false, ['ad-PFK'], 'pdl', ['ad-PDL']), 'PFK darf PDL nicht bearbeiten.');
$assert(false, $policy->canManage('hr', false, ['ad-Stab-HR'], 'gf', ['ad-GF-AS']), 'HR darf GF-AS nicht bearbeiten.');
$assert(true, $policy->canManage('gf', false, ['ad-GF-AS'], 'pfk', ['ad-PFK']), 'GF-AS muss indirekt PFK bearbeiten duerfen.');
$assert(true, $policy->canManage('gf', false, ['ad-GF-Digi'], 'it', ['ad-IT']), 'GF-Digi muss indirekt IT bearbeiten duerfen.');
$assert(false, $policy->canManage('gf-as', false, ['ad-GF-AS'], 'it', ['ad-IT']), 'GF-AS darf IT nicht ohne Zuordnung bearbeiten.');
$assert(true, $policy->canManage('asdgf', false, ['ad-AsdGF-Digi'], 'it', ['ad-IT']), 'Assistenz GF-Digi muss IT bearbeiten duerfen.');
$assert(true, $policy->canManage('finlead', false, ['ad-Leitung-Finanzen-Lohn'], 'fin', ['ad-Finanzen-Lohn']), 'Leitung Finanzen und Lohn muss den Bereich bearbeiten duerfen.');
$assert(true, $policy->canManage('gf-as', false, ['ad-GF-AS'], 'sek', ['ad-Sekretariat']), 'GF-AS muss Sekretariat bearbeiten duerfen.');
$assert(true, $policy->canManage('gf-digi', false, ['ad-GF-Digi'], 'sek', ['ad-Sekretariat']), 'GF-Digi muss Sekretariat bearbeiten duerfen.');

echo "CalendarPermissionPolicyTest: OK\n";
