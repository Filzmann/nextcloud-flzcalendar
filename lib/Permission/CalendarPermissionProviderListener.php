<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Permission;

use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<RegisterPermissionProvidersEvent> */
final class CalendarPermissionProviderListener implements IEventListener {
    public function __construct(private CalendarPermissionProvider $provider) {}
    public function handle(Event $event): void {
        if ($event instanceof RegisterPermissionProvidersEvent) $event->register($this->provider);
    }
}
