<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\FlzCalendar\CalendarSync\ExternalCalendarConnectionStore;
use OCA\FlzCalendar\CalendarSync\ExternalShiftCalendarPublisher;
use OCA\FlzCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\FlzCalendar\Repository\CalendarEntryRepository;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Stellt den vollständigen führenden FLZ-Eintrags- und Urlaubsbestand aktiver persönlicher Nextcloud-Kalender periodisch wieder her.
 * Zukunftsvertrag: Bei bidirektionalem Ausbau bleibt dies der ausgehende Konsistenzschritt nach Import und Konfliktauflösung.
 */
final class ShiftCalendarReconciliationService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private CalendarPreferenceService $preferences,
        private PersonalCalendarPublisher $publisher,
        private ExternalShiftCalendarPublisher $externalPublisher,
        private ExternalCalendarConnectionStore $externalConnections,
        private AbsenceService $absences,
        private CalendarSyncHorizon $horizon,
        private LoggerInterface $logger,
    ) {}

    /** @return array{attempted: int, succeeded: int, failed: int} */
    public function reconcileAll(): array {
        $result = ['attempted' => 0, 'succeeded' => 0, 'failed' => 0];
        [$start, $end] = $this->horizon->range();
        try {
            $absenceEmployeeUids = $this->absences->discover($start, $end);
        } catch (\Throwable $error) {
            $absenceEmployeeUids = [];
            $this->logger->error('Periodische Urlaubs-Discovery ist fehlgeschlagen.', ['exception' => $error]);
        }
        $employeeUids = array_values(array_unique(array_merge(
            $this->entries->findEmployeeUidsWithEntries(),
            $this->preferences->shiftCalendarSyncEmployeeUids(),
            $this->externalConnections->connectedEmployeeUids(),
            $absenceEmployeeUids,
        )));
        sort($employeeUids, SORT_STRING);
        foreach ($employeeUids as $employeeUid) {
            if (!$this->preferences->shiftCalendarSyncEnabled($employeeUid) && !$this->externalConnections->hasConnections($employeeUid)) continue;
            $result['attempted']++;
            if ($this->reconcileEmployee($employeeUid)) $result['succeeded']++;
            else $result['failed']++;
        }
        return $result;
    }

    public function reconcileEmployee(string $employeeUid): bool {
        $native = $this->preferences->shiftCalendarSyncEnabled($employeeUid);
        $external = $this->externalConnections->hasConnections($employeeUid);
        if (!$native && !$external) return false;
        $succeeded = true;
        if ($native) {
            try {
                [$start, $end] = $this->horizon->range();
                $this->publisher->replaceAllContent(
                    $employeeUid,
                    $this->entries->findEntriesForEmployee($employeeUid),
                    $this->absences->query($start, $end, [$employeeUid]),
                );
            } catch (\Throwable $error) {
                $succeeded = false;
                $this->logger->error('Periodischer persönlicher Nextcloud-Kalenderabgleich ist fehlgeschlagen.', ['exception' => $error]);
            }
        }
        if ($external) {
            try {
                $this->externalPublisher->replaceAll($employeeUid, $this->entries->findShiftsForEmployee($employeeUid));
            } catch (\Throwable $error) {
                $succeeded = false;
                $this->logger->error('Periodischer externer Dienstkalenderabgleich ist fehlgeschlagen.', ['exception' => $error]);
            }
        }
        return $succeeded;
    }
}
