<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use InvalidArgumentException;
use OCA\FlzCalendar\Model\CalendarEntry;

/**
 * Zweck: Ordnet einen Termin genau einem ihn vollständig enthaltenden Dienst zu.
 * Vertrag: Kein Treffer ergibt einen Sperrtermin ohne Parent; mehrere Treffer werden als inkonsistente Dienstplanung abgewiesen.
 */
final class ContainingShiftAssignment {
    /** @param list<CalendarEntry> $parents */
    public function assign(CalendarEntry $entry, array $parents): CalendarEntry {
        if ($entry->type() !== CalendarEntry::TYPE_APPOINTMENT) return $entry;
        if (count($parents) > 1) {
            throw new InvalidArgumentException('Der Termin liegt in mehreren Diensten. Bitte Dienste zuerst korrigieren.');
        }
        $parent = $parents[0] ?? null;
        return CalendarEntry::get(array_replace($entry->toArray(), ['parentEntryId' => $parent?->id()]));
    }
}
