<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Zweck: Erzwingt den gemeinsamen Rollen-/Bereichsvertrag serverseitig.
 * Zusammenspiel: Controller -> CalendarAccessService -> zentrale Organisationspolicy und dynamische Gruppenprofile.
 * Vertrag: Organigrammmitglieder lesen und bearbeiten eigene Einträge; direkte Vorgesetzte,
 * freigeschaltete Peers und native Nextcloud-Admins bearbeiten fremde Einträge.
 */
final class CalendarAccessService {
    public function __construct(
        private IGroupManager $groups,
        private IUserSession $session,
        private IUserManager $users,
        private CalendarPermissionPolicy $policy,
        private CalendarSettingsService $settings,
        private CalendarGroupProfile $profiles,
        private ?FlzOrganizationSettingsService $organization = null,
    ) {}

    public function currentUser(): ?IUser {
        return $this->session->getUser();
    }

    public function canView(): bool {
        $user = $this->currentUser();
        if ($user === null) return false;
        return $this->groups->isAdmin($user->getUID()) || $this->isOrganizationMember($this->groupIds($user));
    }

    public function canManage(string $employeeUid): bool {
        $user = $this->currentUser();
        if ($user === null) return false;
        $target = $this->users->get($employeeUid);
        if ($target === null) return false;

        $actorGroups = $this->groupIds($user);
        $isAdmin = $this->groups->isAdmin($user->getUID());
        if (!$isAdmin && !$this->isOrganizationMember($actorGroups)) return false;
        $targetGroups = $this->groupIds($target);

        return $this->policy->canManage(
            $user->getUID(),
            $isAdmin,
            $actorGroups,
            $employeeUid,
            $targetGroups,
            $this->settings->enabledPeerGroups(),
        );
    }

    /** Liefert nur die für Filter und Darstellung freigegebenen Fachgruppen des aktuellen Kontos. */
    public function currentProfile(): array {
        $user = $this->currentUser();
        if ($user === null) return ['roles' => [], 'areas' => [], 'clusters' => []];

        return $this->profiles->get($this->groupIds($user));
    }

    /** @return list<array{uid:string,displayName:string,roles:list<string>,areas:list<string>,clusters:list<string>,canManage:bool}> */
    public function visibleEmployees(): array {
        if (!$this->canView()) return [];

        $byUid = [];
        foreach ($this->definition()->roleGroupIds(static fn(array $role): bool => $role['calendarVisible']) as $role) {
            $group = $this->groups->get($role);
            if ($group === null) continue;

            foreach ($group->getUsers() as $user) {
                $byUid[$user->getUID()] = $user;
            }
        }

        $result = [];
        foreach ($byUid as $user) {
            $ids = array_map('strval', $this->groups->getUserGroupIds($user));
            $profile = $this->profiles->get($ids);
            $result[] = [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName(),
                'roles' => $profile['roles'],
                'areas' => $profile['areas'],
                'clusters' => $profile['clusters'],
                'canManage' => $this->canManage($user->getUID()),
            ];
        }
        usort($result, static fn(array $a, array $b): int => strnatcasecmp($a['displayName'], $b['displayName']));

        return $result;
    }

    /** @return list<string> */
    private function groupIds(IUser $user): array {
        return array_map('strval', $this->groups->getUserGroupIds($user));
    }

    private function isOrganizationMember(array $groupIds): bool {
        return array_intersect($groupIds, $this->definition()->roleGroupIds()) !== [];
    }

    private function definition(): FlzOrganizationDefinition {
        return $this->organization?->definition() ?? FlzOrganizationDefinition::defaults();
    }
}
