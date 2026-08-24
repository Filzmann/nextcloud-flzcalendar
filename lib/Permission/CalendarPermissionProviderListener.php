<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Permission;

use OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;

final class CalendarPermissionProviderListener {
    public function __construct(private CalendarPermissionProvider $provider) {}
    public function handle(object $event): void {
        if ($event instanceof RegisterPermissionProvidersEvent) $event->register($this->provider);
    }
}
