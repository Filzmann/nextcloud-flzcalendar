<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Listener;

use OCA\FlzCalendar\Model\CalendarEntry;
use OCA\FlzCalendar\Repository\CalendarEntryRepository;
use OCA\LocalBase\Calendar\ScheduleConflict;
use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Zweck: Meldet bestehende Dienste und Termine read-only als Konflikte an Abwesenheitsprovider.
 * Zusammenspiel: FlzUrlaub sendet ScheduleConflictQueryEvent; dieser Listener liest ausschließlich aus dem Kalender-Repository.
 * Vertrag: Der Listener verändert keine Kalendereinträge und liefert sichere, knappe Konfliktbezeichnungen.
 */
final class ScheduleConflictQueryListener implements IEventListener {
    public function __construct(private CalendarEntryRepository $entries) {}

    public function handle(Event $event): void {
        if (!$event instanceof ScheduleConflictQueryEvent) return;

        foreach ($this->entries->findRange($event->start(), $event->end(), [$event->employeeUid()]) as $entry) {
            $label = $entry->type() === CalendarEntry::TYPE_SHIFT ? 'Dienst/Büro' : 'Termin';
            $event->add(new ScheduleConflict(
                $entry->type(),
                $entry->start(),
                $entry->end(),
                $label,
                'flzcalendar',
            ));
        }
    }
}
