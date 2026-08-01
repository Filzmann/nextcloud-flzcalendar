<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCA\DAV\CalDAV\CalDavBackend;
use RuntimeException;

/**
 * Zweck: Veröffentlicht AD-Dienste in einem privaten, app-eigenen Nextcloud-DAV-Kalender.
 * Architekturgrenze: Nur diese Klasse kennt die bewusst freigegebene interne OCA\DAV-Schnittstelle.
 * Vertrag: Deterministische URIs erlauben idempotentes Schreiben; fremde Kalenderobjekte bleiben unangetastet.
 */
final class NextcloudDavShiftCalendarPublisher implements ShiftCalendarPublisher {
    private const CALENDAR_URI_PREFIX = 'adcalendar-dienste-';
    private const OBJECT_URI_PREFIX = 'adcalendar-shift-';

    public function __construct(
        private CalDavBackend $backend,
        private ShiftCalendarEventSerializer $serializer,
        private CalendarTargetConfig $targets,
    ) {}

    public function replaceAll(string $employeeUid, array $shifts): void {
        $calendarId = $this->calendarId($employeeUid, true);
        $expected = [];
        foreach ($shifts as $shift) {
            if (!$shift instanceof CalendarEntry || $shift->employeeUid() !== $employeeUid) {
                throw new RuntimeException('Der Dienstabgleich enthält eine fremde Person.');
            }
            $expected[$this->serializer->objectUri($shift)] = true;
        }
        foreach ($this->backend->getCalendarObjects($calendarId) as $object) {
            $uri = (string)($object['uri'] ?? '');
            if ($this->isOwnedObject($object) && !isset($expected[$uri])) $this->deleteObject($calendarId, $uri);
        }
        foreach ($shifts as $shift) $this->upsert($calendarId, $shift);
    }

    public function publish(CalendarEntry $shift): void {
        $calendarId = $this->calendarId($shift->employeeUid(), true);
        $this->upsert($calendarId, $shift);
    }

    public function remove(string $employeeUid, int $shiftId): void {
        $calendarId = $this->calendarId($employeeUid, false);
        if ($calendarId === null) return;
        $uri = self::OBJECT_URI_PREFIX . $shiftId . '.ics';
        $existing = $this->backend->getCalendarObject($calendarId, $uri);
        if ($existing === null) return;
        $this->assertOwnedObject($existing);
        $this->deleteObject($calendarId, $uri);
    }

    public function removeCalendar(string $employeeUid): void {
        $calendarId = $this->calendarId($employeeUid, false);
        if ($calendarId === null) return;
        foreach ($this->backend->getCalendarObjects($calendarId) as $object) {
            $uri = (string)($object['uri'] ?? '');
            if ($this->isOwnedObject($object)) $this->deleteObject($calendarId, $uri);
        }
        if ($this->backend->getCalendarObjects($calendarId) === []) $this->backend->deleteCalendar($calendarId, true);
    }

    private function upsert(int $calendarId, CalendarEntry $shift): void {
        $uri = $this->serializer->objectUri($shift);
        $existing = $this->backend->getCalendarObject($calendarId, $uri);
        if ($existing !== null) $this->assertOwnedObject($existing);
        if ($existing !== null && $this->deletedAt($existing) !== null) {
            $this->deleteObject($calendarId, $uri);
            $existing = null;
        }
        $stamp = $existing === null ? null : $this->dateStamp((string)($existing['calendardata'] ?? ''));
        $data = $this->serializer->serialize($shift, $stamp);
        if ($existing === null) {
            $this->backend->createCalendarObject($calendarId, $uri, $data);
            return;
        }
        if ((string)($existing['calendardata'] ?? '') !== $data) $this->backend->updateCalendarObject($calendarId, $uri, $data);
    }

    private function calendarId(string $employeeUid, bool $create): ?int {
        $principal = 'principals/users/' . $employeeUid;
        $uri = self::CALENDAR_URI_PREFIX . substr(hash('sha256', $employeeUid), 0, 16);
        $calendarName = $this->targets->calendarName();
        foreach ($this->backend->getCalendarsForUser($principal) as $calendar) {
            if (($calendar['principaluri'] ?? '') !== $principal || ($calendar['uri'] ?? '') !== $uri) continue;
            $displayName = (string)($calendar['{DAV:}displayname'] ?? '');
            if (!in_array($displayName, $this->targets->acceptedCalendarNames(), true)) {
                throw new RuntimeException('Die reservierte AD-Kalender-URI wird bereits von einem fremden Kalender verwendet.');
            }
            if ($displayName !== $calendarName) {
                $this->backend->updateCalendar((int)$calendar['id'], ['{DAV:}displayname' => $calendarName]);
            }
            return (int)$calendar['id'];
        }
        if (!$create) return null;
        return (int)$this->backend->createCalendar($principal, $uri, [
            '{DAV:}displayname' => $calendarName,
            'components' => 'VEVENT',
        ]);
    }

    private function deleteObject(int $calendarId, string $uri): void {
        $this->backend->deleteCalendarObject($calendarId, $uri, CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
    }

    private function assertOwnedObject(array $object): void {
        if (!$this->isOwnedObject($object)) {
            throw new RuntimeException('Die reservierte AD-Dienst-URI wird bereits von einem fremden Kalenderobjekt verwendet.');
        }
    }

    private function isOwnedObject(array $object): bool {
        $uri = (string)($object['uri'] ?? '');
        $data = (string)($object['calendardata'] ?? '');
        if (preg_match('/^' . preg_quote(self::OBJECT_URI_PREFIX, '/') . '(\d+)\.ics$/', $uri, $uriMatch) !== 1) return false;
        if (preg_match('/(?:^|\r?\n)X-AD-CALENDAR-SOURCE:adcalendar(?:\r?\n|$)/', $data) !== 1) return false;
        return preg_match('/(?:^|\r?\n)X-AD-CALENDAR-ENTRY-ID:(\d+)(?:\r?\n|$)/', $data, $idMatch) === 1
            && $idMatch[1] === $uriMatch[1];
    }

    private function dateStamp(string $calendarData): ?string {
        return preg_match('/(?:^|\r?\n)DTSTAMP:(\d{8}T\d{6}Z)(?:\r?\n|$)/', $calendarData, $match) === 1 ? $match[1] : null;
    }

    private function deletedAt(array $object): mixed {
        foreach ($object as $key => $value) if (str_ends_with((string)$key, '}deleted-at')) return $value;
        return null;
    }
}
