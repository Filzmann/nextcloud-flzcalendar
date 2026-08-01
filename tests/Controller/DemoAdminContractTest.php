<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root . '/appinfo/routes.php');
$info = file_get_contents($root . '/appinfo/info.xml');
$controller = file_get_contents($root . '/lib/Controller/DemoAdminController.php');
$googleController = file_get_contents($root . '/lib/Controller/GoogleOAuthAdminController.php');
$externalCalendarController = file_get_contents($root . '/lib/Controller/ExternalCalendarAdminController.php');
$calendarDefaultsController = file_get_contents($root . '/lib/Controller/CalendarDefaultsAdminController.php');
$calendarTargets = file_get_contents($root . '/lib/Service/CalendarTargetConfig.php');
$template = file_get_contents($root . '/templates/admin.php');
$script = file_get_contents($root . '/js/admin.js');
if ($routes === false || $info === false || $controller === false || $googleController === false || $externalCalendarController === false || $calendarDefaultsController === false || $calendarTargets === false || $template === false || $script === false) {
    throw new RuntimeException('Kalender-Demo-Adminbestandteile fehlen.');
}
foreach (['/api/admin/demo-pack/install', "'verb' => 'POST'"] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Demo-Route fehlt: {$contract}");
foreach (["'google_oauth_admin#save'", "'google_oauth_admin#remove'", '/api/admin/google-oauth', "'verb' => 'PUT'", "'verb' => 'DELETE'"] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Google-Adminroute fehlt: {$contract}");
foreach (["'external_calendar_admin#testCalDav'", '/api/admin/external-calendars/caldav/test', "'verb' => 'POST'"] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("CalDAV-Adminroute fehlt: {$contract}");
foreach (["'calendar_defaults_admin#save'", '/api/admin/calendar-defaults', "'verb' => 'PUT'"] as $contract) if (!str_contains($routes, $contract)) throw new RuntimeException("Kalenderdefault-Adminroute fehlt: {$contract}");
foreach (['<admin>OCA\\AdCalendar\\Settings\\Admin</admin>', '<admin-section>OCA\\AdCalendar\\Settings\\AdminSection</admin-section>'] as $contract) if (!str_contains($info, $contract)) throw new RuntimeException("Adminregistrierung fehlt: {$contract}");
foreach (['CalendarDemoPackService', 'private function isAdmin()', '$this->groups->isAdmin(', 'Http::STATUS_FORBIDDEN'] as $contract) if (!str_contains($controller, $contract)) throw new RuntimeException("Serverseitiger Demo-Adminschutz fehlt: {$contract}");
if (str_contains($controller, 'NoCSRFRequired')) throw new RuntimeException('Demo-Installation darf den CSRF-Schutz nicht umgehen.');
foreach (['private function isAdmin()', '$this->groups->isAdmin(', 'Http::STATUS_FORBIDDEN', 'saveConfiguration', 'removeConfiguration'] as $contract) if (!str_contains($googleController, $contract)) throw new RuntimeException("Serverseitiger Google-Adminschutz fehlt: {$contract}");
if (str_contains($googleController, 'NoCSRFRequired')) throw new RuntimeException('Google-Administration darf den CSRF-Schutz nicht umgehen.');
foreach (['private function isAdmin()', '$this->groups->isAdmin(', 'Http::STATUS_FORBIDDEN', 'testCalDavConnection'] as $contract) if (!str_contains($externalCalendarController, $contract)) throw new RuntimeException("Serverseitiger CalDAV-Testschutz fehlt: {$contract}");
if (str_contains($externalCalendarController, 'NoCSRFRequired')) throw new RuntimeException('CalDAV-Verbindungstest darf den CSRF-Schutz nicht umgehen.');
foreach (['private function isAdmin()', '$this->groups->isAdmin(', 'Http::STATUS_FORBIDDEN', 'targets->save('] as $contract) if (!str_contains($calendarDefaultsController, $contract)) throw new RuntimeException("Serverseitiger Kalenderdefault-Adminschutz fehlt: {$contract}");
if (str_contains($calendarDefaultsController, 'NoCSRFRequired')) throw new RuntimeException('Kalenderdefault-Administration darf den CSRF-Schutz nicht umgehen.');
foreach (['IAppConfig', 'ExternalCalendarUrlValidator', 'calendar_default_name_history', 'acceptedCalendarNames'] as $contract) {
    if (!str_contains($calendarTargets, $contract)) throw new RuntimeException("Validierter AppConfig-Kalendervertrag fehlt: {$contract}");
}
foreach (['id="adc-demo-confirm"', 'id="adc-demo-install"', 'not installed automatically', 'Shift calendar synchronisation', 'calendarSyncStatus', 'No account or calendar identifiers'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Demo- oder DAV-Adminoberfläche fehlt: {$contract}");
foreach (['id="adc-kopano-caldav-heading"', 'Kopano and CalDAV', 'The Kopano provider must provide CalDAV', 'HTTP 405', 'HTTP 207'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Kopano-CalDAV-Adminhinweis fehlt: {$contract}");
foreach (['id="adc-kopano-test-form"', 'id="adc-kopano-test-url"', 'id="adc-kopano-test-username"', 'id="adc-kopano-test-password"', 'id="adc-kopano-test-status"', 'Test connection', 'Credentials are not stored'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Administratives Kopano-Testformular fehlt: {$contract}");
foreach (['id="adc-calendar-defaults-form"', 'id="adc-calendar-default-kopano-url"', 'id="adc-calendar-default-name"', 'id="adc-calendar-defaults-status"', 'Existing app-owned calendars'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Kalenderdefault-Adminformular fehlt: {$contract}");
foreach (['adc-demo-confirm', 'adc-demo-install', "client.request('/api/admin/demo-pack/install'"] as $contract) if (!str_contains($script, $contract)) throw new RuntimeException("Demo-Admininteraktion fehlt: {$contract}");
foreach (['id="adc-google-oauth-form"', 'id="adc-google-client-id"', 'id="adc-google-client-secret"', 'id="adc-google-redirect-uri"', 'id="adc-google-oauth-remove"', 'autocomplete="new-password"', 'googleOAuth'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Google-Adminoberfläche fehlt: {$contract}");
foreach (['<details class="adc-google-registration-guide">', 'Register a Google app – step by step', 'Google Calendar API', 'Google Auth Platform', 'Internal', 'External', 'https://www.googleapis.com/auth/calendar.app.created', 'Web application', 'Authorised redirect URI', 'Do not add authorised JavaScript origins', 'test users', 'seven days', 'https://console.cloud.google.com/', 'https://developers.google.com/workspace/calendar/api/auth', 'https://developers.google.com/identity/protocols/oauth2/web-server', 'rel="noopener noreferrer"'] as $contract) if (!str_contains($template, $contract)) throw new RuntimeException("Google-Registrierungsanleitung fehlt: {$contract}");
foreach (['initGoogleOAuth', "client.request('/api/admin/google-oauth'", "method: 'PUT'", "method: 'DELETE'", "secret.value = ''", 'navigator.clipboard.writeText'] as $contract) if (!str_contains($script, $contract)) throw new RuntimeException("Google-Admininteraktion fehlt: {$contract}");
foreach (['initCalDavTest', "client.request('/api/admin/external-calendars/caldav/test'", "method: 'POST'", "password.value = ''"] as $contract) if (!str_contains($script, $contract)) throw new RuntimeException("CalDAV-Admininteraktion fehlt: {$contract}");
foreach (['initCalendarDefaults', "client.request('/api/admin/calendar-defaults'", "method: 'PUT'", 'calendarDefaults.calendarName'] as $contract) if (!str_contains($script, $contract)) throw new RuntimeException("Kalenderdefault-Admininteraktion fehlt: {$contract}");

$style = file_get_contents($root . '/css/admin.css');
foreach (['.adc-google-registration-guide', '.adc-google-registration-guide summary:focus-visible', '.adc-google-registration-guide code'] as $contract) if ($style === false || !str_contains($style, $contract)) throw new RuntimeException("Google-Anleitungsdarstellung fehlt: {$contract}");
foreach (['.adc-kopano-test-form', '.adc-kopano-test-status.is-success', '.adc-kopano-test-status.is-error'] as $contract) if ($style === false || !str_contains($style, $contract)) throw new RuntimeException("Kopano-Testdarstellung fehlt: {$contract}");

$settings = file_get_contents($root . '/lib/Settings/Admin.php');
foreach (['ShiftCalendarReconciliationStatusService', 'GoogleOAuthService', 'CalendarTargetConfig', 'IDateTimeFormatter', 'IL10N', "l10n->t('No background run recorded')", "'calendarSyncStatus'", "'lastRunLabel'", "'googleOAuth'", "'calendarDefaults'"] as $contract) if ($settings === false || !str_contains($settings, $contract)) throw new RuntimeException("Aggregierter lokalisierter DAV-, Google- oder Kalenderdefault-Adminstatus fehlt: {$contract}");

echo "DemoAdminContractTest: OK\n";
