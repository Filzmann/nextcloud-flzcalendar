<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Zweck: Erzwingt den gemeinsamen Rollen-/Bereichsvertrag serverseitig.
 * Zusammenspiel: Controller -> CalendarAccessService -> zentrale Organisationspolicy und dynamische Gruppenprofile.
 * Vertrag: Alle angemeldeten Nutzer*innen lesen; eigene Einträge und fachlich delegierte Ziele werden bearbeitet.
 */
final class CalendarAccessService {
    public function __construct(
        private IGroupManager $groups,
        private IUserSession $session,
        private IUserManager $users,
        private CalendarPermissionPolicy $policy,
        private CalendarSettingsService $settings,
        private CalendarGroupProfile $profiles,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
        private ?AdOrganizationSettingsService $organization = null,
    ) {}

    public function currentUser(): ?IUser {
        return $this->session->getUser();
    }

    public function canView(): bool {
        $user = $this->currentUser();
        return $user !== null;
    }

    public function canManage(string $employeeUid): bool {
        $user = $this->currentUser();
        if ($user === null) return false;
        $target = $this->users->get($employeeUid);
        if ($target === null) return false;

        $actorGroups = $this->groupIds($user);
        $targetGroups = $this->groupIds($target);

        return $this->policy->canManage(
            $user->getUID(),
            $this->groups->isAdmin($user->getUID()) && $this->temporaryAdminAccess->hasActiveGrant($user->getUID()),
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

    private function definition(): AdOrganizationDefinition {
        return $this->organization?->definition() ?? AdOrganizationDefinition::defaults();
    }
}
