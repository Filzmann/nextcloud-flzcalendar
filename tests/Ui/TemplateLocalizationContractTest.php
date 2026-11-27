<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$templates = [
    'templates/index.php' => ['Filzmann Kalender', 'Einstellungen', 'Filter und Personenvergleich', 'Daten werden geladen.'],
    'templates/partials/entry-dialog.php' => ['Eintrag', 'Mitarbeiter*in', 'Wiederholung', 'Speichern'],
    'templates/partials/meeting-dialog.php' => ['Meetinglücke finden', 'Teilnehmende suchen', 'Lücken suchen'],
    'templates/partials/settings.php' => ['Meine Dienste im Nextcloud-Kalender', 'Externe Kalender', 'Meine Standard-Dienstzeiten', 'Kalender verbinden'],
    'templates/admin.php' => ['Dienstkalender-Abgleich', 'Kopano und CalDAV', 'Google Calendar OAuth', 'Demo-Pack'],
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
