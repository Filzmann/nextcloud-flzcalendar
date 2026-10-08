<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\LocalBase\Organization\FlzOrganizationDefinition;
use OCA\LocalBase\Organization\FlzOrganizationSettingsService;
use OCA\LocalBase\Service\FlzDemoFixtureCatalog;

/** Zweck: Hält den bisherigen Kalender-Payload stabil und delegiert die gemeinsamen Demopersonen an LocalBase. */
final class DemoFixtureCatalog {
    public function __construct(
        private ?FlzOrganizationSettingsService $organization = null,
        private ?FlzOrganizationDefinition $override = null,
        private ?FlzDemoFixtureCatalog $shared = null,
    ) {}

    /** @return list<array{uid:string,name:string,groups:list<string>}> */
    public function all(): array {
        $fixtures = $this->override !== null
            ? (new FlzDemoFixtureCatalog(null, $this->override))->all()
            : ($this->shared ?? new FlzDemoFixtureCatalog($this->organization))->all();

        return array_map(static fn(array $fixture): array => [
            'uid' => $fixture['uid'],
            'name' => $fixture['displayName'],
            'groups' => $fixture['groups'],
        ], $fixtures);
    }
}
