<?php

declare(strict_types=1);


use OCA\FlzCalendar\Service\CalendarHierarchyPolicy;
use OCA\FlzCalendar\Service\CalendarPermissionPolicy;

$policy = new CalendarPermissionPolicy(new CalendarHierarchyPolicy());
$assert = static function (bool $expected, bool $actual, string $message): void { if ($expected !== $actual) throw new RuntimeException($message); };

$assert(true, $policy->canManage('a', false, [], 'a', []), 'Eigene Eintraege muessen bearbeitbar sein.');
$assert(true, $policy->canManage('pdl', false, ['PDL'], 'pfk', ['PFK']), 'PDL muss PFK bearbeiten duerfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['StvPDL'], 'pfk', ['PFK']), 'Stv. PDL muss PFK bearbeiten dürfen.');
$assert(true, $policy->canManage('stv-pdl', false, ['StvPDL'], 'pflegebuero', ['Bueroorganisation-Pflege']), 'Stv. PDL muss Büroorganisation Pflege bearbeiten dürfen.');
$assert(true, $policy->canManage('gf-digi', false, ['GF-Digi'], 'fuhrpark', ['Fahrzeugverwaltung']), 'GF-Digi muss Fahrzeugverwaltung bearbeiten dürfen.');
$assert(true, $policy->canManage('sekretariat', false, ['Sekretariat'], 'empfang', ['Empfang']), 'Sekretariat muss Empfang bearbeiten dürfen.');
$assert(false, $policy->canManage('pdl', false, ['PDL'], 'eb', ['EB', 'Bereich-West']), 'PDL darf EB nicht bearbeiten.');
$assert(true, $policy->canManage('bl', false, ['BL', 'Bereich-Nordost', 'Bereich-West'], 'eb', ['EB', 'Bereich-West']), 'Gemeinsame BL muss West bearbeiten duerfen.');
$assert(true, $policy->canManage('stv', false, ['StvBL', 'Bereich-Nordost'], 'buero', ['Buero', 'Bereich-Nordost']), 'StvBL muss eigenen Bereich bearbeiten duerfen.');
$assert(false, $policy->canManage('stv', false, ['StvBL', 'Bereich-Nordost'], 'buero', ['Buero', 'Bereich-West']), 'StvBL darf fremden Bereich nicht bearbeiten.');
$assert(false, $policy->canManage('bl', false, ['BL', 'Bereich-Sued'], 'hr', ['Stab-HR']), 'BL darf Stab ohne Delegation nicht bearbeiten.');
$assert(true, $policy->canManage('admin', true, [], 'hr', ['Stab-HR']), 'Admin muss alle bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['Buero'], 'bo-b', ['Buero']), 'Peer-Bearbeitung muss standardmaessig aus sein.');
$assert(true, $policy->canManage('bo-a', false, ['Buero', 'Bereich-Sued'], 'bo-b', ['Buero', 'Bereich-Sued'], ['Buero']), 'Aktivierte BO-Peers im selben Buero muessen einander bearbeiten duerfen.');
$assert(false, $policy->canManage('bo-a', false, ['Buero', 'Bereich-Sued'], 'bo-b', ['Buero', 'Bereich-Nordost'], ['Buero']), 'BO-Peer-Recht darf kein anderes Buero oeffnen.');
$assert(false, $policy->canManage('eb-a', false, ['EB'], 'eb-b', ['EB'], ['EB']), 'Bereichsgebundene Peers ohne gemeinsames Buero duerfen einander nicht bearbeiten.');
$assert(true, $policy->canManage('pfk-a', false, ['PFK'], 'pfk-b', ['PFK'], ['PFK']), 'PFK-Peer-Recht bleibt mangels Buerobereich fachgruppenweit.');
$assert(false, $policy->canManage('pfk', false, ['PFK'], 'bo', ['Buero'], ['PFK']), 'Peer-Recht darf keine andere Zielgruppe oeffnen.');
$assert(false, $policy->canManage('eb', false, ['EB', 'Bereich-Sued'], 'stv', ['EB', 'StvBL', 'Bereich-Sued'], ['EB']), 'EB darf StvBL trotz gemeinsamer EB-Rolle nicht bearbeiten.');
$assert(false, $policy->canManage('pfk', false, ['PFK'], 'pdl', ['PDL']), 'PFK darf PDL nicht bearbeiten.');
$assert(false, $policy->canManage('hr', false, ['Stab-HR'], 'gf', ['GF-AS']), 'HR darf GF-AS nicht bearbeiten.');
$assert(false, $policy->canManage('gf', false, ['GF-AS'], 'pfk', ['PFK']), 'Nur indirekt unterstellte Rollen dürfen nicht bearbeitet werden.');
$assert(true, $policy->canManage('gf', false, ['GF-AS'], 'pdl', ['PDL']), 'Direkt unterstellte Rollen müssen bearbeitbar sein.');
$assert(false, $policy->canManage('gf', false, ['GF-Digi'], 'it', ['IT']), 'GF-Digi darf die nur indirekt unterstellte IT nicht bearbeiten.');
$assert(false, $policy->canManage('gf-as', false, ['GF-AS'], 'it', ['IT']), 'GF-AS darf IT nicht ohne Zuordnung bearbeiten.');
$assert(true, $policy->canManage('asdgf', false, ['AsdGF-Digi'], 'it', ['IT']), 'Assistenz GF-Digi muss IT bearbeiten duerfen.');
$assert(true, $policy->canManage('finlead', false, ['Leitung-Finanzen-Lohn'], 'fin', ['Finanzen']), 'Leitung Finanzen und Lohn muss den Bereich bearbeiten duerfen.');
$assert(true, $policy->canManage('gf-as', false, ['GF-AS'], 'sek', ['Sekretariat']), 'GF-AS muss Sekretariat bearbeiten duerfen.');
$assert(true, $policy->canManage('gf-digi', false, ['GF-Digi'], 'sek', ['Sekretariat']), 'GF-Digi muss Sekretariat bearbeiten duerfen.');

echo "CalendarPermissionPolicyTest: OK\n";
