<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use InvalidArgumentException;
use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\CalendarSync\ExternalCalendarUrlValidator;
use OCP\IAppConfig;

/** Zentrale, validierte AppConfig-Quelle für nicht geheime Kalenderdefaults. */
final class CalendarTargetConfig {
    public const DEFAULT_KOPANO_URL = 'https://mail.adberlin.org/';
    public const DEFAULT_CALENDAR_NAME = 'AD Dienste';
    private const KOPANO_URL_KEY = 'calendar_default_kopano_url';
    private const CALENDAR_NAME_KEY = 'calendar_default_name';
    private const CALENDAR_NAME_HISTORY_KEY = 'calendar_default_name_history';
    private const MAX_CALENDAR_NAME_LENGTH = 255;

    public function __construct(private IAppConfig $config, private ExternalCalendarUrlValidator $urls) {}

    public function kopanoUrl(): string {
        try {
            return $this->urls->normalize($this->config->getValueString(
                Application::APP_ID,
                self::KOPANO_URL_KEY,
                self::DEFAULT_KOPANO_URL,
                true,
            ));
        } catch (InvalidArgumentException) {
            return self::DEFAULT_KOPANO_URL;
        }
    }

    public function calendarName(): string {
        try {
            return $this->validatedName($this->config->getValueString(
                Application::APP_ID,
                self::CALENDAR_NAME_KEY,
                self::DEFAULT_CALENDAR_NAME,
                true,
            ));
        } catch (InvalidArgumentException) {
            return self::DEFAULT_CALENDAR_NAME;
        }
    }

    /** @return array{kopanoUrl:string,calendarName:string} */
    public function adminStatus(): array {
        return ['kopanoUrl' => $this->kopanoUrl(), 'calendarName' => $this->calendarName()];
    }

    /** @return array{kopanoUrl:string,calendarName:string} */
    public function save(string $kopanoUrl, string $calendarName): array {
        $kopanoUrl = $this->urls->normalize($kopanoUrl);
        $calendarName = $this->validatedName($calendarName);
        $previous = $this->adminStatus();
        $previousHistory = $this->config->getValueString(Application::APP_ID, self::CALENDAR_NAME_HISTORY_KEY, '[]', true);
        $history = $this->history($previousHistory);
        $history[] = $previous['calendarName'];
        $history[] = $calendarName;
        $history = array_slice(array_values(array_unique($history)), -32);
        $encodedHistory = json_encode($history, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        try {
            $this->write(self::KOPANO_URL_KEY, $kopanoUrl);
            $this->write(self::CALENDAR_NAME_KEY, $calendarName);
            $this->write(self::CALENDAR_NAME_HISTORY_KEY, $encodedHistory);
        } catch (\Throwable $error) {
            try {
                $this->write(self::KOPANO_URL_KEY, $previous['kopanoUrl']);
                $this->write(self::CALENDAR_NAME_KEY, $previous['calendarName']);
                $this->write(self::CALENDAR_NAME_HISTORY_KEY, $previousHistory);
            } catch (\Throwable) {}
            throw $error;
        }
        return $this->adminStatus();
    }

    /** @return list<string> */
    public function acceptedCalendarNames(): array {
        $values = [self::DEFAULT_CALENDAR_NAME, $this->calendarName()];
        $stored = $this->config->getValueString(Application::APP_ID, self::CALENDAR_NAME_HISTORY_KEY, '[]', true);
        foreach ($this->history($stored) as $name) {
            try { $values[] = $this->validatedName($name); } catch (InvalidArgumentException) {}
        }
        return array_values(array_unique($values));
    }

    private function validatedName(string $value): string {
        $value = trim($value);
        $length = preg_match_all('/./us', $value);
        if ($value === '' || $length === false || $length > self::MAX_CALENDAR_NAME_LENGTH) {
            throw new InvalidArgumentException('Der sichtbare Kalendername muss zwischen 1 und 255 Zeichen lang sein.');
        }
        $controls = preg_match('/[\x00-\x1F\x7F]/u', $value);
        if ($controls !== 0) throw new InvalidArgumentException('Der sichtbare Kalendername enthält unzulässige Steuerzeichen.');
        return $value;
    }

    /** @return list<string> */
    private function history(string $value): array {
        try { $decoded = json_decode($value, true, 16, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { return []; }
        if (!is_array($decoded)) return [];
        return array_values(array_filter($decoded, static fn(mixed $name): bool => is_string($name)));
    }

    private function write(string $key, string $value): void {
        $this->config->setValueString(Application::APP_ID, $key, $value, true);
    }
}
