<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$templates = [
    'templates/index.php' => ['Filzmann Calendar', 'Settings', 'Filter and compare people', 'Data is being loaded.'],
    'templates/partials/entry-dialog.php' => ['Entry', 'Employee', 'Recurrence', 'Save'],
    'templates/partials/meeting-dialog.php' => ['Find a meeting gap', 'Search participants', 'Search gaps'],
    'templates/partials/settings.php' => ['My shifts in Nextcloud Calendar', 'External calendars', 'My default shift times', 'Connect calendar'],
    'templates/admin.php' => ['Shift calendar synchronisation', 'Kopano and CalDAV', 'Google Calendar OAuth', 'Demo pack'],
];

foreach ($templates as $file => $messages) {
    $source = file_get_contents($root . '/' . $file);
    if ($source === false) throw new RuntimeException("Template fehlt: {$file}");
    foreach ($messages as $message) {
        $quoted = preg_quote($message, '/');
        if (preg_match('/\\$l->t\\(\'' . $quoted . '\'/', $source) !== 1) {
            throw new RuntimeException("{$file} lokalisiert den sichtbaren Quelltext nicht: {$message}");
        }
    }
}

echo "TemplateLocalizationContractTest: OK\n";
