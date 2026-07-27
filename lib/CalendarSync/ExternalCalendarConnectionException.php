<?php

declare(strict_types=1);

namespace OCA\AdCalendar\CalendarSync;

/** Sichere, für Nutzer*innen verständliche Providerdiagnose ohne interne Verbindungsdetails. */
final class ExternalCalendarConnectionException extends \RuntimeException {
    public function userMessage(string $provider): string {
        return $provider === 'kopano' && $this->getCode() === 405
            ? 'Der Kopano-Betreiber erlaubt an dieser Adresse keine CalDAV-Verbindung (HTTP 405). Bitte wende dich an dessen Administration.'
            : $this->getMessage();
    }
}
