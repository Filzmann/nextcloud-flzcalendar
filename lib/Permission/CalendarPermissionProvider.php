<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Permission;

use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionRule;

final class CalendarPermissionProvider implements PermissionProvider {
    public function __construct(private CalendarPermissionSourceInterface $source) {}

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor('flzcalendar', 'Filzmann Kalender', '1.0', ['permissions']);
    }

    public function collect(): PermissionProviderResult {
        $rules = [
            $this->rule('Kalendereintrag', 'Alle Einträge', 'Ausdrücklich entschiedene app-spezifische Ausnahme für native Nextcloud-Administration', 'calendar.entry.manage-all', 'Alle Einträge verwalten', 'all-entries', PermissionCondition::nextcloudAdmin()),
            $this->rule('Kalenderintegration', 'Eigene externe Kalenderverbindungen', 'Nur das angemeldete Konto', 'calendar.connector.manage-own', 'Eigene Kalenderverbindungen verwalten', 'own-connectors', PermissionCondition::self()),
            $this->rule('Kalenderintegration', 'OAuth, CalDAV und Systemvorgaben', 'Technische App-Konfiguration', 'calendar.integration.manage-system', 'Kalenderintegration administrieren', 'system-integration', PermissionCondition::nextcloudAdmin()),
            $this->rule('Demodaten', 'Kalender-Demo-Pack', 'Nur synthetische Demodaten', 'calendar.demo.install', 'Kalender-Demodaten installieren', 'demo-data', PermissionCondition::nextcloudAdmin()),
            $this->rule('Kalender', 'Alle Kalenderdaten', 'Native Adminausnahme für fachliche Gesamtverwaltung', 'calendar.read', 'Lesen', 'all-calendars', PermissionCondition::nextcloudAdmin()),
            $this->rule('Besprechungslücken', 'Gemeinsame freie Zeiten', 'Native Adminausnahme für fachliche Gesamtverwaltung', 'calendar.meeting.search', 'Besprechungslücken suchen', 'visible-calendars', PermissionCondition::nextcloudAdmin()),
        ];
        $definition = $this->source->definition();
        if ($definition === null)
        {
            return new PermissionProviderResult(
                $rules,
                false,
                ['flz_organization V1 ist nicht verfügbar; Organisations- und Peerregeln fehlen.'],
            );
        }
        $organizationConditions = array_map(
            static fn(string $groupId): PermissionCondition => PermissionCondition::group($groupId),
            $definition->roleGroupIds(),
        );
        $rules[] = $this->rule('Kalendereintrag', 'Eigene Einträge', 'Selbstbezug innerhalb des Organigramms', 'calendar.entry.manage-own', 'Eigene Einträge verwalten', 'own-entry', PermissionCondition::all([PermissionCondition::self(), PermissionCondition::any($organizationConditions)]));

        foreach ($organizationConditions as $condition) {
            $rules[] = $this->rule('Kalender', 'Alle Kalenderdaten', 'Mitgliedschaft in einer Organigrammrolle', 'calendar.read', 'Lesen', 'all-calendars', $condition);
            $rules[] = $this->rule('Besprechungslücken', 'Gemeinsame freie Zeiten', 'Suche aus bereits sichtbaren Kalenderdaten', 'calendar.meeting.search', 'Besprechungslücken suchen', 'visible-calendars', $condition);
        }

        foreach ($definition->hierarchy() as $actorKey => $directTargets) {
            $actorGroup = $definition->roleGroupId((string)$actorKey);
            if ($actorGroup === null) continue;
            foreach ($directTargets as $targetKey) {
                $area = $definition->roleManagementIsAreaScoped((string)$actorKey) ? '; gemeinsamer Bürobereich erforderlich' : '';
                $rules[] = $this->rule(
                    'Kalendereintrag',
                    'Unterstellte Rolle ' . $definition->roleLabel((string)$targetKey),
                    'Direkte Organisationshierarchie' . $area,
                    'calendar.entry.manage-subordinate',
                    'Unterstellte Einträge verwalten',
                    'target-role:' . $targetKey,
                    PermissionCondition::group($actorGroup)
                );
            }
        }
        foreach ($this->source->peerGroups() as $groupId) {
            $rules[] = $this->rule('Kalendereintrag', 'Peer-Gruppe ' . $groupId, 'Nur nicht übergeordnete Peers; BO/EB zusätzlich im gemeinsamen Bürobereich', 'calendar.entry.manage-peer', 'Peer-Einträge verwalten', 'peer-group:' . $groupId, PermissionCondition::group((string)$groupId));
        }

        return new PermissionProviderResult($rules);
    }

    private function rule(string $type, string $name, string $detail, string $permission, string $label, string $scope, PermissionCondition $condition): PermissionRule {
        return new PermissionRule($type, $name, $detail, $permission, $label, 'allow', $scope, $condition, 'flz_organization:OrganizationAuthorizationService', 'high');
    }
}
