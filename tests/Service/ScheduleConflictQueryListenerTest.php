<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    if (!class_exists(Event::class)) { class Event { public function __construct() {} } }
    if (!interface_exists(IEventListener::class)) { interface IEventListener { public function handle(Event $event): void; } }
}

namespace OCA\FlzCalendar\Repository {
    use OCA\FlzCalendar\Model\CalendarEntry;

    final class CalendarEntryRepository {
        /** @return list<CalendarEntry> */
        public function findRange(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids): array {
            return [
                CalendarEntry::get([
                    'employeeUid' => $uids[0],
                    'start' => '2026-08-10T08:00:00Z',
                    'end' => '2026-08-10T16:00:00Z',
                    'type' => CalendarEntry::TYPE_SHIFT,
                ]),
                CalendarEntry::get([
                    'employeeUid' => $uids[0],
                    'start' => '2026-08-10T17:00:00Z',
                    'end' => '2026-08-10T18:00:00Z',
                    'type' => CalendarEntry::TYPE_APPOINTMENT,
                    'title' => 'Vertraulicher Termin',
                ]),
            ];
        }
    }
}

namespace {
    use OCA\FlzCalendar\Listener\ScheduleConflictQueryListener;
    use OCA\FlzCalendar\Repository\CalendarEntryRepository;
    use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;

    $start = new DateTimeImmutable('2026-08-10T00:00:00Z');
    $end = $start->modify('+1 day');
    $listener = new ScheduleConflictQueryListener(new CalendarEntryRepository());

    $external = new ScheduleConflictQueryEvent('person-a', $start, $end, 'flzplaner');
    $listener->handle($external);
    $conflicts = array_map(static fn($conflict): array => $conflict->toArray(), $external->conflicts());
    if (($conflicts[0]['label'] ?? null) !== 'Dienst/Büro' || ($conflicts[0]['sourceAppId'] ?? null) !== 'flzcalendar') {
        throw new RuntimeException('Kalenderdienste werden nicht mit sicherer Bezeichnung und Provider-ID veröffentlicht.');
    }
    if (($conflicts[1]['label'] ?? null) !== 'Termin' || str_contains(json_encode($conflicts), 'Vertraulicher Termin')) {
        throw new RuntimeException('Private Kalendertitel verlassen den Providervertrag.');
    }

    $self = new ScheduleConflictQueryEvent('person-a', $start, $end, 'flzcalendar');
    $listener->handle($self);
    if ($self->conflicts() !== []) {
        throw new RuntimeException('Der Kalender meldet der eigenen App weiterhin Eigenkonflikte.');
    }

    echo "ScheduleConflictQueryListenerTest: OK\n";
}
