<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllers = [
    'ApiController.php',
    'CalendarDefaultsAdminController.php',
    'DemoAdminController.php',
    'ExternalCalendarAdminController.php',
    'ExternalCalendarController.php',
    'GoogleOAuthAdminController.php',
    'MeetingController.php',
];

foreach ($controllers as $file) {
    $source = file_get_contents($root . '/lib/Controller/' . $file);
    if ($source === false || !str_contains($source, 'LocalizedErrorResponseFactory')) {
        throw new RuntimeException("{$file} verwendet nicht den zentralen lokalisierten API-Fehlervertrag.");
    }
    if (preg_match("/new JSONResponse\\(\\s*\\[\\s*['\"]error['\"]\\s*=>/", $source) === 1) {
        throw new RuntimeException("{$file} liefert noch einen Fehler ohne stabilen maschinenlesbaren Code.");
    }
    if (preg_match("/errors->create\\([^,]+,\\s*\\$[^,]+->getMessage\\(/", $source) === 1) {
        throw new RuntimeException("{$file} veröffentlicht noch einen internen Exceptiontext als L10N-Schlüssel.");
    }
}

echo "LocalizedApiErrorContractTest: OK\n";
