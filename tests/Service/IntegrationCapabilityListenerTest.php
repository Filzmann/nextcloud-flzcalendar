<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event { public function __construct() {} } interface IEventListener { public function handle(Event $event): void; } }
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }

namespace {

    use OCA\FlzCalendar\Listener\IntegrationCapabilityQueryListener;
    use OCA\FlzOrganization\PublicApi\V1\IntegrationCapabilities;
    use OCA\FlzOrganization\PublicApi\V1\IntegrationCapabilityQueryEvent;
    use OCP\EventDispatcher\Event;

    $listener = new IntegrationCapabilityQueryListener();
    $listener->handle(new Event());
    $event = new IntegrationCapabilityQueryEvent(IntegrationCapabilities::all());
    $listener->handle($event);

    if ($event->providersFor(IntegrationCapabilities::SCHEDULE_CONFLICT_READ) !== ['flzcalendar']) throw new RuntimeException('Kalender-Konfliktfähigkeit fehlt.');
    if ($event->providersFor(IntegrationCapabilities::SCHEDULE_BLOCK_WRITE) !== ['flzcalendar']) throw new RuntimeException('Kalender-Schreibfähigkeit fehlt.');
    if ($event->isAvailable(IntegrationCapabilities::ABSENCE_READ)) throw new RuntimeException('Kalender meldet eine fremde Fähigkeit.');

    echo "Filzmann Kalender capability listener test passed\n";
}
