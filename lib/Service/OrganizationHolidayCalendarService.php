<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use RuntimeException;

/**
 * Read-only holiday projection from the public flz_organization V1 contract.
 */
final class OrganizationHolidayCalendarService
{
    public function __construct(private OrganizationRuntime $organization)
    {
    }

    public function forYear(int $year): object
    {
        $snapshot = $this->organization->holidayCalendar($year);
        if ($snapshot === null)
        {
            throw new RuntimeException('Der Organisations-Ferienkalender ist nicht verfügbar.');
        }
        return $snapshot;
    }

    /**
     * Returns the timezone for the organization.
     * 
     * @return string The IANA timezone identifier
     */
    public function timezone(): string {
        $context = $this->organization->calendarContext();
        if ($context === null)
        {
            throw new RuntimeException('Der Organisations-Kalenderkontext ist nicht verfügbar.');
        }
        return $context->timezone();
    }
}
