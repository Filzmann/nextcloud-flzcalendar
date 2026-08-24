<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Service;

use OCA\AdCalendar\AppInfo\Application;
use OCP\Config\IUserConfig;

/**
 * Zweck: Speichert den bewusst per „Zum Standard machen“ gesetzten persönlichen Kalenderfilter.
 * Vertrag: Unbekannte Personen, Rollen und Bereiche gelangen nicht in den gespeicherten Frontendzustand.
 */
final class CalendarPreferenceService {
    private const FILTER_KEY = 'filter_default';
    private const SHIFT_KEY = 'shift_defaults';
    private const SHIFT_CALENDAR_SYNC_KEY = 'shift_calendar_sync_enabled';

    public function __construct(private IUserConfig $config) {}

    public function filterDefault(string $uid, array $employees, array $roles, array $areas): ?array {
        $raw = $this->config->getValueString($uid, Application::APP_ID, self::FILTER_KEY);
        if ($raw === '') return null;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $this->normalize($decoded, $employees, $roles, $areas) : null;
    }

    public function saveFilterDefault(string $uid, array $filters, array $employees, array $roles, array $areas): array {
        $normalized = $this->normalize($filters, $employees, $roles, $areas);
        $this->config->setValueString($uid, Application::APP_ID, self::FILTER_KEY, json_encode($normalized, JSON_THROW_ON_ERROR));
        return $normalized;
    }

    public function shiftDefaults(string $uid): array {
        return $this->storedShiftDefaults($uid) ?? $this->normalizeShiftDefaults([]);
    }

    /** Liefert null, solange die Vorschlagswerte nie bewusst gespeichert wurden. */
    public function storedShiftDefaults(string $uid): ?array {
        $raw = $this->config->getValueString($uid, Application::APP_ID, self::SHIFT_KEY);
        if ($raw === '') return null;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $this->normalizeShiftDefaults($decoded) : null;
    }

    public function saveShiftDefaults(string $uid, array $defaults): array {
        $normalized = $this->normalizeShiftDefaults($defaults);
        $this->config->setValueString($uid, Application::APP_ID, self::SHIFT_KEY, json_encode($normalized, JSON_THROW_ON_ERROR));
        return $normalized;
    }

    public function shiftCalendarSyncEnabled(string $uid): bool {
        return $this->config->getValueString($uid, Application::APP_ID, self::SHIFT_CALENDAR_SYNC_KEY, '1') === '1';
    }

    public function saveShiftCalendarSyncEnabled(string $uid, bool $enabled): bool {
        $this->config->setValueString($uid, Application::APP_ID, self::SHIFT_CALENDAR_SYNC_KEY, $enabled ? '1' : '0');
        return $enabled;
    }

    /**
     * Datensparsame subjectgebundene Projektion der tatsächlich gespeicherten
     * Benutzerwerte. Ausgewählte Personen werden nur gezählt, weil ihre IDs
     * Daten Dritter sind.
     */
    public function personalDataProjection(string $uid): array {
        $incomplete = false;

        $filter = null;
        $rawFilter = $this->config->getValueString($uid, Application::APP_ID, self::FILTER_KEY);
        if ($rawFilter !== '') {
            $decoded = json_decode($rawFilter, true);
            if (!is_array($decoded)) {
                $incomplete = true;
            } else {
                $filter = [
                    'selectedPeopleCount' => count($this->storedStringList($decoded['people'] ?? [])),
                    'roles' => $this->storedStringList($decoded['roles'] ?? []),
                    'areas' => $this->storedStringList($decoded['areas'] ?? []),
                    'vertical' => filter_var($decoded['vertical'] ?? true, FILTER_VALIDATE_BOOL),
                    'period' => ($decoded['period'] ?? 'week') === 'month' ? 'month' : 'week',
                    'showLeadershipStaff' => filter_var($decoded['showLeadershipStaff'] ?? true, FILTER_VALIDATE_BOOL),
                    'leadershipStaffOnly' => filter_var($decoded['leadershipStaffOnly'] ?? false, FILTER_VALIDATE_BOOL),
                ];
            }
        }

        $shiftDefaults = null;
        $rawShiftDefaults = $this->config->getValueString($uid, Application::APP_ID, self::SHIFT_KEY);
        if ($rawShiftDefaults !== '') {
            $decoded = json_decode($rawShiftDefaults, true);
            if (!is_array($decoded)) {
                $incomplete = true;
            } else {
                $shiftDefaults = $this->normalizeShiftDefaults($decoded);
            }
        }

        $calendarSyncEnabled = null;
        $rawCalendarSync = $this->config->getValueString($uid, Application::APP_ID, self::SHIFT_CALENDAR_SYNC_KEY);
        if ($rawCalendarSync !== '') {
            if (!in_array($rawCalendarSync, ['0', '1'], true)) {
                $incomplete = true;
            } else {
                $calendarSyncEnabled = $rawCalendarSync === '1';
            }
        }

        return [
            'filter' => $filter,
            'shiftDefaults' => $shiftDefaults,
            'calendarSyncEnabled' => $calendarSyncEnabled,
            'incomplete' => $incomplete,
        ];
    }

