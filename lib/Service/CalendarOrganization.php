<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use UnexpectedValueException;

/** Read-only calendar projection of the public organization definition. */
final class CalendarOrganization
{
    /** @param array<string, mixed> $definition */
    public function __construct(private array $definition)
    {
        if (!is_int($definition['version'] ?? null)
            || $definition['version'] < 1
            || !is_string($definition['staffBlockLabel'] ?? null)
            || trim($definition['staffBlockLabel']) === ''
            || !is_array($definition['roles'] ?? null)
            || !is_array($definition['areas'] ?? null)
            || !is_array($definition['hierarchy'] ?? null)) {
            throw new UnexpectedValueException('Invalid public organization definition.');
        }

        $this->assertRoleMappings($definition['roles']);
        $this->assertAreaMappings($definition['areas']);
        $this->assertHierarchy($definition['hierarchy']);
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return $this->definition;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->definition;
    }

    /** @return array<string, array<string, mixed>> */
    public function roles(): array
    {
        return $this->definition['roles'];
    }

    /** @return array<string, array<string, mixed>> */
    public function areas(): array
    {
        return $this->definition['areas'];
    }

    /** @return array<string, list<string>> */
    public function hierarchy(): array
    {
        return $this->definition['hierarchy'];
    }

    /** @return list<string> */
    public function roleGroupIds(?callable $filter = null): array
    {
        $roles = $filter === null ? $this->roles() : array_filter($this->roles(), $filter);
        return $this->groupIds($roles);
    }

    /** @return list<string> */
    public function areaGroupIds(): array
    {
        return $this->groupIds($this->areas());
    }

    /** @return list<string> */
    public function roleKeysForGroups(array $groupIds): array
    {
        return $this->keysForGroups($this->roles(), $groupIds);
    }

    /** @return list<string> */
    public function areaKeysForGroups(array $groupIds): array
    {
        return $this->keysForGroups($this->areas(), $groupIds);
    }

    public function roleGroupId(string $key): ?string
    {
        return isset($this->roles()[$key]['groupId']) ? (string)$this->roles()[$key]['groupId'] : null;
    }

    public function areaGroupId(string $key): ?string
    {
        return isset($this->areas()[$key]['groupId']) ? (string)$this->areas()[$key]['groupId'] : null;
    }

    public function roleLabel(string $key): string
    {
        return (string)($this->roles()[$key]['label'] ?? $key);
    }

    public function roleIsAreaScopedByGroup(string $groupId): bool
    {
        foreach ($this->roles() as $role)
        {
            if ($role['groupId'] === $groupId)
            {
                return (bool)($role['areaScoped'] ?? false);
            }
        }
        return false;
    }

    public function roleManagementIsAreaScoped(string $key): bool
    {
        return (bool)($this->roles()[$key]['managementAreaScoped'] ?? false);
    }

    /** @param array<mixed> $mappings */
    private function assertRoleMappings(array $mappings): void
    {
        foreach ($mappings as $key => $mapping) {
            if (!is_string($key)
                || trim($key) === ''
                || !is_array($mapping)
                || !is_string($mapping['groupId'] ?? null)
                || trim($mapping['groupId']) === ''
                || !is_string($mapping['label'] ?? null)
                || trim($mapping['label']) === ''
                || !is_int($mapping['sortOrder'] ?? null)
                || !is_bool($mapping['calendarVisible'] ?? null)
                || !is_bool($mapping['areaScoped'] ?? null)
                || !is_bool($mapping['managementAreaScoped'] ?? null)
                || !is_bool($mapping['peerEnabled'] ?? null)) {
                throw new UnexpectedValueException('Invalid public organization group mapping.');
            }
        }
    }

    /** @param array<mixed> $mappings */
    private function assertAreaMappings(array $mappings): void
    {
        foreach ($mappings as $key => $mapping)
        {
            if (!is_string($key)
                || trim($key) === ''
                || !is_array($mapping)
                || !is_string($mapping['groupId'] ?? null)
                || trim($mapping['groupId']) === ''
                || !is_string($mapping['label'] ?? null)
                || trim($mapping['label']) === ''
                || !is_string($mapping['shortLabel'] ?? null)
                || trim($mapping['shortLabel']) === ''
                || !is_int($mapping['sortOrder'] ?? null))
            {
                throw new UnexpectedValueException('Invalid public organization area mapping.');
            }
        }
    }

    /** @param array<mixed> $hierarchy */
    private function assertHierarchy(array $hierarchy): void
    {
        $roles = $this->definition['roles'];
        foreach ($hierarchy as $actor => $targets)
        {
            if (!is_string($actor) || !isset($roles[$actor]) || !is_array($targets))
            {
                throw new UnexpectedValueException('Invalid public organization hierarchy.');
            }
            foreach ($targets as $target)
            {
                if (!is_string($target) || !isset($roles[$target]))
                {
                    throw new UnexpectedValueException('Invalid public organization hierarchy target.');
                }
            }
        }
    }

    /** @param array<mixed> $mappings @return list<string> */
    private function groupIds(array $mappings): array
    {
        return array_values(array_map(
            static fn(array $mapping): string => trim($mapping['groupId']),
            $mappings,
        ));
    }

    /** @param array<string, array<string, mixed>> $mappings @return list<string> */
    private function keysForGroups(array $mappings, array $groupIds): array
    {
        $lookup = array_fill_keys(array_map('strval', $groupIds), true);
        $keys = [];
        foreach ($mappings as $key => $mapping)
        {
            if (isset($lookup[$mapping['groupId']]))
            {
                $keys[] = $key;
            }
        }
        return $keys;
    }
}
