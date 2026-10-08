<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Privacy;

use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class CalendarProcessingMetadataProviderListener implements IEventListener {
    public function __construct(private CalendarProcessingMetadataProvider $provider) {}

    public function handle(Event $event): void {
        if ($event instanceof RegisterProcessingMetadataProvidersEvent) $event->register($this->provider);
    }
}
