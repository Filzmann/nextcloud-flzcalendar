<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use DateTimeImmutable;
use DateTimeZone;
use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\FlzCalendar\Repository\CalendarEntryRepository;
use OCA\LocalBase\Service\DemoAccountProvisioningService;

/**
 * Zweck: Installiert das neutrale Kalender-Demo-Pack idempotent und ohne bestehende Konten zu übernehmen.
 * Zusammenspiel: Admin-Endpunkt oder OCC-Command -> CalendarDemoPackService -> LocalBase-Provisioning und CalendarEntryRepository.
 * Vertrag: Konten und Gruppen werden vollständig geprüft, bevor LocalBase die erste Änderung ausführt.
 */
final class CalendarDemoPackService {
    public function __construct(
        private DemoAccountProvisioningService $accounts,
        private CalendarEntryRepository $entries,
        private DemoFixtureCatalog $fixtures,
    ) {}

    /** @return array{accounts:array{createdUsers:int,reusedUsers:int,createdGroups:int},createdCalendars:int,skippedCalendars:int} */
    public function install(): array {
        $fixtures = $this->fixtures->all();
        $accounts = $this->accounts->provision('flz-full-suite-demo', array_map(static fn(array $fixture): array => [
            'uid' => $fixture['uid'],
            'displayName' => $fixture['name'],
            'groups' => $fixture['groups'],
        ], $fixtures));

        $monday = new DateTimeImmutable('monday this week 08:00', new DateTimeZone('Europe/Berlin'));
        $utc = new DateTimeZone('UTC');
        $createdCalendars = 0;
        $skippedCalendars = 0;
        foreach ($fixtures as $index => $fixture) {
            if ($this->entries->existsCreatedByForEmployee('demo-seed', $fixture['uid'], $monday, $monday->modify('+7 days'))) {
                $skippedCalendars++;
                continue;
            }
            $day = $monday->modify('+' . ($index % 5) . ' days');
            $shiftId = $this->entries->save(CalendarEntry::get([
                'employeeUid' => $fixture['uid'],
                'start' => $day->setTimezone($utc),
                'end' => $day->modify('+8 hours')->setTimezone($utc),
                'type' => 'shift',
            ]), 'demo-seed');
            $this->entries->save(CalendarEntry::get([
                'employeeUid' => $fixture['uid'],
                'start' => $day->modify('+2 hours')->setTimezone($utc),
                'end' => $day->modify('+3 hours')->setTimezone($utc),
                'type' => 'appointment',
                'title' => 'Neutraler Teamtermin',
                'parentEntryId' => $shiftId,
            ]), 'demo-seed');
            $this->entries->save(CalendarEntry::get([
                'employeeUid' => $fixture['uid'],
                'start' => $day->modify('+10 hours')->setTimezone($utc),
                'end' => $day->modify('+11 hours')->setTimezone($utc),
                'type' => 'appointment',
                'title' => 'Neutraler Sperrtermin',
            ]), 'demo-seed');
            $createdCalendars++;
        }

        return compact('accounts', 'createdCalendars', 'skippedCalendars');
    }
}
