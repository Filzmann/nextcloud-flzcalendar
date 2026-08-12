<?php

declare(strict_types=1);


use OCA\AdCalendar\Service\CalendarHierarchyPolicy;

$hierarchy = new CalendarHierarchyPolicy();
if (!$hierarchy->manages(['ad-GF-AS'], ['ad-BL', 'ad-Bereich-Nordost', 'ad-Bereich-West'])) throw new RuntimeException('GF-AS muss BL NOW fuehren.');
if (!$hierarchy->manages(['ad-GF-AS'], ['ad-Stab-HR'])) throw new RuntimeException('GF-AS muss HR fuehren.');
if (!$hierarchy->manages(['ad-GF-Digi'], ['ad-AsdGF-Digi'])) throw new RuntimeException('GF-Digi muss Assistenz Digitalisierung fuehren.');
if ($hierarchy->manages(['ad-GF-Digi'], ['ad-PFK'])) throw new RuntimeException('GF-Digi darf PFK nicht fuehren.');
if (!$hierarchy->targetIsSuperior(['ad-IT'], ['ad-AsdGF-Digi'])) throw new RuntimeException('Assistenz GF-Digi muss gegen IT-Peerzugriff geschuetzt sein.');

echo "CalendarHierarchyPolicyTest: OK\n";
