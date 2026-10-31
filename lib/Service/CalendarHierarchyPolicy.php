<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\LocalBase\Organization\FlzOrganizationHierarchy;

/**
 * Zweck: Bildet die direkten organisatorischen Weisungskanten für Kalenderbearbeitung ab.
 * Vertrag: Eine Leitungsrolle darf ausschließlich unmittelbar zugeordnete Zielrollen bearbeiten.
 */
final class CalendarHierarchyPolicy extends FlzOrganizationHierarchy {
    public function manages(array $actorGroups, array $targetGroups): bool {
        foreach ($this->definition()->roleKeysForGroups($actorGroups) as $actorRole) {
            $directTargets = $this->definition()->hierarchy()[$actorRole] ?? [];
            foreach ($this->definition()->roleKeysForGroups($targetGroups) as $targetRole) {
                if (in_array($targetRole, $directTargets, true)) return true;
            }
        }
        return false;
    }

    public function managementRequiresSharedArea(array $actorGroups, array $targetGroups): bool {
        $matched = false;
        foreach ($this->definition()->roleKeysForGroups($actorGroups) as $actorRole) {
            $directTargets = $this->definition()->hierarchy()[$actorRole] ?? [];
            foreach ($this->definition()->roleKeysForGroups($targetGroups) as $targetRole) {
                if (!in_array($targetRole, $directTargets, true)) continue;
                $matched = true;
                if (!$this->definition()->roleManagementIsAreaScoped($actorRole)) return false;
            }
        }
        return $matched;
    }

    public function targetIsSuperior(array $actorGroups, array $targetGroups): bool {
        return $this->manages($targetGroups, $actorGroups) && !$this->manages($actorGroups, $targetGroups);
    }
}
