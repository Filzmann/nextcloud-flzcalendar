<?php

declare(strict_types=1);

use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Service\CalendarService;
use OCA\AdCalendar\Service\ShiftCalendarReconciliationService;
use OCA\AdCalendar\Service\TemporaryAdminAccessService;
use OCA\DAV\CalDAV\CalDavBackend;

$objectStorageCalendarTitle = 'FR-08 horizontaler Dienstabgleich';
$postgresqlCalendarTitle = 'FR-04 PostgreSQL Upgrade-Bestand';
$postgresqlRollbackTitle = 'FR-04 PostgreSQL Rollback darf nicht bestehen';

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/adcalendar/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    'grantService' => TemporaryAdminAccessService::class,
    'grantManagerGroups' => ['Datenschutzbeauftragte'],
    'permissionProbe' => static fn(string $uid): bool => OCP\Server::get(CalendarAccessService::class)
        ->canManage('compat-target'),
    'apiSmokes' => [
        ['/index.php/apps/adcalendar/api/week?start=2035-01-01', [200]],
    ],
    'postgresqlUpgradeSeed' => static function (string $uid) use ($postgresqlCalendarTitle, $postgresqlRollbackTitle): void {
        $repository = OCP\Server::get(CalendarEntryRepository::class);
        $entry = CalendarEntry::get([
            'employeeUid' => 'compat-target',
            'start' => '2036-02-03T09:00:00+01:00',
            'end' => '2036-02-03T17:00:00+01:00',
            'type' => CalendarEntry::TYPE_SHIFT,
            'title' => $postgresqlCalendarTitle,
        ]);
        $entryId = $repository->save($entry, $uid);
        if ($repository->find($entryId)?->title() !== $postgresqlCalendarTitle) {
            throw new RuntimeException('Der synthetische AD-Kalender-PostgreSQL-Bestand wurde nicht korrekt angelegt.');
        }

        $rollbackEntry = CalendarEntry::get([
            'employeeUid' => 'compat-target',
            'start' => '2036-02-04T09:00:00+01:00',
            'end' => '2036-02-04T17:00:00+01:00',
            'type' => CalendarEntry::TYPE_SHIFT,
            'title' => $postgresqlRollbackTitle,
        ]);
        try {
            $repository->saveMany([$rollbackEntry, new stdClass()], $uid);
            throw new RuntimeException('Der AD-Kalender-Rollbackfall hat unerwartet committed.');
        } catch (TypeError $error) {
            if (!str_contains($error->getMessage(), stdClass::class)) {
                throw $error;
            }
            // Der zweite ungültige Eintrag muss den bereits geschriebenen ersten Eintrag zurückrollen.
        }
        foreach ($repository->findEntriesForEmployee('compat-target') as $stored) {
            if ($stored->title() === $postgresqlRollbackTitle) {
                throw new RuntimeException('Der AD-Kalender-Rollback hinterließ einen Datensatz.');
            }
        }
    },
    'postgresqlUpgradeVerify' => static function (string $uid) use ($postgresqlCalendarTitle, $postgresqlRollbackTitle): void {
        $repository = OCP\Server::get(CalendarEntryRepository::class);
        $entries = $repository->findEntriesForEmployee('compat-target');
        $matches = array_values(array_filter(
            $entries,
            static fn(CalendarEntry $entry): bool => $entry->title() === $postgresqlCalendarTitle,
        ));
        if (count($matches) !== 1 || $matches[0]->id() === null || $matches[0]->durationMinutes() !== 480) {
            throw new RuntimeException('Der AD-Kalender-Bestand wurde beim PostgreSQL-Upgrade nicht unverändert erhalten.');
        }
        foreach ($entries as $entry) {
            if ($entry->title() === $postgresqlRollbackTitle) {
                throw new RuntimeException('Der zurückgerollte AD-Kalender-Datensatz erschien nach dem Upgrade.');
            }
        }
        $repository->delete((int)$matches[0]->id());
    },
    'objectStorageWebSetup' => static function (string $uid) use ($objectStorageCalendarTitle): void {
        OCP\Server::get(CalendarService::class)->save([
            'employeeUid' => 'compat-target',
            'start' => '2035-01-02T09:00:00+00:00',
            'end' => '2035-01-02T17:00:00+00:00',
            'type' => 'shift',
            'title' => $objectStorageCalendarTitle,
        ], null, $uid);
    },
    'objectStorageJobVerify' => static function (string $uid) use ($objectStorageCalendarTitle): void {
        $employeeUid = 'compat-target';
        $entries = array_values(array_filter(
            OCP\Server::get(CalendarEntryRepository::class)->findEntriesForEmployee($employeeUid),
            static fn($entry): bool => $entry->title() === $objectStorageCalendarTitle,
        ));
        if (count($entries) !== 1 || $entries[0]->id() === null) {
            throw new RuntimeException('Der Jobprozess sieht den im Webprozess gespeicherten AD-Kalenderdienst nicht eindeutig.');
        }
        $entry = $entries[0];
        $publisher = OCP\Server::get(PersonalCalendarPublisher::class);
        $backend = OCP\Server::get(CalDavBackend::class);
        $calendarId = null;
        foreach ($backend->getCalendarsForUser('principals/users/' . $employeeUid) as $calendar) {
            if (str_starts_with((string)($calendar['uri'] ?? ''), 'adcalendar-dienste-')) {
                $calendarId = (int)$calendar['id'];
                break;
            }
        }
        if ($calendarId === null) {
            throw new RuntimeException('Der Webprozess hat keinen privaten AD-Kalender angelegt.');
        }
        $uri = 'adcalendar-shift-' . $entry->id() . '.ics';
        $publisher->removeEntry($entry);
        if ($backend->getCalendarObject($calendarId, $uri) !== null) {
            throw new RuntimeException('Der negative DAV-Reparaturfall konnte nicht hergestellt werden.');
        }
        if (!OCP\Server::get(ShiftCalendarReconciliationService::class)->reconcileEmployee($employeeUid)
            || $backend->getCalendarObject($calendarId, $uri) === null) {
            throw new RuntimeException('Der getrennte Jobprozess hat die fehlende DAV-Projektion nicht wiederhergestellt.');
        }
        OCP\Server::get(CalendarService::class)->delete((int)$entry->id(), '');
        $publisher->removeCalendar($employeeUid);
    },
];
