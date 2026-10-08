<?php

declare(strict_types=1);

namespace OCP {
    interface IAppConfig {
        public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string;
        public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;
    }
}
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }

namespace {

    use OCA\FlzCalendar\CalendarSync\ExternalCalendarUrlValidator;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCP\IAppConfig;

    $config = new class implements IAppConfig {
        public array $values = [];
        public array $writes = [];
        public ?int $throwOnWrite = null;
        private int $writeCount = 0;
        public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string { return $this->values[$key] ?? $default; }
        public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool {
            $this->writeCount++;
            if ($this->throwOnWrite === $this->writeCount) throw new RuntimeException('AppConfig-Schreibfehler');
            $this->values[$key] = $value;
            $this->writes[] = [$key, $value, $lazy, $sensitive];
            return true;
        }
        public function resetCounter(): void { $this->writeCount = 0; }
    };
    $targets = new CalendarTargetConfig($config, new ExternalCalendarUrlValidator());

    if ($targets->kopanoUrl() !== '' || $targets->calendarName() !== 'Filzmann Dienste') {
        throw new RuntimeException('Markenneutrale Defaults fehlen bei einer frischen Installation.');
    }
    $saved = $targets->save(' https://calendar.example.test/caldav ', ' Team & Dienst ');
    if ($saved !== ['kopanoUrl' => 'https://calendar.example.test/caldav/', 'calendarName' => 'Team & Dienst']
        || $targets->adminStatus() !== $saved
        || $config->writes[0] !== ['calendar_default_kopano_url', 'https://calendar.example.test/caldav/', true, false]) {
        throw new RuntimeException('Validierte Kalenderdefaults werden nicht nativ und nicht geheim gespeichert.');
    }
    if (!in_array('Filzmann Dienste', $targets->acceptedCalendarNames(), true) || !in_array('Team & Dienst', $targets->acceptedCalendarNames(), true)) {
        throw new RuntimeException('Die sichere Namenshistorie für vorhandene App-Kalender fehlt.');
    }

    $before = $config->values;
    foreach ([
        ['http://calendar.example.test', 'Kalender'],
        ['https://calendar.example.test', ''],
        ['https://calendar.example.test', str_repeat('x', 256)],
        ['https://calendar.example.test', "Dienst\nManipulation"],
    ] as [$url, $name]) {
        try {
            $targets->save($url, $name);
            throw new RuntimeException('Ungültige Kalenderdefaults wurden gespeichert.');
        } catch (InvalidArgumentException) {}
        if ($config->values !== $before) throw new RuntimeException('Validierungsfehler verändert AppConfig.');
    }

    $config->resetCounter();
    $config->throwOnWrite = 2;
    try {
        $targets->save('https://neu.example.test', 'Neuer Name');
        throw new RuntimeException('Teilweiser AppConfig-Fehler wurde akzeptiert.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() === 'Teilweiser AppConfig-Fehler wurde akzeptiert.') throw $error;
    }
    if ($targets->adminStatus() !== $saved) throw new RuntimeException('Teilweiser AppConfig-Fehler hinterlässt eine halbe Konfiguration.');

    $config->throwOnWrite = null;
    $config->values['calendar_default_name'] = "\x01kaputt";
    $config->values['calendar_default_kopano_url'] = 'http://unsicher.example.test';
    if ($targets->calendarName() !== 'Filzmann Dienste' || $targets->kopanoUrl() !== '') {
        throw new RuntimeException('Direkt manipulierte AppConfig umgeht sichere markenneutrale Defaults.');
    }

    echo "CalendarTargetConfigTest: OK\n";
}