    /** Liefert ausdrücklich aktivierte Konten; standardmäßig aktive Konten mit Diensten ergänzt der Abgleichservice. @return list<string> */
    public function shiftCalendarSyncEmployeeUids(): array {
        $uids = [];
        foreach ($this->config->getValuesByUsers(Application::APP_ID, self::SHIFT_CALENDAR_SYNC_KEY) as $uid => $enabled) {
            if ((string)$enabled === '1' && trim((string)$uid) !== '') $uids[] = (string)$uid;
        }
        sort($uids, SORT_STRING);
        return array_values(array_unique($uids));
    }

    private function normalize(array $filters, array $employees, array $roles, array $areas): array {
        $people = $this->allowedList($filters['people'] ?? [], $employees);
        $selectedRoles = $this->allowedList($filters['roles'] ?? [], $roles);
        $selectedAreas = $this->allowedList($filters['areas'] ?? [], $areas);
        $showLeadershipStaff = filter_var($filters['showLeadershipStaff'] ?? true, FILTER_VALIDATE_BOOL);
        $leadershipStaffOnlyRequested = filter_var(
            $filters['leadershipStaffOnly'] ?? $filters['empty'] ?? false,
            FILTER_VALIDATE_BOOL,
        );
        return [
            'people' => $people,
            'roles' => $selectedRoles,
            'areas' => $selectedAreas,
            'vertical' => filter_var($filters['vertical'] ?? true, FILTER_VALIDATE_BOOL),
            'period' => ($filters['period'] ?? 'week') === 'month' ? 'month' : 'week',
            'showLeadershipStaff' => $showLeadershipStaff,
            'leadershipStaffOnly' => $leadershipStaffOnlyRequested && $showLeadershipStaff && $people === [] && $selectedRoles === [] && $selectedAreas === [],
        ];
    }

    private function allowedList(mixed $values, array $allowed): array {
        if (!is_array($values)) return [];
        $allowedMap = array_fill_keys(array_map('strval', $allowed), true);
        return array_values(array_unique(array_filter(array_map('strval', $values), static fn(string $value): bool => isset($allowedMap[$value]))));
    }

    private function storedStringList(mixed $values): array {
        if (!is_array($values)) return [];
        $result = [];
        foreach ($values as $value) {
            if (!is_string($value)) continue;
            $value = trim($value);
            if ($value === '' || strlen($value) > 128 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) continue;
            $result[$value] = true;
        }

        return array_keys($result);
    }

    private function normalizeShiftDefaults(array $defaults): array {
        $result = [];
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $value = is_array($defaults[(string)$weekday] ?? $defaults[$weekday] ?? null) ? ($defaults[(string)$weekday] ?? $defaults[$weekday]) : [];
            $result[(string)$weekday] = [
                'enabled' => filter_var($value['enabled'] ?? true, FILTER_VALIDATE_BOOL),
                'start' => $this->time($value['start'] ?? '08:00', '08:00'),
                'end' => $this->time($value['end'] ?? '16:00', '16:00'),
            ];
        }
        return $result;
    }

    private function time(mixed $value, string $fallback): string {
        $value = (string)$value;
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value : $fallback;
    }
}
