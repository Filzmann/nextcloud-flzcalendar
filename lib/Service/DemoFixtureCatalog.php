<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

/** Builds neutral calendar demo identities from the public organization definition. */
final class DemoFixtureCatalog
{
    public function __construct(
        private OrganizationRuntime $organization,
        private ?CalendarOrganization $override = null,
    )
    {
    }

    /** @return list<array{uid:string,name:string,groups:list<string>}> */
    public function all(): array
    {
        $definition = $this->override ?? $this->organization->organization();
        if ($definition === null)
        {
            return [];
        }

        return array_map(static function (array $fixture) use ($definition): array
        {
            return [
                'uid' => $fixture['uid'],
                'name' => $fixture['name'],
                'groups' => array_values(array_filter([
                    ...array_map($definition->roleGroupId(...), $fixture['roles']),
                    ...array_map($definition->areaGroupId(...), $fixture['areas']),
                ])),
            ];
        }, self::fixtures());
    }

    /** @return list<array{uid:string,name:string,roles:list<string>,areas:list<string>}> */
    private static function fixtures(): array
    {
        return [
            ['uid' => 'flz-demo-gf-as', 'name' => 'Alma Adler (GF-AS)', 'roles' => ['gf_as'], 'areas' => []],
            ['uid' => 'flz-demo-gf-digi', 'name' => 'David Berger (GF-Digi)', 'roles' => ['gf_digi'], 'areas' => []],
            ['uid' => 'flz-demo-pdl', 'name' => 'Paula Lindner (PDL)', 'roles' => ['pdl'], 'areas' => []],
            ['uid' => 'flz-demo-stvpdl', 'name' => 'Pia Neumann (Stv. PDL)', 'roles' => ['deputy_pdl'], 'areas' => []],
            ['uid' => 'flz-demo-pflegebuero', 'name' => 'Bela Krämer (Büroorganisation Pflege)', 'roles' => ['care_office'], 'areas' => []],
            ['uid' => 'flz-demo-asdgf-digi', 'name' => 'Alexis Dorn (AsdGF-Digi)', 'roles' => ['assistant_gf_digi'], 'areas' => []],
            ['uid' => 'flz-demo-finanzleitung', 'name' => 'Leonie Frank (Leitung Finanzen und Lohn)', 'roles' => ['finance_lead'], 'areas' => []],
            ['uid' => 'flz-demo-finanzen', 'name' => 'Finn Lohmann (Finanzen)', 'roles' => ['finance'], 'areas' => []],
            ['uid' => 'flz-demo-lohn', 'name' => 'Luca Hoffmann (Lohn)', 'roles' => ['payroll'], 'areas' => []],
            ['uid' => 'flz-demo-it', 'name' => 'Imani Teich (IT)', 'roles' => ['it'], 'areas' => []],
            ['uid' => 'flz-demo-fahrzeugverwaltung', 'name' => 'Fatima Wagner (Fahrzeugverwaltung)', 'roles' => ['fleet_management'], 'areas' => []],
            ['uid' => 'flz-demo-sekretariat', 'name' => 'Samira König (Sekretariat)', 'roles' => ['secretariat'], 'areas' => []],
            ['uid' => 'flz-demo-empfang', 'name' => 'Eleni Schubert (Empfang)', 'roles' => ['reception'], 'areas' => []],
            ['uid' => 'flz-demo-hr', 'name' => 'Hanna Reuter (Stabsstelle HR)', 'roles' => ['staff_hr'], 'areas' => []],
            ['uid' => 'flz-demo-qmb', 'name' => 'Quinn Meyer (Stabsstelle Qualitätsmanagement)', 'roles' => ['staff_qmb'], 'areas' => []],
            ['uid' => 'flz-demo-bl-now', 'name' => 'Nora Winter (Büro Nordost und West, BL)', 'roles' => ['bl', 'office'], 'areas' => ['northeast', 'west']],
            ['uid' => 'flz-demo-bl-sued', 'name' => 'Sofia Kern (Büro Süd, BL)', 'roles' => ['bl', 'office'], 'areas' => ['south']],
            ['uid' => 'flz-demo-stvbl-no', 'name' => 'Nele Hartmann (EB Nordost, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-stvbl-west', 'name' => 'Wiebke Hahn (EB West, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['west']],
            ['uid' => 'flz-demo-stvbl-sued', 'name' => 'Sina Maurer (EB Süd, Stv. BL)', 'roles' => ['deputy_bl', 'eb'], 'areas' => ['south']],
            ['uid' => 'flz-demo-bo-no', 'name' => 'Mara Brandt (Büro Nordost)', 'roles' => ['office'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-bo-west', 'name' => 'Mika Werner (Büro West)', 'roles' => ['office'], 'areas' => ['west']],
            ['uid' => 'flz-demo-bo-sued', 'name' => 'Selin Krüger (Büro Süd)', 'roles' => ['office'], 'areas' => ['south']],
            ['uid' => 'flz-demo-eb-no', 'name' => 'Enna Busch (EB Nordost)', 'roles' => ['eb'], 'areas' => ['northeast']],
            ['uid' => 'flz-demo-eb-west', 'name' => 'Emil Weber (EB West)', 'roles' => ['eb'], 'areas' => ['west']],
            ['uid' => 'flz-demo-eb-sued', 'name' => 'Eda Sommer (EB Süd)', 'roles' => ['eb'], 'areas' => ['south']],
            ['uid' => 'flz-demo-pfk-a', 'name' => 'Petra Falk (PFK)', 'roles' => ['pfk'], 'areas' => []],
            ['uid' => 'flz-demo-pfk-b', 'name' => 'Robin Keller (PFK)', 'roles' => ['pfk'], 'areas' => []],
        ];
    }
}
