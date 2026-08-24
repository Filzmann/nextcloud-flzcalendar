<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

use OCA\AdCalendar\Model\CalendarEntry;
use Psr\Log\LoggerInterface;
use RuntimeException;

/** Verteilt ausgehende Dienste fehlertolerant auf alle bewusst verbundenen externen Provider. */
final class ExternalShiftCalendarPublisher implements ShiftCalendarPublisher {
    public function __construct(
        private ExternalCalendarConnectionStore $connections,
        private CalDavClient $calDav,
        private GoogleCalendarClient $google,
        private LoggerInterface $logger,
    ) {}

    public function replaceAll(string $employeeUid, array $shifts): void {
        $this->each($employeeUid, fn(string $provider, array $connection) => $this->replace($employeeUid, $provider, $connection, $shifts));
    }

    public function publish(CalendarEntry $shift): void {
        $this->each($shift->employeeUid(), function(string $provider, array $connection) use ($shift): void {
            if ($provider === 'google') $this->google->publish($shift->employeeUid(), $connection, $shift);
            else $this->calDav->publish($connection, $shift);
        });
    }

    public function remove(string $employeeUid, int $shiftId): void {
        $this->each($employeeUid, function(string $provider, array $connection) use ($employeeUid, $shiftId): void {
            if ($provider === 'google') $this->google->remove($employeeUid, $connection, $shiftId);
            else $this->calDav->remove($connection, $shiftId);
        });
    }

    public function removeCalendar(string $employeeUid): void {
        $this->each($employeeUid, fn(string $provider, array $connection) => $this->removeConnectionCalendar($employeeUid, $provider, $connection));
    }

    /** @param list<CalendarEntry> $shifts */
    public function replaceProvider(string $employeeUid, string $provider, array $shifts): void {
        $connection = $this->connections->connection($employeeUid, $provider);
        if ($connection === null) throw new RuntimeException('Die externe Kalenderverbindung fehlt.');
        $connection = $this->renamedConnection($employeeUid, $provider, $connection);
        $this->replace($employeeUid, $provider, $connection, $shifts);
    }

    public function removeProviderCalendar(string $employeeUid, string $provider, array $connection): void {
        $this->removeConnectionCalendar($employeeUid, $provider, $connection);
    }

    private function replace(string $employeeUid, string $provider, array $connection, array $shifts): void {
        if ($provider === 'google') $this->google->replaceAll($employeeUid, $connection, $shifts);
        else $this->calDav->replaceAll($connection, $shifts);
    }

    private function removeConnectionCalendar(string $employeeUid, string $provider, array $connection): void {
        if ($provider === 'google') $this->google->removeCalendar($employeeUid, $connection);
        else $this->calDav->removeCalendar($connection);
    }

    private function each(string $employeeUid, callable $operation): void {
        $failed = false;
        foreach ($this->connections->connections($employeeUid) as $provider => $connection) {
            try {
                $connection = $this->renamedConnection($employeeUid, $provider, $connection);
                $operation($provider, $connection);
            } catch (\Throwable $error) {
                $failed = true;
                $this->logger->error('Externer Dienstkalender konnte nicht abgeglichen werden.', ['exceptionClass' => $error::class]);
            }
        }
        if ($failed) throw new RuntimeException('Mindestens ein externer Kalender konnte nicht abgeglichen werden.');
    }

    private function renamedConnection(string $employeeUid, string $provider, array $connection): array {
        $renamed = $provider === 'google'
            ? $this->google->renameCalendar($employeeUid, $connection)
            : $this->calDav->renameCalendar($connection);
        if ($renamed !== $connection) $this->connections->save($employeeUid, $provider, $renamed);
        return $renamed;
    }
}
