<?php

declare(strict_types=1);


use OCA\FlzCalendar\Service\CalendarHierarchyPolicy;

$hierarchy = new CalendarHierarchyPolicy();
if (!$hierarchy->manages(['GF-AS'], ['BL', 'Bereich-Nordost', 'Bereich-West'])) throw new RuntimeException('GF-AS muss BL NOW fuehren.');
if (!$hierarchy->manages(['GF-AS'], ['Stab-HR'])) throw new RuntimeException('GF-AS muss HR fuehren.');
if (!$hierarchy->manages(['GF-Digi'], ['AsdGF-Digi'])) throw new RuntimeException('GF-Digi muss Assistenz Digitalisierung fuehren.');
if ($hierarchy->manages(['GF-Digi'], ['PFK'])) throw new RuntimeException('GF-Digi darf PFK nicht fuehren.');
if (!$hierarchy->targetIsSuperior(['IT'], ['AsdGF-Digi'])) throw new RuntimeException('Assistenz GF-Digi muss gegen IT-Peerzugriff geschuetzt sein.');

echo "CalendarHierarchyPolicyTest: OK\n";
