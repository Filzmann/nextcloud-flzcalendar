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

namespace OCA\LocalBase\Organization {
    final class AdOrganizationDefinition {
        public static function defaults(): self { return new self(); }
        public function roleGroupIds(callable $filter): array {
            $roles = [
                'role-a' => ['calendarVisible' => true],
                'role-hidden' => ['calendarVisible' => false],
                'missing' => ['calendarVisible' => true],
            ];
            return array_keys(array_filter($roles, $filter));
        }
    }
    final class AdOrganizationSettingsService { public function definition(): AdOrganizationDefinition { return AdOrganizationDefinition::defaults(); } }
}

namespace OCA\AdCalendar\Service {
    final class CalendarPermissionPolicy {
        public array $calls = [];
        public bool $allowed = true;
        public function canManage(...$arguments): bool { $this->calls[] = $arguments; return $this->allowed; }
    }
    final class CalendarSettingsService { public function enabledPeerGroups(): array { return ['peer-a']; } }
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
    require_once __DIR__ . '/../../lib/Service/CalendarAccessService.php';

    use OCA\AdCalendar\Service\CalendarAccessService;
    use OCA\AdCalendar\Service\CalendarGroupProfile;
    use OCA\AdCalendar\Service\CalendarPermissionPolicy;
    use OCA\AdCalendar\Service\CalendarSettingsService;
    use OCA\LocalBase\Organization\AdOrganizationSettingsService;
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
    $session = new class implements IUserSession {
        public ?IUser $user = null;
        public function getUser(): ?IUser { return $this->user; }
    };
    $users = new class implements IUserManager {
        public array $users = [];
        public function get(string $uid): ?IUser { return $this->users[$uid] ?? null; }
    };
    $users->users = ['actor' => $actor, 'alpha' => $alpha, 'zeta' => $zeta];
    $groups = new class implements IGroupManager {
        public array $members = [];
        public array $groupIds = [];
        public function isAdmin(string $uid): bool { return $uid === 'actor'; }
        public function getUserGroupIds(IUser $user): array { return $this->groupIds[$user->getUID()] ?? []; }
        public function get(string $gid): ?object {
            if (!array_key_exists($gid, $this->members)) return null;
            return new class($this->members[$gid]) { public function __construct(private array $users) {} public function getUsers(): array { return $this->users; } };
        }
    };
    $groups->members = ['role-a' => [$zeta, $alpha, $alpha]];
    $groups->groupIds = [
        'actor' => ['role-planner', 'area-a'],
        'alpha' => ['role-a', 'area-a'],
        'zeta' => ['role-a', 'area-b'],
    ];
    $policy = new CalendarPermissionPolicy();
    $access = new CalendarAccessService(
        $groups,
        $session,
        $users,
        $policy,
        new CalendarSettingsService(),
        new CalendarGroupProfile(),
        new AdOrganizationSettingsService(),
    );

    if ($access->currentUser() !== null || $access->canView() || $access->canManage('alpha')
        || $access->currentProfile() !== ['roles' => [], 'areas' => [], 'clusters' => []]
        || $access->visibleEmployees() !== []) {
        throw new RuntimeException('Anonyme Zugriffe werden nicht vollständig verweigert.');
    }

    $session->user = $actor;
    if (!$access->canView() || $access->canManage('unknown')) throw new RuntimeException('Angemeldete Sicht oder unbekanntes Ziel wird falsch bewertet.');
    if (!$access->canManage('alpha')) throw new RuntimeException('Policy-Ergebnis wird nicht für bekannte Zielkonten verwendet.');
    $policyCall = $policy->calls[0];
    if ($policyCall[0] !== 'actor' || $policyCall[1] !== true || $policyCall[2] !== ['role-planner', 'area-a']
        || $policyCall[3] !== 'alpha' || $policyCall[4] !== ['role-a', 'area-a'] || $policyCall[5] !== ['peer-a']) {
        throw new RuntimeException('Berechtigungsprüfung erhält nicht Akteur, Adminstatus, Profile und Peer-Vertrag.');
    }
    $profile = $access->currentProfile();
    if ($profile['roles'] !== ['role-planner'] || $profile['areas'] !== ['area-a']) throw new RuntimeException('Aktuelles Fachprofil wird nicht aus nativen Gruppen abgeleitet.');
    $visible = $access->visibleEmployees();
    if (array_column($visible, 'uid') !== ['alpha', 'zeta'] || $visible[0]['roles'] !== ['role-a']
        || $visible[1]['areas'] !== ['area-b'] || $visible[0]['canManage'] !== true) {
        throw new RuntimeException('Sichtbare Kalenderkonten werden nicht dedupliziert, profiliert und natürlich sortiert.');
    }
    $policy->allowed = false;
    if ($access->canManage('zeta')) throw new RuntimeException('Verweigertes Policy-Ergebnis wird nicht durchgesetzt.');

    echo "CalendarAccessServiceExecutionTest: OK\n";
}
