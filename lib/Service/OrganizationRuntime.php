<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCP\App\IAppManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;

/** Fail-closed runtime adapter for the optional flz_organization public V1 contract. */
class OrganizationRuntime
{
    private const APP_ID = 'flz_organization';
    private const CONTRACT_VERSION = '1.0';
    private const CONTRACT = 'OCA\\FlzOrganization\\PublicApi\\V1\\Contract';
    private const ORGANIZATION_SNAPSHOT = 'OCA\\FlzOrganization\\PublicApi\\V1\\OrganizationSnapshot';
    private const ORGANIZATION_SNAPSHOT_SERVICE = 'OCA\\FlzOrganization\\PublicApi\\V1\\OrganizationSnapshotService';
    private const AUTHORIZATION_SERVICE = 'OCA\\FlzOrganization\\PublicApi\\V1\\OrganizationAuthorizationService';
    private const CALENDAR_CONTEXT_SNAPSHOT = 'OCA\\FlzOrganization\\PublicApi\\V1\\CalendarContextSnapshot';
    private const CALENDAR_CONTEXT_PROVIDER = 'OCA\\FlzOrganization\\PublicApi\\V1\\CalendarContextProvider';
    private const HOLIDAY_CALENDAR_SNAPSHOT = 'OCA\\FlzOrganization\\PublicApi\\V1\\HolidayCalendarSnapshot';
    private const HOLIDAY_CALENDAR_PROVIDER = 'OCA\\FlzOrganization\\PublicApi\\V1\\HolidayCalendarProvider';
    private const CALENDAR_EDIT_SCOPE = 'calendar.edit';

    public function __construct(
        private IAppManager $apps,
        private LoggerInterface $logger,
    )
    {
    }

    public function organization(): ?CalendarOrganization
    {
        if (!$this->isAvailable()) return null;

        try {
            $service = $this->resolve(self::ORGANIZATION_SNAPSHOT_SERVICE);
            $serviceClass = self::ORGANIZATION_SNAPSHOT_SERVICE;
            if (!$service instanceof $serviceClass) return null;
            $snapshot = $service->snapshot();
            $snapshotClass = self::ORGANIZATION_SNAPSHOT;
            if (!$snapshot instanceof $snapshotClass
                || $snapshot->contractVersion() !== self::CONTRACT_VERSION
                || !$snapshot->isValid()) {
                return null;
            }
            return new CalendarOrganization($snapshot->definition());
        } catch (Throwable $exception) {
            $this->warn('organization', $exception);
            return null;
        }
    }

    /** @param list<string> $actorGroups @param list<string> $targetGroups */
    public function canManage(string $actorUid, array $actorGroups, string $targetUid, array $targetGroups): bool
    {
        if (!$this->isAvailable()) return false;

        try {
            $service = $this->resolve(self::AUTHORIZATION_SERVICE);
            $serviceClass = self::AUTHORIZATION_SERVICE;
            if (!$service instanceof $serviceClass) return false;
            return $service->canManage(
                $actorUid,
                $actorGroups,
                $targetUid,
                $targetGroups,
                self::CALENDAR_EDIT_SCOPE,
            ) === true;
        } catch (Throwable $exception) {
            $this->warn('authorization', $exception);
            return false;
        }
    }

    /** @return list<string> */
    public function peerGroups(): array
    {
        if (!$this->isAvailable()) return [];

        try {
            $service = $this->resolve(self::AUTHORIZATION_SERVICE);
            $serviceClass = self::AUTHORIZATION_SERVICE;
            if (!$service instanceof $serviceClass) return [];
            $groups = $service->peerGroups(self::CALENDAR_EDIT_SCOPE);
            if (!is_array($groups) || !array_is_list($groups)) return [];
            foreach ($groups as $group) {
                if (!is_string($group) || trim($group) === '') return [];
            }
            return array_values(array_unique($groups));
        } catch (Throwable $exception) {
            $this->warn('peer groups', $exception);
            return [];
        }
    }

    public function calendarContext(): ?object
    {
        if (!$this->isAvailable()) return null;

        try {
            $provider = $this->resolve(self::CALENDAR_CONTEXT_PROVIDER);
            $providerClass = self::CALENDAR_CONTEXT_PROVIDER;
            if (!$provider instanceof $providerClass) return null;
            $snapshot = $provider->context();
            $snapshotClass = self::CALENDAR_CONTEXT_SNAPSHOT;
            if (!$snapshot instanceof $snapshotClass
                || !$this->hasCompatibleSnapshotVersion($snapshot)) {
                return null;
            }
            return $snapshot;
        } catch (Throwable $exception) {
            $this->warn('calendar context', $exception);
            return null;
        }
    }

    public function holidayCalendar(int $year): ?object
    {
        if (!$this->isAvailable()) return null;

        try {
            $provider = $this->resolve(self::HOLIDAY_CALENDAR_PROVIDER);
            $providerClass = self::HOLIDAY_CALENDAR_PROVIDER;
            if (!$provider instanceof $providerClass) return null;
            $snapshot = $provider->calendar($year);
            $snapshotClass = self::HOLIDAY_CALENDAR_SNAPSHOT;
            if (!$snapshot instanceof $snapshotClass
                || !$this->hasCompatibleSnapshotVersion($snapshot)
                || (method_exists($snapshot, 'isUsable') && !$snapshot->isUsable())) {
                return null;
            }
            return $snapshot;
        } catch (Throwable $exception) {
            $this->warn('holiday calendar', $exception);
            return null;
        }
    }

    protected function isContractCompatible(): bool
    {
        foreach ([
            self::CONTRACT,
            self::ORGANIZATION_SNAPSHOT,
            self::ORGANIZATION_SNAPSHOT_SERVICE,
            self::AUTHORIZATION_SERVICE,
            self::CALENDAR_CONTEXT_SNAPSHOT,
            self::CALENDAR_CONTEXT_PROVIDER,
            self::HOLIDAY_CALENDAR_SNAPSHOT,
            self::HOLIDAY_CALENDAR_PROVIDER,
        ] as $class) {
            if (!class_exists($class)) return false;
        }

        $contract = self::CONTRACT;
        return method_exists($contract, 'supports') && $contract::supports(self::CONTRACT_VERSION);
    }

    protected function resolve(string $class): object
    {
        return Server::get($class);
    }

    private function isAvailable(): bool
    {
        try {
            return in_array(self::APP_ID, $this->apps->getEnabledApps(), true)
                && $this->isContractCompatible();
        } catch (Throwable $exception) {
            $this->warn('contract availability', $exception);
            return false;
        }
    }

    private function hasCompatibleSnapshotVersion(object $snapshot): bool
    {
        return !method_exists($snapshot, 'contractVersion')
            || $snapshot->contractVersion() === self::CONTRACT_VERSION;
    }

    private function warn(string $operation, Throwable $exception): void
    {
        $this->logger->warning('Optional organization provider failed closed.', [
            'operation' => $operation,
            'exception' => $exception::class,
        ]);
    }
}
