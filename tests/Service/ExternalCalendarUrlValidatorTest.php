<?php

declare(strict_types=1);


use OCA\AdCalendar\CalendarSync\ExternalCalendarUrlValidator;

$validator = new ExternalCalendarUrlValidator();

if ($validator->normalize('https://mail.adberlin.org') !== 'https://mail.adberlin.org/') {
    throw new RuntimeException('Die Kopano-Vorgabe wird nicht als HTTPS-CalDAV-Basis normalisiert.');
}
if ($validator->normalize(' https://calendar.example.test/dav ') !== 'https://calendar.example.test/dav/') {
    throw new RuntimeException('Ein erlaubter CalDAV-Pfad wird nicht stabil normalisiert.');
}

foreach (['http://calendar.example.test', 'https://127.0.0.1/dav', 'https://user:secret@example.test/dav', 'https://example.test/dav?token=secret'] as $url) {
    try {
        $validator->normalize($url);
        throw new RuntimeException("Unsichere CalDAV-Adresse wurde akzeptiert: {$url}");
    } catch (InvalidArgumentException) {
    }
}

try {
    $validator->sameOrigin('https://calendar.example.test/dav/', 'https://evil.example.test/dav/');
    throw new RuntimeException('Provider-Discovery darf Zugangsdaten nicht an einen fremden Ursprung weiterreichen.');
} catch (InvalidArgumentException) {
}

echo "ExternalCalendarUrlValidatorTest: OK\n";
