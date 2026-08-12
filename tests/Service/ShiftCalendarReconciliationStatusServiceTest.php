<?php

declare(strict_types=1);

namespace OCP {
    if (!interface_exists(IAppConfig::class)) {
        interface IAppConfig {
            public function getValueString(string $appId, string $key, string $default = '', bool $lazy = false): string;
            public function setValueString(string $appId, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;
        }
    }
}

namespace OCP\AppFramework\Utility {
    if (!interface_exists(ITimeFactory::class)) {
        interface ITimeFactory { public function getTime(): int; }
    }
}

namespace OCA\AdCalendar\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'adcalendar'; }
    }
}

namespace {

    $config = new class implements \OCP\IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = '', bool $lazy = false): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool { $this->values[$appId][$key] = $value; return true; }
    };
    $time = new class implements \OCP\AppFramework\Utility\ITimeFactory {
        public function getTime(): int { return 1_753_000_000; }
    };
    $service = new \OCA\AdCalendar\Service\ShiftCalendarReconciliationStatusService($config, $time);

    $pending = $service->status();
    if ($pending !== ['hasRun' => false, 'lastRunAt' => 0, 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending']) {
        throw new \RuntimeException('Noch nicht ausgeführter DAV-Abgleich wird nicht neutral aggregiert dargestellt.');
    }

    $service->record(['attempted' => 4, 'succeeded' => 3, 'failed' => 1, 'userIds' => ['darf-nicht-persistiert-werden']]);
    $status = $service->status();
    if ($status !== ['hasRun' => true, 'lastRunAt' => 1_753_000_000, 'attempted' => 4, 'succeeded' => 3, 'failed' => 1, 'state' => 'warning']) {
        throw new \RuntimeException('Aggregierter DAV-Abgleichstatus ist unvollständig.');
    }
    $stored = reset($config->values['adcalendar']);
    if (!is_string($stored) || str_contains($stored, 'darf-nicht-persistiert-werden') || !str_contains($stored, '"direction":"outbound"')) {
        throw new \RuntimeException('DAV-Status persistiert personenbezogene Details oder hält die spätere Richtungs-Erweiterung nicht offen.');
    }

    $config->values['adcalendar'][array_key_first($config->values['adcalendar'])] = '{ungültig';
    if ($service->status()['state'] !== 'pending') throw new \RuntimeException('Ungültiger DAV-Status fällt nicht sicher auf den neutralen Zustand zurück.');

    echo "ShiftCalendarReconciliationStatusServiceTest: OK\n";
}
