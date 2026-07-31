<?php

declare(strict_types=1);

$indexTemplate = file_get_contents(__DIR__ . '/../../templates/index.php');
$partials = array_map(static fn(string $name): string|false => file_get_contents(__DIR__ . '/../../templates/partials/' . $name . '.php'), ['settings', 'entry-dialog', 'meeting-dialog']);
$template = $indexTemplate === false || in_array(false, $partials, true) ? false : $indexTemplate . implode('', $partials);
$css = file_get_contents(__DIR__ . '/../../css/style.css');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
if ($template === false || $css === false || $info === false) throw new RuntimeException('UI-Dateien konnten nicht gelesen werden.');
foreach (['partials/settings', 'partials/entry-dialog', 'partials/meeting-dialog'] as $partial) if (!str_contains($indexTemplate, "echo \$this->inc('{$partial}')")) throw new RuntimeException("Template-Partial fehlt: {$partial}");
if (str_contains($info, '<app>') || str_contains($info, '<navigations>')) throw new RuntimeException('Standalone-Appvertrag fehlt.');
foreach (['role="tablist"', 'id="adc-tab-calendar"', 'id="adc-tab-settings"', 'id="adc-settings-view"', 'id="adc-shift-defaults-form"', 'id="adc-calendar-sync-form"', 'id="adc-calendar-sync-enabled"', 'Der Abgleich ist standardmäßig aktiv', 'Termine und Urlaube werden nicht synchronisiert', 'Änderungen im Zielkalender werden überschrieben', 'id="adc-external-calendars-heading"', 'data-external-connect="kopano"', 'data-external-connect="google"', 'data-external-connect="apple"', 'data-external-connect="manual"', 'https://mail.adberlin.org', 'Der Kopano-Betreiber muss CalDAV', 'id="adc-external-calendar-dialog"', 'Zugangsdaten werden verschlüsselt', '<details class="adc-filters">', 'id="adc-filter-status"', 'id="adc-save-default"', 'id="adc-reset-selection"', 'Auswahl zurücksetzen', 'id="adc-entry-dialog"', 'id="adc-recurrence-fields"', 'id="adc-recurrence-frequency"', 'id="adc-recurrence-interval"', 'id="adc-recurrence-until"', 'name="adc-recurrence-weekday"', 'id="adc-meeting-dialog"', 'id="adc-meeting-duration"', 'id="adc-meeting-title"', 'class="adc-overview"', 'class="adc-overview-header"', 'class="adc-button-icon icon-calendar-dark" aria-hidden="true"', "\\OCP\\Util::addScript('adcalendar', 'models/organization')", "\\OCP\\Util::addScript('adcalendar', 'modules/calendar-date')", "\\OCP\\Util::addScript('adcalendar', 'modules/calendar-state')", "\\OCP\\Util::addScript('adcalendar', 'modules/entry-workflow')", "\\OCP\\Util::addScript('adcalendar', 'modules/meeting-capabilities')", "\\OCP\\Util::addScript('adcalendar', 'components/calendar-filters')", "\\OCP\\Util::addScript('adcalendar', 'components/calendar-cell')", "\\OCP\\Util::addScript('adcalendar', 'components/entry-dialog')", "\\OCP\\Util::addScript('adcalendar', 'components/meeting-finder')", "\\OCP\\Util::addScript('adcalendar', 'components/shift-defaults')", "\\OCP\\Util::addScript('adcalendar', 'components/shift-calendar-sync')", "\\OCP\\Util::addScript('adcalendar', 'components/external-calendars')", "\\OCP\\Util::addScript('adcalendar', 'components/tab-navigation')", "\\OCP\\Util::addScript('adcalendar', 'components/week-navigation')", "\\OCP\\Util::addScript('adcalendar', 'components/week-table')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Kompakter Filtervertrag fehlt: {$contract}");
}
foreach (["\\OCP\\Util::addScript('localbase', 'api/api-client')", "\\OCP\\Util::addScript('localbase', 'models/model')", "\\OCP\\Util::addScript('localbase', 'repositories/repository')", "\\OCP\\Util::addScript('localbase', 'ui/ui')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("LocalBase-UI-Vertrag fehlt: {$contract}");
}
foreach (['id="adc-period-week"', 'id="adc-period-month"', 'id="adc-month-number"', 'id="adc-calendar-tables"', 'aria-label="Ansichtszeitraum"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Monatsansicht-Vertrag fehlt: {$contract}");
}
if (!str_contains($template, "\\OCP\\Util::addScript('adcalendar', 'modules/calendar-timeline')")) throw new RuntimeException('Zeitachsenmodul fehlt im Template.');
if (!str_contains($template, "\\OCP\\Util::addScript('adcalendar', 'modules/holiday-calendar')")) throw new RuntimeException('Datengetriebenes Feiertagsmodul wird nicht vor der Kalendermatrix geladen.');
if (str_contains($template, 'berlin-public-holidays')) throw new RuntimeException('Manuelle Berliner Feiertagsberechnung wird weiterhin geladen.');
foreach (['data-orgsuite data-suite="ad" data-current-app="adcalendar"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
if (str_contains($template, "addScript('orgsuite'") || str_contains($template, "addStyle('orgsuite'")) throw new RuntimeException('Direkte OrgSuite-Assetkopplung vorhanden.');
if (preg_match('/^\\s*(?:script|style)\\s*\\(/m', $template) === 1) throw new RuntimeException('Veralteter globaler Templatehelfer gefunden.');
if (!str_contains($template, 'erscheinen als feste Dienste im Kalender')) throw new RuntimeException('Standarddienst-Erklaerung fehlt in den Einstellungen.');
if (preg_match('/\.adc-app\s*\{[^}]*width:\s*100%[^}]*height:\s*100%/s', $css) !== 1) throw new RuntimeException('App-Root nutzt die verfügbare Fensterbreite nicht vollständig.');
foreach (['height: 100%', 'min-height: 0', 'overflow-y: auto', 'overflow-x: hidden', 'background: var(--color-main-background)', '.adc-app [hidden] { display: none !important; }', 'overflow: auto; scrollbar-gutter: stable both-edges;', 'width: max-content', 'min-width: 0', 'table-layout: auto', '.adc-filter-grid', '.adc-selection-actions', 'height: auto !important', '.adc-dialog:not([open])', 'max-height: calc(100vh - 24px)', '.adc-dialog button:focus-visible', '.adc-recurrence__options', '.adc-quick-add', '.adc-quick-add[data-tooltip]::after', '.adc-meeting-people', '.adc-tabs', '.adc-shift-default-row', '.adc-provider-grid', '.adc-provider-card', '.adc-external-fields', '.adc-overview-header', 'white-space: nowrap', '.adc-settings-view { display: grid; gap: 12px; width: 100%; max-width: none', '.adc-entry--blocked { border: 2px solid var(--color-error)', 'background: var(--color-error)', 'color: var(--color-error-text)'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Scroll-/Layoutvertrag fehlt: {$contract}");
}
foreach (['.adc-cell-entries { display: grid', 'repeating-linear-gradient'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Zeitachsen-Layoutvertrag fehlt: {$contract}");
}
foreach (['.adc-calendar tbody th[scope="row"]', '.adc-person-heading', 'position: sticky', 'inset-inline-start: 0', '.adc-weekend', '.adc-holiday', '.adc-outside-month', '.adc-period-matrix'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Fixierter Monatslayoutvertrag fehlt: {$contract}");
}
foreach (['.adc-calendar th.adc-compact-day', '.adc-calendar td.adc-compact-day', '.adc-cell-entries:empty', 'min-width: 4.5rem', 'min-height: 0'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Adaptiver Sondertagvertrag fehlt: {$contract}");
}
foreach (['.adc-app { display: flex; flex-direction: column;', '#adc-calendar-view { display: flex; flex: 1 1 auto; min-height: 0; flex-direction: column;', '.adc-overview { display: flex; flex: 1 1 auto; min-height: 0; flex-direction: column;', '.adc-calendar-tables, .adc-period-matrix { flex: 1 1 auto; min-height: 0;', '.adc-calendar thead th.adc-compact-day { width: 4.5rem; min-width: 4.5rem; max-width: 4.5rem;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Ständig zugänglicher Tabellen-Scrollvertrag fehlt: {$contract}");
}
foreach (['.adc-calendar-tables { display: flex; overflow: hidden;', '.adc-period-matrix { display: flex; min-width: 0; overflow: hidden;', '.adc-table-wrap { flex: 1 1 auto; width: 100%; max-width: 100%; min-width: 0; height: auto; max-height: none; overflow: auto;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Viewport-fester horizontaler Scrollvertrag fehlt: {$contract}");
}
if (!str_contains($css, 'max-height: calc(100dvh - var(--header-height, 50px))')) {
    throw new RuntimeException('Der App-Root wächst über den sichtbaren Nextcloud-Inhaltsbereich hinaus.');
}
echo "LayoutSmokeTest: OK\n";
