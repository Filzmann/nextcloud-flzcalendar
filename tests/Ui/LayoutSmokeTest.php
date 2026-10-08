<?php

declare(strict_types=1);

$indexTemplate = file_get_contents(__DIR__ . '/../../templates/index.php');
$partials = array_map(static fn(string $name): string|false => file_get_contents(__DIR__ . '/../../templates/partials/' . $name . '.php'), ['settings', 'entry-dialog', 'meeting-dialog']);
$template = $indexTemplate === false || in_array(false, $partials, true) ? false : $indexTemplate . implode('', $partials);
$css = file_get_contents(__DIR__ . '/../../css/style.css');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
if ($template === false || $css === false || $info === false) throw new RuntimeException('UI-Dateien konnten nicht gelesen werden.');
$l = (object)['t' => '$l->t'];
foreach (['partials/settings', 'partials/entry-dialog', 'partials/meeting-dialog'] as $partial) if (!str_contains($indexTemplate, "echo \$this->inc('{$partial}')")) throw new RuntimeException("Template-Partial fehlt: {$partial}");
if (str_contains($info, '<app>') || str_contains($info, '<navigations>')) throw new RuntimeException('Standalone-Appvertrag fehlt.');
foreach (['role="tablist"', 'id="flz-calendar-tab-calendar"', 'id="flz-calendar-tab-settings"', 'id="flz-calendar-settings-view"', 'id="flz-calendar-shift-defaults-form"', 'id="flz-calendar-calendar-sync-form"', 'id="flz-calendar-calendar-sync-enabled"', 'Synchronisation is enabled by default', 'appointments and absences are not synchronised', 'changes in the target calendar are overwritten', 'id="flz-calendar-external-calendars-heading"', 'data-external-connect="kopano"', 'data-external-connect="google"', 'data-external-connect="apple"', 'data-external-connect="manual"', 'The Kopano provider must allow CalDAV', 'id="flz-calendar-external-calendar-dialog"', 'Credentials are stored encrypted', '<details class="flz-calendar-filters">', 'id="flz-calendar-filter-status"', 'id="flz-calendar-save-default"', 'id="flz-calendar-reset-selection"', "$l->t('Reset selection')", 'id="flz-calendar-entry-dialog"', 'id="flz-calendar-recurrence-fields"', 'id="flz-calendar-recurrence-frequency"', 'id="flz-calendar-recurrence-interval"', 'id="flz-calendar-recurrence-until"', 'name="flz-calendar-recurrence-weekday"', 'id="flz-calendar-meeting-dialog"', 'id="flz-calendar-meeting-duration"', 'id="flz-calendar-meeting-title"', 'class="flz-calendar-overview"', 'class="flz-calendar-overview-header"', 'class="flz-calendar-button-icon icon-calendar-dark" aria-hidden="true"', "\\OCP\\Util::addScript('flzcalendar', 'models/organization')", "\\OCP\\Util::addScript('flzcalendar', 'modules/calendar-date')", "\\OCP\\Util::addScript('flzcalendar', 'modules/calendar-state')", "\\OCP\\Util::addScript('flzcalendar', 'modules/entry-workflow')", "\\OCP\\Util::addScript('flzcalendar', 'modules/meeting-capabilities')", "\\OCP\\Util::addScript('flzcalendar', 'components/calendar-filters')", "\\OCP\\Util::addScript('flzcalendar', 'components/calendar-cell')", "\\OCP\\Util::addScript('flzcalendar', 'components/entry-dialog')", "\\OCP\\Util::addScript('flzcalendar', 'components/meeting-finder')", "\\OCP\\Util::addScript('flzcalendar', 'components/shift-defaults')", "\\OCP\\Util::addScript('flzcalendar', 'components/shift-calendar-sync')", "\\OCP\\Util::addScript('flzcalendar', 'components/external-calendars')", "\\OCP\\Util::addScript('flzcalendar', 'components/tab-navigation')", "\\OCP\\Util::addScript('flzcalendar', 'components/week-navigation')", "\\OCP\\Util::addScript('flzcalendar', 'components/week-table')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Kompakter Filtervertrag fehlt: {$contract}");
}
foreach (["\\OCP\\Util::addScript('localbase', 'api/api-client')", "\\OCP\\Util::addScript('localbase', 'models/model')", "\\OCP\\Util::addScript('localbase', 'repositories/repository')", "\\OCP\\Util::addScript('localbase', 'ui/ui')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("LocalBase-UI-Vertrag fehlt: {$contract}");
}
foreach (['id="flz-calendar-period-week"', 'id="flz-calendar-period-month"', 'id="flz-calendar-month-number"', 'id="flz-calendar-calendar-tables"', "$l->t('View period')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Monatsansicht-Vertrag fehlt: {$contract}");
}
foreach (['id="flz-calendar-back-to-top"', 'class="flz-calendar-back-to-top"', "$l->t('Back to top')"] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Mobiler Rücksprung fehlt: {$contract}");
}
if (!str_contains($template, "\\OCP\\Util::addScript('flzcalendar', 'modules/calendar-timeline')")) throw new RuntimeException('Zeitachsenmodul fehlt im Template.');
if (!str_contains($template, "\\OCP\\Util::addScript('flzcalendar', 'modules/holiday-calendar')")) throw new RuntimeException('Datengetriebenes Feiertagsmodul wird nicht vor der Kalendermatrix geladen.');
if (str_contains($template, 'berlin-public-holidays')) throw new RuntimeException('Manuelle Berliner Feiertagsberechnung wird weiterhin geladen.');
foreach (['data-orgsuite data-suite="flz" data-current-app="flzcalendar"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
if (str_contains($template, "addScript('orgsuite'") || str_contains($template, "addStyle('orgsuite'")) throw new RuntimeException('Direkte OrgSuite-Assetkopplung vorhanden.');
if (preg_match('/^\\s*(?:script|style)\\s*\\(/m', $template) === 1) throw new RuntimeException('Veralteter globaler Templatehelfer gefunden.');
if (!str_contains($template, 'appear as fixed shifts in the calendar')) throw new RuntimeException('Standarddienst-Erklaerung fehlt in den Einstellungen.');
if (preg_match('/\.flz-calendar-app\s*\{[^}]*width:\s*100%[^}]*height:\s*100%/s', $css) !== 1) throw new RuntimeException('App-Root nutzt die verfügbare Fensterbreite nicht vollständig.');
foreach (['height: 100%', 'min-height: 0', 'overflow-y: auto', 'overflow-x: hidden', 'background: var(--color-main-background)', '.flz-calendar-app [hidden] { display: none !important; }', 'overflow: auto; scrollbar-gutter: stable both-edges;', 'width: max-content', 'min-width: 0', 'table-layout: auto', '.flz-calendar-filter-grid', '.flz-calendar-selection-actions', 'height: auto !important', '.flz-calendar-dialog:not([open])', 'max-height: calc(100vh - 24px)', '.flz-calendar-dialog button:focus-visible', '.flz-calendar-recurrence__options', '.flz-calendar-quick-add', '.flz-calendar-quick-add[data-tooltip]::after', '.flz-calendar-meeting-people', '.flz-calendar-tabs', '.flz-calendar-shift-default-row', '.flz-calendar-provider-grid', '.flz-calendar-provider-card', '.flz-calendar-external-fields', '.flz-calendar-overview-header', 'white-space: nowrap', '.flz-calendar-settings-view { display: grid; gap: 12px; width: 100%; max-width: none', '.flz-calendar-entry--blocked { border: 2px solid var(--color-error)', 'background: var(--color-error)', 'color: var(--color-error-text)'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Scroll-/Layoutvertrag fehlt: {$contract}");
}
foreach (['.flz-calendar-cell-entries { display: grid', 'repeating-linear-gradient'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Zeitachsen-Layoutvertrag fehlt: {$contract}");
}
foreach (['.flz-calendar-calendar tbody th[scope="row"]', '.flz-calendar-person-heading', 'position: sticky', 'inset-inline-start: 0', '.flz-calendar-weekend', '.flz-calendar-holiday', '.flz-calendar-outside-month', '.flz-calendar-period-matrix'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Fixierter Monatslayoutvertrag fehlt: {$contract}");
}
foreach (['.flz-calendar-calendar th.flz-calendar-compact-day', '.flz-calendar-calendar td.flz-calendar-compact-day', '.flz-calendar-cell-entries:empty', 'min-width: 4.5rem', 'min-height: 0'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Adaptiver Sondertagvertrag fehlt: {$contract}");
}
foreach (['.flz-calendar-app { display: flex; flex-direction: column;', '#flz-calendar-calendar-view { display: flex; flex: 1 1 auto; min-height: 0; flex-direction: column;', '.flz-calendar-overview { display: flex; flex: 1 1 auto; min-height: 0; flex-direction: column;', '.flz-calendar-calendar-tables, .flz-calendar-period-matrix { flex: 1 1 auto; min-height: 0;', '.flz-calendar-calendar thead th.flz-calendar-compact-day { width: 4.5rem; min-width: 4.5rem; max-width: 4.5rem;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Ständig zugänglicher Tabellen-Scrollvertrag fehlt: {$contract}");
}
foreach (['.flz-calendar-navigation button { max-width: 100%; height: auto; white-space: normal; overflow-wrap: anywhere;', '.flz-calendar-period-toggle { display: inline-flex; flex-wrap: wrap; max-width: 100%;'] as $contract) {
    if (!str_contains($css, $contract)) {
        throw new RuntimeException("Lange übersetzte Navigationsbeschriftungen sind nicht layoutstabil: {$contract}");
    }
}
foreach (['.flz-calendar-calendar-tables { display: flex; overflow: hidden;', '.flz-calendar-period-matrix { display: flex; min-width: 0; overflow: hidden;', '.flz-calendar-table-wrap { flex: 1 1 auto; width: 100%; max-width: 100%; min-width: 0; height: auto; max-height: none; overflow: auto;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Viewport-fester horizontaler Scrollvertrag fehlt: {$contract}");
}
foreach (['.flz-calendar-mobile-calendar { display: none;', '@media (max-width: 700px)', '.flz-calendar-table-wrap { display: none;', '.flz-calendar-mobile-calendar { display: grid;', '.flz-calendar-mobile-day > summary', '.flz-calendar-mobile-holiday', '.flz-calendar-mobile-person', '.flz-calendar-mobile-cell .flz-calendar-icon-button', '.flz-calendar-navigation #flz-calendar-toggle-view { display: none;', 'min-height: 44px', 'min-width: 44px !important'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Echte mobile Kalenderdarstellung fehlt: {$contract}");
}
foreach (['.flz-calendar-back-to-top { display: none;', '#flz-calendar-calendar-view, .flz-calendar-overview, .flz-calendar-calendar-tables, .flz-calendar-period-matrix { flex: none; min-height: auto; overflow: visible;', '.flz-calendar-back-to-top { display: inline-flex; position: fixed;', 'min-width: 44px;', 'min-height: 44px;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Einzelner mobiler Vertikalscroller oder Rücksprung fehlt: {$contract}");
}
foreach (['grid-template-areas:', '"period period"', '"previous next"', '#flz-calendar-previous-period { grid-area: previous;', '#flz-calendar-next-period { grid-area: next;', '.flz-calendar-tabs button,', '.flz-calendar-filter-grid button,', '.flz-calendar-dialog__actions button { min-height: 44px;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Mobile Navigation oder Touch-Ziele sind nicht stabil: {$contract}");
}
foreach (['.flz-calendar-app input:focus-visible', '.flz-calendar-app select:focus-visible', '.flz-calendar-app summary:focus-visible', 'outline: 3px solid var(--color-primary-element)', '.flz-calendar-dialog__hint {', 'background: var(--color-error)', '.flz-calendar-entry__title { display: block; overflow: visible', '.flz-calendar-calendar thead th { position: sticky; top: 0; z-index: 20', '.flz-calendar-group-heading { position: sticky; inset-inline-start: 0;'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Abnahmefähiger Fokus-/Kontrast-/Sticky-Vertrag fehlt: {$contract}");
}
if (!str_contains($css, '.flz-calendar-dialog__hint:empty { display: none; }')) {
    throw new RuntimeException('Leere Dialogfehlermeldungen bleiben als bedeutungsloser rosa Balken sichtbar.');
}
if (!str_contains($template, 'id="flz-calendar-type" type="hidden"')) throw new RuntimeException('Der durch die Aktion festgelegte Eintragstyp wird weiterhin redundant ausgewählt.');
if (!str_contains($template, 'id="flz-calendar-employee" type="hidden"') || !str_contains($template, 'id="flz-calendar-employee-name"') || str_contains($template, '<select id="flz-calendar-employee"')) {
    throw new RuntimeException('Die bereits durch die Kalenderzelle festgelegte Person bleibt im Eintragsdialog auswählbar.');
}
if (!str_contains($css, '.flz-calendar-readonly-field { display: grid; grid-column: 1 / -1;') || !str_contains($css, '.flz-calendar-readonly-field output { display: block;')) {
    throw new RuntimeException('Die feste Personendarstellung besitzt keinen stabilen, dialogbreiten Feldrahmen.');
}
if (!str_contains($css, 'max-height: calc(100dvh - var(--header-height, 50px))')) {
    throw new RuntimeException('Der App-Root wächst über den sichtbaren Nextcloud-Inhaltsbereich hinaus.');
}
echo "LayoutSmokeTest: OK\n";
