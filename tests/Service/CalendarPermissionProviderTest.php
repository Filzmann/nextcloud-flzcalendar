<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event {}
    interface IEventListener { public function handle(Event $event): void; }
}
namespace OCA\FlzPermissionMatrix\PublicApi\V1 {
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
    final class RegisterPermissionProvidersEvent extends \OCP\EventDispatcher\Event { public array $providers = []; public function register(PermissionProvider $provider): void { $this->providers[] = $provider; } }
}

namespace {
    use OCA\FlzCalendar\Permission\CalendarPermissionProvider;
    use OCA\FlzCalendar\Permission\CalendarPermissionProviderListener;
    use OCA\FlzCalendar\Permission\CalendarPermissionSourceInterface;
    use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
    use OCA\LocalBase\Organization\FlzOrganizationDefinition;
    use OCP\EventDispatcher\IEventListener;

    $source = new class implements CalendarPermissionSourceInterface {
        public function definition(): FlzOrganizationDefinition { return FlzOrganizationDefinition::defaults(); }
        public function peerGroups(): array { return ['EB']; }
    };
    $provider = new CalendarPermissionProvider($source);
    $result = $provider->collect();
    $rules = $result->rules;
    $byPermission = [];
    foreach ($rules as $rule) $byPermission[$rule->permission][] = $rule;

    $readConditions = array_map(static fn($rule) => $rule->condition, $byPermission['calendar.read'] ?? []);
    if (!in_array('nextcloud-admin', array_map(static fn($condition) => $condition->operator, $readConditions), true)
        || !in_array('group', array_map(static fn($condition) => $condition->operator, $readConditions), true)) {
        throw new RuntimeException('Lesen muss auf Organigrammrollen und die ausdrückliche native Adminausnahme begrenzt sein.');
    }
    $ownCondition = $byPermission['calendar.entry.manage-own'][0]->condition ?? null;
    if (($ownCondition->operator ?? null) !== 'all'
        || ($ownCondition->children[0]->operator ?? null) !== 'self'
        || ($ownCondition->children[1]->operator ?? null) !== 'any') {
        throw new RuntimeException('Eigene Bearbeitung muss Selbstbezug und Organigrammrolle gemeinsam verlangen.');
    }
    $adminCondition = $byPermission['calendar.entry.manage-all'][0]->condition ?? null;
    if (($adminCondition->operator ?? null) !== 'nextcloud-admin') {
        throw new RuntimeException('Die ausdrücklich entschiedene Kalenderausnahme muss native Administration ohne app-lokale Freigabe ausweisen.');
    }
    if (($byPermission['calendar.connector.manage-own'][0]->condition->operator ?? null) !== 'self') {
        throw new RuntimeException('Persönliche Kalenderkonnektoren müssen als reiner Selbstbezug ausgewiesen werden.');
    }
    $peerGroups = array_map(static fn($rule) => $rule->condition->groupId, $byPermission['calendar.entry.manage-peer'] ?? []);
    if ($peerGroups !== ['EB']) throw new RuntimeException('Nur tatsächlich konfigurierte Peer-Gruppen dürfen projiziert werden.');
    $subordinateScopes = array_column($byPermission['calendar.entry.manage-subordinate'] ?? [], 'scope');
    if (!in_array('target-role:pdl', $subordinateScopes, true) || in_array('target-role:pfk', array_slice($subordinateScopes, 0, 1), true)) {
        throw new RuntimeException('Die Rechteprojektion muss direkte statt transitive Hierarchiekanten ausweisen.');
    }
    if (($byPermission['calendar.meeting.search'][0]->condition->operator ?? null) !== 'group') throw new RuntimeException('Die Lückensuche muss als Organigrammrecht projiziert werden.');

    $event = new RegisterPermissionProvidersEvent();
    $listener = new CalendarPermissionProviderListener($provider);
    if (!$listener instanceof IEventListener) throw new RuntimeException('Der Permission-Provider-Listener muss den Nextcloud-Eventvertrag implementieren.');
    $listener->handle($event);
    if (($event->providers[0] ?? null) !== $provider) throw new RuntimeException('Der Provider muss lazy registriert werden.');

    echo 'Calendar permission provider tests passed' . PHP_EOL;
}
