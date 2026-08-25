<?php

declare(strict_types=1);

namespace OCA\FilzmannPermissionMatrix\PublicApi\V1 {
    interface PermissionProvider { public function descriptor(): PermissionProviderDescriptor; public function collect(): PermissionProviderResult; }
    final class PermissionProviderDescriptor { public function __construct(public string $appId, public string $name, public string $version, public array $capabilities) {} }
    final class PermissionCondition {
        private function __construct(public string $operator, public ?string $groupId = null, public array $children = []) {}
        public static function group(string $id): self { return new self('group', $id); }
        public static function all(array $children): self { return new self('all', null, $children); }
        public static function any(array $children): self { return new self('any', null, $children); }
        public static function self(): self { return new self('self'); }
        public static function authenticated(): self { return new self('authenticated'); }
        public static function nextcloudAdmin(): self { return new self('nextcloud-admin'); }
        public static function temporaryAppAdminGrant(): self { return new self('app-admin-grant'); }
    }
    final class PermissionRule { public function __construct(public string $objectType, public string $objectName, public string $detail, public string $permission, public string $label, public string $effect, public string $scope, public PermissionCondition $condition, public string $source, public string $confidence) {} }
    final class PermissionProviderResult { public function __construct(public array $rules, public bool $complete = true, public array $warnings = []) {} }
    final class RegisterPermissionProvidersEvent { public array $providers = []; public function register(PermissionProvider $provider): void { $this->providers[] = $provider; } }
}

namespace {
    use OCA\AdCalendar\Permission\CalendarPermissionProvider;
    use OCA\AdCalendar\Permission\CalendarPermissionProviderListener;
    use OCA\AdCalendar\Permission\CalendarPermissionSourceInterface;
    use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
    use OCA\LocalBase\Organization\AdOrganizationDefinition;

    $source = new class implements CalendarPermissionSourceInterface {
        public function definition(): AdOrganizationDefinition { return AdOrganizationDefinition::defaults(); }
        public function peerGroups(): array { return ['ad-EB']; }
    };
    $provider = new CalendarPermissionProvider($source);
    $result = $provider->collect();
    $rules = $result->rules;
    $byPermission = [];
    foreach ($rules as $rule) $byPermission[$rule->permission][] = $rule;

    if (($byPermission['calendar.read'][0]->condition->operator ?? null) !== 'authenticated') throw new RuntimeException('Lesen muss als Anmeldebedingung ausgewiesen werden.');
    if (($byPermission['calendar.entry.manage-own'][0]->condition->operator ?? null) !== 'self') throw new RuntimeException('Eigene Bearbeitung darf kein Gruppenrecht werden.');
    $adminCondition = $byPermission['calendar.entry.manage-all'][0]->condition ?? null;
    if (($adminCondition->operator ?? null) !== 'all'
        || ($adminCondition->children[0]->operator ?? null) !== 'nextcloud-admin'
        || ($adminCondition->children[1]->operator ?? null) !== 'app-admin-grant') {
        throw new RuntimeException('Vollzugriff muss native Administration und aktive app-lokale Freigabe verlangen.');
    }
    $peerGroups = array_map(static fn($rule) => $rule->condition->groupId, $byPermission['calendar.entry.manage-peer'] ?? []);
    if ($peerGroups !== ['ad-EB']) throw new RuntimeException('Nur tatsächlich konfigurierte Peer-Gruppen dürfen projiziert werden.');
    if (($byPermission['calendar.entry.manage-subordinate'] ?? []) === []) throw new RuntimeException('Die kanonische Hierarchie muss als Detailrecht projiziert werden.');

    $event = new RegisterPermissionProvidersEvent();
    (new CalendarPermissionProviderListener($provider))->handle($event);
    if (($event->providers[0] ?? null) !== $provider) throw new RuntimeException('Der Provider muss lazy registriert werden.');

    echo 'Calendar permission provider tests passed' . PHP_EOL;
}
