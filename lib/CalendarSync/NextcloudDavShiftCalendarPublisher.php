<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCA\LocalBase\Calendar\AbsenceInterval;
use OCA\DAV\CalDAV\CalDavBackend;
use RuntimeException;

/**
 * Zweck: Veröffentlicht AD-Dienste in einem privaten, app-eigenen Nextcloud-DAV-Kalender.
 * Architekturgrenze: Nur diese Klasse kennt die bewusst freigegebene interne OCA\DAV-Schnittstelle.
 * Vertrag: Deterministische URIs erlauben idempotentes Schreiben; fremde Kalenderobjekte bleiben unangetastet.
 */
final class NextcloudDavShiftCalendarPublisher implements ShiftCalendarPublisher, PersonalCalendarPublisher {
    private const CALENDAR_URI_PREFIX = 'adcalendar-dienste-';
    private const OBJECT_URI_PREFIX = 'adcalendar-shift-';

    public function __construct(
        private CalDavBackend $backend,
        private ShiftCalendarEventSerializer $serializer,
        private CalendarTargetConfig $targets,
    ) {}

    public function replaceAll(string $employeeUid, array $shifts): void {
        $this->replaceAllContent($employeeUid, $shifts, []);
    }

    public function replaceAllContent(string $employeeUid, array $entries, array $absences): void {
        $calendarId = $this->calendarId($employeeUid, true);
        $expected = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof CalendarEntry || $entry->employeeUid() !== $employeeUid) {
                throw new RuntimeException('Der Kalenderabgleich enthält einen fremden Eintrag.');
            }
            $expected[$this->serializer->objectUri($entry)] = true;
        }
        foreach ($absences as $absence) {
            if (!$absence instanceof AbsenceInterval || $absence->employeeUid() !== $employeeUid) {
                throw new RuntimeException('Der Kalenderabgleich enthält einen fremden Urlaub.');
            }
            $expected[$this->serializer->absenceObjectUri($absence)] = true;
        }
        foreach ($this->backend->getCalendarObjects($calendarId) as $object) {
            $object = $this->completeObject($calendarId, $object);
            $uri = (string)($object['uri'] ?? '');
            if ($this->isOwnedObject($object) && !isset($expected[$uri])) $this->deleteObject($calendarId, $uri);
        }
        foreach ($entries as $entry) $this->upsertEntry($calendarId, $entry);
        foreach ($absences as $absence) $this->upsertAbsence($calendarId, $absence);
    }

    public function publish(CalendarEntry $entry): void {
        $calendarId = $this->calendarId($entry->employeeUid(), true);
        $this->upsertEntry($calendarId, $entry);
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

    public function removeEntry(CalendarEntry $entry): void {
        if ($entry->id() === null) return;
        $calendarId = $this->calendarId($entry->employeeUid(), false);
        if ($calendarId === null) return;
        $uri = $this->serializer->objectUri($entry);
        $existing = $this->backend->getCalendarObject($calendarId, $uri);
        if ($existing === null) return;
        $this->assertOwnedObject($existing);
        $this->deleteObject($calendarId, $uri);
    }

    public function removeCalendar(string $employeeUid): void {
        $calendarId = $this->calendarId($employeeUid, false);
        if ($calendarId === null) return;
        foreach ($this->backend->getCalendarObjects($calendarId) as $object) {
            $object = $this->completeObject($calendarId, $object);
            $uri = (string)($object['uri'] ?? '');
            if ($this->isOwnedObject($object)) $this->deleteObject($calendarId, $uri);
        }
        if ($this->backend->getCalendarObjects($calendarId) === []) $this->backend->deleteCalendar($calendarId, true);
    }

    private function upsertEntry(int $calendarId, CalendarEntry $entry): void {
        $uri = $this->serializer->objectUri($entry);
        $existing = $this->backend->getCalendarObject($calendarId, $uri);
        if ($existing !== null) $this->assertOwnedObject($existing);
        if ($existing !== null && $this->deletedAt($existing) !== null) {
            $this->deleteObject($calendarId, $uri);
            $existing = null;
        }
        $stamp = $existing === null ? null : $this->dateStamp((string)($existing['calendardata'] ?? ''));
        $data = $this->serializer->serialize($entry, $stamp);
        $this->writeObject($calendarId, $uri, $data, $existing);
    }

    private function upsertAbsence(int $calendarId, AbsenceInterval $absence): void {
        $uri = $this->serializer->absenceObjectUri($absence);
        $existing = $this->backend->getCalendarObject($calendarId, $uri);
        if ($existing !== null) $this->assertOwnedObject($existing);
        if ($existing !== null && $this->deletedAt($existing) !== null) {
            $this->deleteObject($calendarId, $uri);
            $existing = null;
        }
        $stamp = $existing === null ? null : $this->dateStamp((string)($existing['calendardata'] ?? ''));
        $data = $this->serializer->serializeAbsence($absence, $stamp);
        $this->writeObject($calendarId, $uri, $data, $existing);
    }

    private function writeObject(int $calendarId, string $uri, string $data, ?array $existing): void {
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

    private function completeObject(int $calendarId, array $object): array {
        if (array_key_exists('calendardata', $object)) return $object;
        $uri = (string)($object['uri'] ?? '');
        if ($uri === '') return $object;
        return $this->backend->getCalendarObject($calendarId, $uri) ?? $object;
    }

    private function assertOwnedObject(array $object): void {
        if (!$this->isOwnedObject($object)) {
            throw new RuntimeException('Die reservierte AD-Dienst-URI wird bereits von einem fremden Kalenderobjekt verwendet.');
        }
    }

    private function isOwnedObject(array $object): bool {
        $uri = (string)($object['uri'] ?? '');
        $data = str_replace(["\r\n ", "\n "], '', (string)($object['calendardata'] ?? ''));
        if (preg_match('/(?:^|\r?\n)X-AD-CALENDAR-SOURCE:adcalendar(?:\r?\n|$)/', $data) !== 1) return false;
        if (preg_match('/^adcalendar-(shift|appointment)-(\d+)\.ics$/', $uri, $uriMatch) === 1) {
            if (preg_match('/(?:^|\r?\n)X-AD-CALENDAR-ENTRY-ID:(\d+)(?:\r?\n|$)/', $data, $idMatch) !== 1
                || $idMatch[1] !== $uriMatch[2]) return false;
            if ($uriMatch[1] === 'shift' && !str_contains($data, 'X-AD-CALENDAR-ENTRY-TYPE:')) return true;
            return preg_match('/(?:^|\r?\n)X-AD-CALENDAR-ENTRY-TYPE:(shift|appointment)(?:\r?\n|$)/', $data, $typeMatch) === 1
                && $typeMatch[1] === $uriMatch[1];
        }
        if (preg_match('/^adcalendar-absence-([a-f0-9]{64})\.ics$/', $uri, $uriMatch) !== 1) return false;
        return preg_match('/(?:^|\r?\n)X-AD-CALENDAR-ABSENCE-ID:([a-f0-9]{64})(?:\r?\n|$)/', $data, $idMatch) === 1
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
