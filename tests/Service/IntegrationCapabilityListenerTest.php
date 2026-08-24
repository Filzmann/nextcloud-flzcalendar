<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event { public function __construct() {} } interface IEventListener { public function handle(Event $event): void; } }
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }

namespace {

    use OCA\AdCalendar\Listener\IntegrationCapabilityQueryListener;
    use OCA\LocalBase\Integration\AdIntegrationCapabilities;
    use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;
    use OCP\EventDispatcher\Event;

    $listener = new IntegrationCapabilityQueryListener();
    $listener->handle(new Event());
    $event = new IntegrationCapabilityQueryEvent(AdIntegrationCapabilities::all());
    $listener->handle($event);

    if ($event->providersFor(AdIntegrationCapabilities::SCHEDULE_CONFLICT_READ) !== ['adcalendar']) throw new RuntimeException('Kalender-Konfliktfähigkeit fehlt.');
    if ($event->providersFor(AdIntegrationCapabilities::SCHEDULE_BLOCK_WRITE) !== ['adcalendar']) throw new RuntimeException('Kalender-Schreibfähigkeit fehlt.');
    if ($event->isAvailable(AdIntegrationCapabilities::ABSENCE_READ)) throw new RuntimeException('Kalender meldet eine fremde Fähigkeit.');

    echo "AD Kalender capability listener test passed\n";
}
