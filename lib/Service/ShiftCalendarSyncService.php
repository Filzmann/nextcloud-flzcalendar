<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use InvalidArgumentException;
use OCA\FlzCalendar\CalendarSync\ExternalCalendarConnectionStore;
use OCA\FlzCalendar\CalendarSync\ExternalShiftCalendarPublisher;
use OCA\FlzCalendar\CalendarSync\PersonalCalendarPublisher;
use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\FlzCalendar\Repository\CalendarEntryRepository;
use Psr\Log\LoggerInterface;

/** Zweck: Orchestriert den standardmäßig aktiven persönlichen Abgleich, Opt-out und die fehlertolerante ausgehende Dienstveröffentlichung. */
final class ShiftCalendarSyncService {
    public function __construct(
        private CalendarEntryRepository $entries,
        private CalendarPreferenceService $preferences,
        private PersonalCalendarPublisher $publisher,
        private ExternalShiftCalendarPublisher $externalPublisher,
        private ExternalCalendarConnectionStore $externalConnections,
        private AbsenceService $absences,
        private CalendarSyncHorizon $horizon,
        private CalendarTargetConfig $calendarTargets,
        private LoggerInterface $logger,
    ) {}

    public function status(string $employeeUid): array {
        return [
            'enabled' => trim($employeeUid) !== '' && $this->preferences->shiftCalendarSyncEnabled($employeeUid),
            'calendarName' => $this->calendarTargets->calendarName(),
        ];
    }

    public function configure(string $employeeUid, bool $enabled): array {
        if (trim($employeeUid) === '') throw new InvalidArgumentException('Die angemeldete Person fehlt.');
        if ($enabled) {
            [$start, $end] = $this->horizon->range();
            $this->publisher->replaceAllContent(
                $employeeUid,
                $this->entries->findEntriesForEmployee($employeeUid),
                $this->absences->query($start, $end, [$employeeUid]),
            );
            $this->preferences->saveShiftCalendarSyncEnabled($employeeUid, true);
        } else {
            $this->publisher->removeCalendar($employeeUid);
            $this->preferences->saveShiftCalendarSyncEnabled($employeeUid, false);
        }
        return $this->status($employeeUid);
    }

    public function publish(CalendarEntry $entry): bool {
        if ($entry->id() === null) return false;
        $native = $this->preferences->shiftCalendarSyncEnabled($entry->employeeUid());
        $external = $entry->type() === CalendarEntry::TYPE_SHIFT
            && $this->externalConnections->hasConnections($entry->employeeUid());
        if (!$native && !$external) return false;
        $attempted = false;
        $succeeded = true;
        if ($native) {
            $attempted = true;
            try { $this->publisher->publish($entry); }
            catch (\Throwable $error) { $succeeded = false; $this->logFailure('Eintrag konnte nicht in den privaten Nextcloud-Kalender übertragen werden.', $entry, $error); }
        }
        if ($external) {
            $attempted = true;
            try { $this->externalPublisher->publish($entry); }
            catch (\Throwable $error) { $succeeded = false; $this->logFailure('Dienst konnte nicht in alle externen Kalender übertragen werden.', $entry, $error); }
        }
        return $attempted && $succeeded;
    }

    public function remove(CalendarEntry $entry): bool {
        if ($entry->id() === null) return false;
        $native = $this->preferences->shiftCalendarSyncEnabled($entry->employeeUid());
        $external = $entry->type() === CalendarEntry::TYPE_SHIFT
            && $this->externalConnections->hasConnections($entry->employeeUid());
        if (!$native && !$external) return false;
        $attempted = false;
        $succeeded = true;
        if ($native) {
            $attempted = true;
            try { $this->publisher->removeEntry($entry); }
            catch (\Throwable $error) { $succeeded = false; $this->logFailure('Eintrag konnte nicht aus dem privaten Nextcloud-Kalender entfernt werden.', $entry, $error); }
        }
        if ($external) {
            $attempted = true;
            try { $this->externalPublisher->remove($entry->employeeUid(), (int)$entry->id()); }
            catch (\Throwable $error) { $succeeded = false; $this->logFailure('Dienst konnte nicht aus allen externen Kalendern entfernt werden.', $entry, $error); }
        }
        return $attempted && $succeeded;
    }

    private function logFailure(string $message, CalendarEntry $entry, \Throwable $error): void {
        $this->logger->error($message, ['entryId' => $entry->id(), 'exception' => $error]);
    }
}
