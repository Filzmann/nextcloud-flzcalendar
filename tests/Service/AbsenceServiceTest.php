<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    if (!class_exists(Event::class)) {
        class Event { public function __construct() {} }
    }
    if (!interface_exists(IEventDispatcher::class)) {
        interface IEventDispatcher { public function dispatchTyped(Event $event): Event; }
    }
}

namespace {
    require_once __DIR__ . '/../../../localbase/lib/Calendar/AbsenceInterval.php';
    require_once __DIR__ . '/../../../localbase/lib/Calendar/AbsenceQueryEvent.php';
    require_once __DIR__ . '/../../lib/Service/AbsenceService.php';

    use OCA\AdCalendar\Service\AbsenceService;
    use OCA\LocalBase\Calendar\AbsenceInterval;
    use OCA\LocalBase\Calendar\AbsenceQueryEvent;
    use OCP\EventDispatcher\Event;
    use OCP\EventDispatcher\IEventDispatcher;

    $events = new class implements IEventDispatcher {
        public function dispatchTyped(Event $event): Event {
            if (!$event instanceof AbsenceQueryEvent) return $event;
            $status = $event->employeeUids()[0] === 'planned-person'
                ? AbsenceInterval::STATUS_PLANNED
                : AbsenceInterval::STATUS_APPROVED;
            $event->add(new AbsenceInterval(
                $event->employeeUids()[0],
                new DateTimeImmutable('2026-08-10T00:00:00Z'),
                new DateTimeImmutable('2026-08-11T00:00:00Z'),
                $status,
            ));
            return $event;
        }
    };
    $service = new AbsenceService($events);
    $start = new DateTimeImmutable('2026-08-10T08:00:00Z');
    $end = new DateTimeImmutable('2026-08-10T16:00:00Z');

    foreach (['planned-person', 'approved-person'] as $uid) {
        try {
            $service->assertShiftWritable($uid, $start, $end);
            throw new RuntimeException("Urlaub blockiert den Dienst nicht: {$uid}");
        } catch (InvalidArgumentException $error) {
            if (!str_contains($error->getMessage(), 'Urlaub')) throw $error;
        }
    }

    echo "AbsenceServiceTest: OK\n";
}
