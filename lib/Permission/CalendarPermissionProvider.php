<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Permission;

use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FilzmannPermissionMatrix\PublicApi\V1\PermissionRule;

final class CalendarPermissionProvider implements PermissionProvider {
    public function __construct(private CalendarPermissionSourceInterface $source) {}

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor('adcalendar', 'AD Kalender', '1.0', ['permissions']);
    }

    public function collect(): PermissionProviderResult {
        $definition = $this->source->definition();
        $rules = [
            $this->rule('Kalender', 'Alle Kalenderdaten', 'Lesen', 'calendar.read', 'Lesen', 'all-calendars', PermissionCondition::authenticated()),
            $this->rule('Kalendereintrag', 'Eigene Einträge', 'Anlegen, ändern und löschen', 'calendar.entry.manage-own', 'Eigene Einträge verwalten', 'own-entry', PermissionCondition::self()),
            $this->rule('Kalendereintrag', 'Alle Einträge', 'Native Nextcloud-Administration', 'calendar.entry.manage-all', 'Alle Einträge verwalten', 'all-entries', PermissionCondition::nextcloudAdmin()),
        ];

        foreach ($definition->hierarchy() as $actorKey => $directTargets) {
            $actorGroup = $definition->roleGroupId((string)$actorKey);
            if ($actorGroup === null) continue;
            foreach (array_keys($definition->roles()) as $targetKey) {
                if (!$definition->managesRole((string)$actorKey, (string)$targetKey)) continue;
                $area = $definition->roleManagementIsAreaScoped((string)$actorKey) ? '; gemeinsamer Bürobereich erforderlich' : '';
                $rules[] = $this->rule(
                    'Kalendereintrag',
                    'Unterstellte Rolle ' . $definition->roleLabel((string)$targetKey),
                    'Transitive Organisationshierarchie' . $area,
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
        return new PermissionRule($type, $name, $detail, $permission, $label, 'allow', $scope, $condition, 'adcalendar:CalendarPermissionPolicy', 'high');
    }
}
