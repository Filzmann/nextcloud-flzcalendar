<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\FlzOrganization\PublicApi\V1\HolidayCalendarProvider as FlzOrganizationHolidayCalendarProvider;
use OCA\LocalBase\Calendar\HolidayCalendarService as LocalBaseHolidayCalendarService;
use OCP\App\IAppManager;
use OCP\Server;

/**
 * Holiday Calendar Service Adapter.
 * 
 * Priorität:
 * 1. flz_organization (neue kanonische Quelle für Kalenderkontext und Feiertage)
 * 2. localbase (historischer Fallback für Kompatibilität)
 * 
 * Diese Klasse implementiert die gleiche Schnittstelle wie LocalBaseHolidayCalendarService
 * und dient als Drop-in-Ersatz, um flz_organization als primäre Quelle zu nutzen.
 */
class OrganizationHolidayCalendarService extends LocalBaseHolidayCalendarService {
    private const FLZ_ORGANIZATION_CLASS = 'OCA\\FlzOrganization\\PublicApi\\V1\\HolidayCalendarProvider';
    private const LOCALBASE_CLASS = 'OCA\\LocalBase\\Calendar\\HolidayCalendarProvider';

    public function __construct(
        IAppManager $apps,
        private ?LocalBaseHolidayCalendarService $fallbackService = null
    ) {
        // We extend LocalBaseHolidayCalendarService but override its behavior
        // This is a transitional solution until all consumers use flz_organization directly
        parent::__construct($apps);
    }

    /**
     * Liefert Feiertage für ein bestimmtes Jahr.
     * 
     * @param int $year Das Jahr
     * @return array<int, array<int, string>> Array von Monaten zu Tagen zu Feiertagsnamen
     */
    public function holidaysForYear(int $year): array {
        // 1. Versuche flz_organization (neue kanonische Quelle)
        $flzOrgResult = $this->tryFlzOrganization($year);
        if ($flzOrgResult !== null) {
            return $flzOrgResult;
        }

        // 2. Fallback zu LocalBase (historische Kompatibilität)
        return $this->fallbackService !== null
            ? $this->fallbackService->holidaysForYear($year)
            : [];
    }

    /**
     * Versucht, Feiertage von flz_organization zu holen.
     */
    private function tryFlzOrganization(int $year): ?array {
        if (!in_array('flz_organization', $this->apps->getEnabledApps(), true)) {
            return null;
        }

        if (!class_exists(self::FLZ_ORGANIZATION_CLASS)) {
            return null;
        }

        try {
            $provider = Server::get(self::FLZ_ORGANIZATION_CLASS);
            $snapshot = $provider->calendar($year);
            
            if ($snapshot->contractVersion() !== '1.0' || !$snapshot->isUsable()) {
                return null;
            }

            // Convert flz_organization snapshot to LocalBase format
            // LocalBase format: array<int, array<int, string>> [month][day] = holiday name
            $holidays = [];
            foreach ($snapshot->publicHolidayDates() as $date => $label) {
                $dateTime = new \DateTimeImmutable($date);
                $month = (int)$dateTime->format('n');
                $day = (int)$dateTime->format('j');
                $holidays[$month][$day] = $label;
            }

            return $holidays;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Returns the timezone for the organization.
     * 
     * @return string The IANA timezone identifier
     */
    public function timezone(): string {
        // Try flz_organization first
        if (in_array('flz_organization', $this->apps->getEnabledApps(), true)
            && class_exists(self::FLZ_ORGANIZATION_CLASS)) {
            try {
                $provider = Server::get(self::FLZ_ORGANIZATION_CLASS);
                $snapshot = $provider->calendar(2024); // Any year for timezone
                if ($snapshot->contractVersion() === '1.0' && $snapshot->isUsable()) {
                    return $snapshot->timezone();
                }
            } catch (\Throwable) {
                // Fall through to fallback
            }
        }

        // Fallback to LocalBase or default
        if ($this->fallbackService !== null) {
            return $this->fallbackService->timezone();
        }

        return 'Europe/Berlin';
    }
}
