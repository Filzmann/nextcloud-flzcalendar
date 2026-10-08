<?php

declare(strict_types=1);

namespace OCP {
    interface IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string;
        public function setValueString(string $appId, string $key, string $value): void;
    }
}

namespace OCP\AppFramework\Utility {
    interface ITimeFactory { public function getTime(): int; }
}

namespace OCA\LocalBase\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'localbase'; }
    }
}

namespace {
    use OCA\FlzCalendar\Service\CalendarSyncHorizon;
    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCP\AppFramework\Utility\ITimeFactory;
    use OCP\IAppConfig;

    $config = new class implements IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string {
            return json_encode([
                'version' => 1,
                'countryCode' => 'DE',
                'subdivisionCode' => 'DE-BE',
                'timezone' => 'Europe/Berlin',
            ], JSON_THROW_ON_ERROR);
        }
        public function setValueString(string $appId, string $key, string $value): void {}
    };
    $time = new class implements ITimeFactory {
        public function getTime(): int { return 1786276800; } // 2026-08-09T12:00:00Z
    };

    [$start, $end] = (new CalendarSyncHorizon(new CalendarContextSettingsService($config), $time))->range();
    if ($start->format(DATE_ATOM) !== '2026-01-01T00:00:00+01:00'
        || $end->format(DATE_ATOM) !== '2029-01-01T00:00:00+01:00') {
        throw new RuntimeException('Der Urlaubsabgleich umfasst nicht das laufende und die zwei folgenden Kalenderjahre.');
    }

    echo "CalendarSyncHorizonTest: OK\n";
}
