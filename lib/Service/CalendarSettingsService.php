<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

/** Zweck: Stellt dem Kalender den gemeinsamen Organisations- und Rechtevertrag read-only bereit. */
final class CalendarSettingsService
{
    public function __construct(private OrganizationRuntime $organization)
    {
    }

    /** @return array<string,bool> */
    public function peerEditing(): array
    {
        $definition = $this->organization->organization();
        if ($definition === null)
        {
            return [];
        }
        $enabled = array_fill_keys($this->organization->peerGroups(), true);
        $result = [];
        foreach ($definition->roles() as $role)
        {
            if (($role['peerEnabled'] ?? false) === true)
            {
                $result[(string)$role['groupId']] = isset($enabled[$role['groupId']]);
            }
        }
        return $result;
    }

    /** @return list<string> */
    public function enabledPeerGroups(): array
    {
        return $this->organization->peerGroups();
    }

    public function organization(): array
    {
        return $this->organization->organization()?->toArray() ?? [];
    }
}
