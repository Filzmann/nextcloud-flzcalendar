<?php

declare(strict_types=1);

namespace OCP {
    interface IUser { public function getUID(): string; public function getDisplayName(): string; }
    interface IUserSession { public function getUser(): ?IUser; }
    interface IUserManager { public function get(string $uid): ?IUser; }
    interface IGroupManager {
        public function isAdmin(string $uid): bool;
        public function getUserGroupIds(IUser $user): array;
        public function get(string $gid);
    }
}

namespace OCA\FlzCalendar\Service {
    final class CalendarOrganization {
        public function roleGroupIds(?callable $filter = null): array {
            $roles = [
                'role-a' => ['calendarVisible' => true],
                'role-hidden' => ['calendarVisible' => false],
                'missing' => ['calendarVisible' => true],
            ];
            return array_keys($filter === null ? $roles : array_filter($roles, $filter));
        }
    }
    final class OrganizationRuntime {
        public array $calls = [];
        public bool $allowed = true;
        public function organization(): ?CalendarOrganization { return new CalendarOrganization(); }
        public function canManage(...$arguments): bool { $this->calls[] = $arguments; return $this->allowed; }
    }
    final class CalendarGroupProfile {
        public function get(array $groups): array {
            return [
                'roles' => array_values(array_filter($groups, static fn(string $id): bool => str_starts_with($id, 'role-'))),
                'areas' => array_values(array_filter($groups, static fn(string $id): bool => str_starts_with($id, 'area-'))),
                'clusters' => [],
            ];
        }
    }
}

namespace {

    use OCA\FlzCalendar\Service\CalendarAccessService;
    use OCA\FlzCalendar\Service\CalendarGroupProfile;
    use OCA\FlzCalendar\Service\OrganizationRuntime;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserManager;
    use OCP\IUserSession;

    $user = static fn(string $uid, string $name): IUser => new class($uid, $name) implements IUser {
        public function __construct(private string $uid, private string $name) {}
        public function getUID(): string { return $this->uid; }
        public function getDisplayName(): string { return $this->name; }
    };
    $actor = $user('actor', 'Planner');
    $alpha = $user('alpha', 'Alpha');
    $zeta = $user('zeta', 'Zeta');
    $outsider = $user('outsider', 'Outsider');
    $session = new class implements IUserSession {
        public ?IUser $user = null;
        public function getUser(): ?IUser { return $this->user; }
    };
    $users = new class implements IUserManager {
        public array $users = [];
        public function get(string $uid): ?IUser { return $this->users[$uid] ?? null; }
    };
    $users->users = ['actor' => $actor, 'alpha' => $alpha, 'zeta' => $zeta, 'outsider' => $outsider];
    $groups = new class implements IGroupManager {
        public array $members = [];
        public array $groupIds = [];
        public array $admins = [];
        public function isAdmin(string $uid): bool { return in_array($uid, $this->admins, true); }
        public function getUserGroupIds(IUser $user): array { return $this->groupIds[$user->getUID()] ?? []; }
        public function get(string $gid): ?object {
            if (!array_key_exists($gid, $this->members)) return null;
            return new class($this->members[$gid]) { public function __construct(private array $users) {} public function getUsers(): array { return $this->users; } };
        }
    };
    $groups->members = ['role-a' => [$zeta, $alpha, $alpha]];
    $groups->groupIds = [
        'actor' => ['role-a', 'area-a'],
        'alpha' => ['role-a', 'area-a'],
        'zeta' => ['role-a', 'area-b'],
    ];
    $organization = new OrganizationRuntime();
    $access = new CalendarAccessService(
        $groups,
        $session,
        $users,
        new CalendarGroupProfile(),
        $organization,
    );

    if ($access->currentUser() !== null || $access->canView() || $access->canManage('alpha')
        || $access->currentProfile() !== ['roles' => [], 'areas' => [], 'clusters' => []]
        || $access->visibleEmployees() !== []) {
        throw new RuntimeException('Anonyme Zugriffe werden nicht vollständig verweigert.');
    }

    $session->user = $actor;
    if (!$access->canView() || $access->canManage('unknown')) throw new RuntimeException('Organigramm-Sicht oder unbekanntes Ziel wird falsch bewertet.');
    if (!$access->canManage('alpha')) throw new RuntimeException('Policy-Ergebnis wird nicht für bekannte Zielkonten verwendet.');
    $policyCall = $organization->calls[0];
    if ($policyCall[0] !== 'actor' || $policyCall[1] !== ['role-a', 'area-a']
        || $policyCall[2] !== 'alpha' || $policyCall[3] !== ['role-a', 'area-a']) {
        throw new RuntimeException('Organigrammrollen dürfen nicht versehentlich als native Adminausnahme gelten.');
    }
    $groups->groupIds['actor'] = [];
    if ($access->canView() || $access->canManage('actor')) throw new RuntimeException('Organisationsfremde Konten dürfen weder lesen noch eigene Einträge verwalten.');
    $groups->admins = ['actor'];
    if (!$access->canView()) throw new RuntimeException('Die ausdrücklich freigegebene native Adminausnahme muss die Kalenderverwaltung erreichbar machen.');
    if (!$access->canManage('alpha')) throw new RuntimeException('Native Administration muss ohne app-lokale Freigabe die ausdrückliche Kalenderausnahme erfüllen.');
    $groups->admins = [];
    if ($access->canView() || $access->canManage('alpha')) {
        throw new RuntimeException('Der Verlust des nativen Adminstatus muss die Kalenderausnahme beim nächsten Zugriff sofort entziehen.');
    }
    $groups->groupIds['actor'] = ['role-a', 'area-a'];
    $profile = $access->currentProfile();
    if ($profile['roles'] !== ['role-a'] || $profile['areas'] !== ['area-a']) throw new RuntimeException('Aktuelles Fachprofil wird nicht aus nativen Gruppen abgeleitet.');
    $visible = $access->visibleEmployees();
    if (array_column($visible, 'uid') !== ['alpha', 'zeta'] || $visible[0]['roles'] !== ['role-a']
        || $visible[1]['areas'] !== ['area-b'] || $visible[0]['canManage'] !== true) {
        throw new RuntimeException('Sichtbare Kalenderkonten werden nicht dedupliziert, profiliert und natürlich sortiert.');
    }
    $organization->allowed = false;
    if ($access->canManage('zeta')) throw new RuntimeException('Verweigertes Policy-Ergebnis wird nicht durchgesetzt.');

    echo "CalendarAccessServiceExecutionTest: OK\n";
}
