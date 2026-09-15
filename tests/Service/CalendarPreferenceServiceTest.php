<?php

declare(strict_types=1);

namespace OCP\Config {
    if (!interface_exists(IUserConfig::class)) {
        interface IUserConfig {
            public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string;
            public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool;
            public function getValuesByUsers(string $app, string $key, mixed $typedAs = null, ?array $userIds = null): array;
        }
    }
}

namespace OCA\AdCalendar\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'adcalendar'; }
    }
}

namespace {

use OCA\AdCalendar\Service\CalendarPreferenceService;
use OCP\Config\IUserConfig;

$config = new class implements IUserConfig {
    public array $values = [];
    public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string { return $this->values[$userId][$app][$key] ?? $default; }
    public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool { $this->values[$userId][$app][$key] = $value; return true; }
    public function getValuesByUsers(string $app, string $key, mixed $typedAs = null, ?array $userIds = null): array {
        $result = [];
        foreach ($this->values as $uid => $apps) if (isset($apps[$app][$key])) $result[$uid] = $apps[$app][$key];
        return $result;
    }
};
$service = new CalendarPreferenceService($config);
if ($service->filterDefault('demo', ['a'], ['ad-Buero'], ['ad-Bereich-Sued']) !== null) throw new RuntimeException('Fehlender persoenlicher Standard muss null bleiben.');
if ($service->storedShiftDefaults('demo') !== null) throw new RuntimeException('Nicht gespeicherte Dienstzeiten duerfen keine Kalenderdienste erzeugen.');
if (!$service->shiftCalendarSyncEnabled('demo')) throw new RuntimeException('Private Dienstkalendersynchronisation ist ohne gespeicherten Opt-out nicht standardmäßig aktiv.');
if (!$service->saveShiftCalendarSyncEnabled('demo', true) || !$service->shiftCalendarSyncEnabled('demo')) throw new RuntimeException('Persönliche Kalenderaktivierung wurde nicht gespeichert.');
if ($service->saveShiftCalendarSyncEnabled('demo', false) || $service->shiftCalendarSyncEnabled('demo')) throw new RuntimeException('Persönliches Kalender-Opt-out wurde nicht gespeichert.');
$service->saveShiftCalendarSyncEnabled('zwei', true);
$service->saveShiftCalendarSyncEnabled('eins', true);
$service->saveShiftCalendarSyncEnabled('aus', false);
if ($service->shiftCalendarSyncEmployeeUids() !== ['eins', 'zwei']) throw new RuntimeException('Periodischer Abgleich erhält explizit aktivierte Konten nicht in stabiler Reihenfolge.');
$saved = $service->saveFilterDefault('demo', [
    'people' => ['a', 'fremd'], 'roles' => ['ad-Buero', 'ad-Unbekannt'],
    'areas' => ['ad-Bereich-Sued', 'ad-Bereich-Fremd'], 'vertical' => false, 'period' => 'month', 'empty' => true, 'showLeadershipStaff' => false,
], ['a'], ['ad-Buero'], ['ad-Bereich-Sued']);
if ($saved !== ['people' => ['a'], 'roles' => ['ad-Buero'], 'areas' => ['ad-Bereich-Sued'], 'vertical' => false, 'period' => 'month', 'showLeadershipStaff' => false, 'leadershipStaffOnly' => false]) {
    throw new RuntimeException('Persoenlicher Filterstandard wurde nicht auf erlaubte Werte begrenzt.');
}
if ($service->filterDefault('demo', ['a'], ['ad-Buero'], ['ad-Bereich-Sued']) !== $saved) throw new RuntimeException('Gespeicherter Filterstandard ist nicht lesbar.');
$invalidPeriod = $service->saveFilterDefault('period', ['period' => 'jahr'], ['a'], ['ad-Buero'], ['ad-Bereich-Sued']);
if ($invalidPeriod['period'] !== 'week') throw new RuntimeException('Unbekannter Ansichtszeitraum wurde als persönlicher Standard akzeptiert.');
$staffOnly = $service->saveFilterDefault('staff', [
    'people' => [], 'roles' => [], 'areas' => [], 'empty' => 'true', 'showLeadershipStaff' => true,
], ['a'], ['ad-Buero'], ['ad-Bereich-Sued']);
if (!$staffOnly['leadershipStaffOnly']) throw new RuntimeException('Gespeicherter Leitungs-/Stabsstandard wurde nicht aus dem bisherigen Filtervertrag uebernommen.');
$inconsistent = $service->saveFilterDefault('staff', [
    'people' => ['a'], 'roles' => [], 'areas' => [], 'leadershipStaffOnly' => true, 'showLeadershipStaff' => true,
], ['a'], ['ad-Buero'], ['ad-Bereich-Sued']);
if ($inconsistent['leadershipStaffOnly']) throw new RuntimeException('Ein Personenfilter darf nicht zugleich als reiner Leitungs-/Stabsfilter gespeichert werden.');
$shiftDefaults = $service->saveShiftDefaults('demo', [
    '1' => ['enabled' => true, 'start' => '07:30', 'end' => '15:45'],
    '2' => ['enabled' => false, 'start' => 'ungueltig', 'end' => '23:30'],
]);
if ($shiftDefaults['1'] !== ['enabled' => true, 'start' => '07:30', 'end' => '15:45']) throw new RuntimeException('Persoenliche Dienstzeit wurde nicht gespeichert.');
if ($shiftDefaults['2'] !== ['enabled' => false, 'start' => '08:00', 'end' => '23:30']) throw new RuntimeException('Dienstzeitnormalisierung ist falsch.');
if ($shiftDefaults['7'] !== ['enabled' => true, 'start' => '08:00', 'end' => '16:00']) throw new RuntimeException('Fehlender Wochentag hat keinen stabilen Standard.');
if ($service->shiftDefaults('demo') !== $shiftDefaults) throw new RuntimeException('Gespeicherte Dienstzeiten sind nicht lesbar.');
if ($service->storedShiftDefaults('demo') !== $shiftDefaults) throw new RuntimeException('Gespeicherte Dienstzeiten sind nicht als Serienregel lesbar.');

$projection = $service->personalDataProjection('demo');
if (($projection['filter']['selectedPeopleCount'] ?? null) !== 1
    || ($projection['filter']['roles'] ?? null) !== ['ad-Buero']
    || ($projection['filter']['areas'] ?? null) !== ['ad-Bereich-Sued']
    || ($projection['filter']['period'] ?? null) !== 'month'
    || ($projection['shiftDefaults']['1']['start'] ?? null) !== '07:30'
    || ($projection['calendarSyncEnabled'] ?? null) !== false) {
    throw new RuntimeException('Die subjectgebundene Projektion enthält die gespeicherten persönlichen Kalenderwerte nicht vollständig.');
}
if (str_contains(json_encode($projection, JSON_THROW_ON_ERROR), 'fremd')) {
    throw new RuntimeException('Die persönliche Filterprojektion gibt keine ausgewählten Drittpersonen-IDs aus.');
}
$config->values['corrupt']['adcalendar']['filter_default'] = '{';
$config->values['corrupt']['adcalendar']['shift_calendar_sync_enabled'] = 'unexpected';
$corrupt = $service->personalDataProjection('corrupt');
if (($corrupt['incomplete'] ?? false) !== true
    || !array_key_exists('filter', $corrupt) || $corrupt['filter'] !== null
    || !array_key_exists('calendarSyncEnabled', $corrupt) || $corrupt['calendarSyncEnabled'] !== null) {
    throw new RuntimeException('Unlesbare persönliche Benutzerwerte werden nicht als unvollständig erkannt.');
}

echo "CalendarPreferenceServiceTest: OK\n";
}
