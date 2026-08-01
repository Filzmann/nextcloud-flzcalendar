<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../../lib/Controller/ApiController.php');
$meetingSource = file_get_contents(__DIR__ . '/../../lib/Controller/MeetingController.php');
$routes = file_get_contents(__DIR__ . '/../../appinfo/routes.php');
if ($source === false || $meetingSource === false || $routes === false) throw new RuntimeException('Controllervertrag konnte nicht gelesen werden.');
if (!str_contains($source, 'use DateTimeImmutable;') || !str_contains($source, 'new DateTimeImmutable($start)')) throw new RuntimeException('Wochencontroller löst DateTimeImmutable nicht aus dem globalen Namespace auf.');

foreach (['create', 'update', 'delete'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\b/s', $source)) {
        throw new RuntimeException("{$method} ist nicht als angemeldeter API-Pfad deklariert.");
    }
}
if (preg_match('/#\[NoCSRFRequired\]\s+#\[NoAdminRequired\]\s+public function (create|update|delete)\b/s', $source)) {
    throw new RuntimeException('Ein schreibender Endpunkt umgeht CSRF-Schutz.');
}
foreach (['canView()', 'canManage('] as $guard) {
    if (!str_contains($source, $guard)) throw new RuntimeException("Berechtigungspruefung {$guard} fehlt.");
}
if (!preg_match('/#\[NoAdminRequired\]\s+#\[NoCSRFRequired\]\s+public function range\b/s', $source) || !str_contains($routes, "'api#range'")) {
    throw new RuntimeException('Lesender, geschützter Monatsbereichs-Endpunkt fehlt.');
}
foreach (['saveSettings', 'saveOrganizationSettings'] as $method) if (str_contains($source, "function {$method}")) throw new RuntimeException("Organisationsweite Einstellung {$method} liegt noch in der Fachapp.");
foreach (['preferences', 'savePreferences', 'saveShiftDefaults', 'saveCalendarSync'] as $method) {
    if (!preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\b/s', $source)) {
        throw new RuntimeException("Angemeldeter API-Pfad {$method} fehlt.");
    }
}
if (preg_match('/#\[NoCSRFRequired\]\s+#\[NoAdminRequired\]\s+public function (savePreferences|saveShiftDefaults|saveCalendarSync)\b/s', $source)) {
    throw new RuntimeException('Neue schreibende API-Pfade umgehen den CSRF-Schutz.');
}
foreach (['gaps', 'block', 'update', 'delete'] as $method) if (!preg_match('/#\[NoAdminRequired\]\s+public function ' . $method . '\b/s', $meetingSource)) throw new RuntimeException("Meeting-API {$method} fehlt.");
if (str_contains($meetingSource, 'NoCSRFRequired') || !str_contains($meetingSource, 'foreach ($uids as $uid) if (!$this->access->canManage($uid))') || substr_count($meetingSource, 'foreach ($entries as $entry) if (!$this->access->canManage($entry->employeeUid()))') < 2) throw new RuntimeException('Meeting-API umgeht CSRF oder prüft nicht jeden Zielkalender.');
foreach (["'meeting#gaps'", "'meeting#block'", "'meeting#update'", "'meeting#delete'"] as $route) if (!str_contains($routes, $route)) throw new RuntimeException("Meetingroute fehlt: {$route}");
if (!str_contains($routes, "'api#saveCalendarSync'")) throw new RuntimeException('Persönliche Kalender-Synchronisationsroute fehlt.');
foreach (['currentUser()', 'shiftSync->configure'] as $contract) if (!str_contains($source, $contract)) throw new RuntimeException("Persönlicher Synchronisationsschutz fehlt: {$contract}");
foreach (['meetingGaps', 'blockMeeting'] as $removed) if (str_contains($source, "function {$removed}")) throw new RuntimeException("Meetinglogik liegt noch im allgemeinen ApiController: {$removed}");
foreach (['meeting_managed_together', 'meeting_deleted_together'] as $code) {
    if (!str_contains($source, $code)) throw new RuntimeException("Einzel-API blockiert verknüpfte Meetings nicht mit stabilem Code: {$code}");
}
foreach (['RecurringAppointmentService', "seriesScope === 'series'", 'seriesEntries', 'canManage($seriesEntry->employeeUid())'] as $contract) {
    if (!str_contains($source, $contract)) throw new RuntimeException("Serverseitiger Serienvertrag fehlt: {$contract}");
}
if (str_contains($source, 'Response::STATUS_') || !str_contains($source, 'Http::STATUS_BAD_REQUEST')) {
    throw new RuntimeException('Controller verwendet nicht den Nextcloud-HTTP-Statusvertrag.');
}

echo "ControllerSecuritySmokeTest: OK\n";
