<?php

declare(strict_types=1);

namespace OCP\App {
    if (!interface_exists(IAppManager::class)) {
        interface IAppManager { public function getEnabledApps(): array; }
    }
}

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface { public function warning(string|\Stringable $message, array $context = []): void; }
    }
}

namespace OCA\FlzOrganization\PublicApi\V1 {
    {
        final class Contract {
            public const APP_ID = 'flz_organization';
            public static function supports(string $version): bool { return $version === '1.0'; }
        }
        final class OrganizationSnapshot {
            public function __construct(private bool $valid, private array $definition) {}
            public function isValid(): bool { return $this->valid; }
            public function contractVersion(): string { return '1.0'; }
            public function definition(): array { return $this->definition; }
        }
        final class OrganizationSnapshotService {
            public function __construct(private OrganizationSnapshot $snapshot) {}
            public function snapshot(): OrganizationSnapshot { return $this->snapshot; }
        }
        final class OrganizationAuthorizationService {
            public const SCOPE_CALENDAR_EDIT = 'calendar.edit';
            public function canManage(string $actorUid, array $actorGroups, string $targetUid, array $targetGroups, string $scope, bool $allowSelf = true): bool {
                return $scope === self::SCOPE_CALENDAR_EDIT && ($allowSelf && $actorUid === $targetUid || in_array('lead', $actorGroups, true));
            }
            public function peerGroups(string $scope): array { return $scope === self::SCOPE_CALENDAR_EDIT ? ['office'] : []; }
        }
        final class CalendarContextSnapshot { public function timezone(): string { return 'Europe/Berlin'; } }
        final class CalendarContextProvider { public function context(): CalendarContextSnapshot { return new CalendarContextSnapshot(); } }
        final class HolidayCalendarSnapshot { public function __construct(public int $year) {} public function isUsable(): bool { return true; } }
        final class HolidayCalendarProvider { public function calendar(int $year): HolidayCalendarSnapshot { return new HolidayCalendarSnapshot($year); } }
    }
}

namespace {
    use OCA\FlzCalendar\Service\CalendarOrganization;
    use OCA\FlzCalendar\Service\OrganizationRuntime;
    use OCA\FlzOrganization\PublicApi\V1\CalendarContextProvider;
    use OCA\FlzOrganization\PublicApi\V1\HolidayCalendarProvider;
    use OCA\FlzOrganization\PublicApi\V1\OrganizationAuthorizationService;
    use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshot;
    use OCA\FlzOrganization\PublicApi\V1\OrganizationSnapshotService;
    use OCP\App\IAppManager;
    use Psr\Log\LoggerInterface;

    final class OrganizationRuntimeApps implements IAppManager {
        public function __construct(private array $enabled) {}
        public function getEnabledApps(): array { return $this->enabled; }
    }
    final class OrganizationRuntimeLogger implements LoggerInterface {
        public array $warnings = [];
        public function warning(string|Stringable $message, array $context = []): void { $this->warnings[] = [$message, $context]; }
    }
    final class TestOrganizationRuntime extends OrganizationRuntime {
        public int $resolutions = 0;
        public bool $compatible = true;
        public function __construct(IAppManager $apps, LoggerInterface $logger, private array $services) { parent::__construct($apps, $logger); }
        protected function isContractCompatible(): bool { return $this->compatible; }
        protected function resolve(string $class): object { $this->resolutions++; return $this->services[$class]; }
    }

    $definition = [
        'version' => 5,
        'staffBlockLabel' => 'Leitung und Stab',
        'roles' => [
            'lead' => ['groupId' => 'lead', 'label' => 'Leitung', 'calendarVisible' => true, 'sortOrder' => 10, 'areaScoped' => false, 'managementAreaScoped' => false, 'peerEnabled' => false],
            'office' => ['groupId' => 'office', 'label' => 'Büro', 'calendarVisible' => true, 'sortOrder' => 20, 'areaScoped' => true, 'managementAreaScoped' => true, 'peerEnabled' => true],
        ],
        'areas' => ['west' => ['groupId' => 'west', 'label' => 'West', 'shortLabel' => 'W', 'sortOrder' => 10]],
        'hierarchy' => ['lead' => ['office']],
    ];
    $snapshot = new OrganizationSnapshot(true, $definition);
    $services = [
        OrganizationSnapshotService::class => new OrganizationSnapshotService($snapshot),
        OrganizationAuthorizationService::class => new OrganizationAuthorizationService(),
        CalendarContextProvider::class => new CalendarContextProvider(),
        HolidayCalendarProvider::class => new HolidayCalendarProvider(),
    ];

    $missing = new TestOrganizationRuntime(new OrganizationRuntimeApps([]), new OrganizationRuntimeLogger(), $services);
    if ($missing->organization() !== null || $missing->canManage('same', [], 'same', []) || $missing->resolutions !== 0) {
        throw new RuntimeException('A missing provider must fail closed without resolving public services.');
    }

    $incompatible = new TestOrganizationRuntime(new OrganizationRuntimeApps(['flz_organization']), new OrganizationRuntimeLogger(), $services);
    $incompatible->compatible = false;
    if ($incompatible->organization() !== null || $incompatible->resolutions !== 0) {
        throw new RuntimeException('An incompatible provider must fail closed before service resolution.');
    }

    $invalidLogger = new OrganizationRuntimeLogger();
    $invalidDefinition = $definition;
    unset($invalidDefinition['roles']['office']['peerEnabled']);
    $invalidServices = $services;
    $invalidServices[OrganizationSnapshotService::class] = new OrganizationSnapshotService(
        new OrganizationSnapshot(true, $invalidDefinition),
    );
    $invalid = new TestOrganizationRuntime(
        new OrganizationRuntimeApps(['flz_organization']),
        $invalidLogger,
        $invalidServices,
    );
    if ($invalid->organization() !== null || $invalidLogger->warnings === []) {
        throw new RuntimeException('An invalid public definition must fail closed and remain diagnosable.');
    }

    $runtime = new TestOrganizationRuntime(new OrganizationRuntimeApps(['flz_organization']), new OrganizationRuntimeLogger(), $services);
    $organization = $runtime->organization();
    if (!$organization instanceof CalendarOrganization
        || $organization->roleGroupIds() !== ['lead', 'office']
        || $organization->areaGroupIds() !== ['west']
        || !$runtime->canManage('actor', ['lead'], 'target', ['office'])
        || $runtime->canManage('actor', [], 'target', ['office'])
        || $runtime->peerGroups() !== ['office']
        || $runtime->calendarContext()?->timezone() !== 'Europe/Berlin'
        || $runtime->holidayCalendar(2027)?->year !== 2027) {
        throw new RuntimeException('A compatible provider is not projected through the public V1 services.');
    }

    echo "OrganizationRuntimeTest: OK\n";
}
