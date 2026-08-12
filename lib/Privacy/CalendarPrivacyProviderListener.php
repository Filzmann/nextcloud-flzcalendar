<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Privacy;

use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class CalendarPrivacyProviderListener implements IEventListener {
    public function __construct(private CalendarPersonalDataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof PersonalDataProviderRegistryEvent) $event->register($this->provider);
    }
}
