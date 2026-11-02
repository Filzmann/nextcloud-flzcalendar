<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueString(string $appId, string $key, string $default = ''): string;
            public function setValueString(string $appId, string $key, string $value): void;
        }
    }
}

namespace OCA\LocalBase\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {
    use OCA\FlzCalendar\Permission\NextcloudCalendarPermissionSource;
    use OCA\FlzCalendar\Service\CalendarSettingsService;
    use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
    use OCA\LocalBase\Organization\FlzSuiteAdminSettingsService;
    use OCP\IAppConfig;

    $config = new class implements IAppConfig {
        /** @var array<string,array<string,string>> */
        public array $values = [];

        public function getValueString(string $appId, string $key, string $default = ''): string {
            return $this->values[$appId][$key] ?? $default;
        }

        public function setValueString(string $appId, string $key, string $value): void {
            $this->values[$appId][$key] = $value;
        }
    };

    $organization = new FlzOrganizationSettingsService($config);
    $adminSettings = new FlzSuiteAdminSettingsService($config, $organization);
    $calendarSettings = new CalendarSettingsService($adminSettings, $organization);
    $source = new NextcloudCalendarPermissionSource($organization, $calendarSettings);

    $defaults = $source->peerGroups();
    foreach (['Buero', 'EB', 'PFK'] as $enabledByDefault) {
        if (!in_array($enabledByDefault, $defaults, true)) {
            throw new RuntimeException('Der Kalender konsumiert den freigegebenen Peerdefault nicht: ' . $enabledByDefault);
        }
    }
    foreach (['Stab-HR', 'Stab-QMB'] as $disabledByDefault) {
        if (in_array($disabledByDefault, $defaults, true)) {
            throw new RuntimeException('Der Kalender aktiviert eine standardmäßig ausgeschaltete Peerrolle: ' . $disabledByDefault);
        }
    }

    $adminSettings->saveCalendarPeerEditing([
        'Buero' => false,
        'EB' => true,
        'PFK' => true,
        'Stab-HR' => true,
        'Stab-QMB' => false,
    ]);
    $configured = $source->peerGroups();
    if (in_array('Buero', $configured, true)
        || !in_array('EB', $configured, true)
        || !in_array('PFK', $configured, true)
        || !in_array('Stab-HR', $configured, true)
        || in_array('Stab-QMB', $configured, true)) {
        throw new RuntimeException('Der Kalender respektiert explizite Peerentscheidungen aus dem LocalBase-Vertrag nicht vollständig.');
    }

    echo "CalendarPeerSettingsConsumerContractTest: OK\n";
}
