<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('localbase', 'models/model');
\OCP\Util::addScript('localbase', 'repositories/repository');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('adcalendar', 'models/calendar-entry');
\OCP\Util::addScript('adcalendar', 'models/organization');
\OCP\Util::addScript('adcalendar', 'repositories/calendar-repository');
\OCP\Util::addScript('adcalendar', 'modules/calendar-date');
\OCP\Util::addScript('adcalendar', 'modules/holiday-calendar');
\OCP\Util::addScript('adcalendar', 'modules/calendar-state');
\OCP\Util::addScript('adcalendar', 'modules/calendar-timeline');
\OCP\Util::addScript('adcalendar', 'modules/entry-workflow');
\OCP\Util::addScript('adcalendar', 'modules/meeting-capabilities');
\OCP\Util::addScript('adcalendar', 'components/calendar-filters');
\OCP\Util::addScript('adcalendar', 'components/calendar-cell');
\OCP\Util::addScript('adcalendar', 'components/entry-dialog');
\OCP\Util::addScript('adcalendar', 'components/meeting-finder');
\OCP\Util::addScript('adcalendar', 'components/shift-defaults');
\OCP\Util::addScript('adcalendar', 'components/shift-calendar-sync');
\OCP\Util::addScript('adcalendar', 'components/external-calendars');
\OCP\Util::addScript('adcalendar', 'components/tab-navigation');
\OCP\Util::addScript('adcalendar', 'components/week-navigation');
\OCP\Util::addScript('adcalendar', 'components/week-table');
\OCP\Util::addScript('adcalendar', 'main');
\OCP\Util::addStyle('adcalendar', 'style');
?>
<div id="adcalendar-app" class="adc-app">
    <div class="orgsuite-host" data-orgsuite data-suite="ad" data-current-app="adcalendar"></div>
    <header class="adc-header">
        <div>
            <h1>AD Kalender</h1>
            <p>Dienste, Termine und Sperrtermine im Wochen- oder Monatsüberblick</p>
        </div>
    </header>
    <div id="adc-notice" role="status" aria-live="polite"></div>
    <nav class="adc-tabs" role="tablist" aria-label="AD Kalender Bereiche">
        <button type="button" id="adc-tab-calendar" role="tab" aria-controls="adc-calendar-view" aria-selected="true">Kalender</button>
        <button type="button" id="adc-tab-settings" role="tab" aria-controls="adc-settings-view" aria-selected="false">Einstellungen</button>
    </nav>
    <section id="adc-calendar-view" role="tabpanel" aria-labelledby="adc-tab-calendar">
      <details class="adc-filters">
        <summary id="adc-filter-heading">Filter und Personenvergleich <span id="adc-filter-status" class="adc-filter-status">Alle Personen</span></summary>
        <div class="adc-filter-grid" aria-labelledby="adc-filter-heading">
            <fieldset><legend>Rollen</legend><div id="adc-role-filters"></div></fieldset>
            <fieldset><legend>Bereiche</legend><div id="adc-area-filters"></div></fieldset>
            <div class="adc-person-filter">
                <label for="adc-person-search">Person suchen</label>
                <input id="adc-person-search" type="search" autocomplete="off" aria-controls="adc-search-results">
                <ul id="adc-search-results" class="adc-search-results"></ul>
            </div>
            <div class="adc-selection-filter">
                <strong>Ausgewählte Personen</strong>
                <ul id="adc-selected-people" class="adc-selected-people"><li>Keine explizite Auswahl – Gruppenfilter gelten.</li></ul>
                <div class="adc-selection-actions">
                    <button type="button" id="adc-reset-selection" hidden><span class="adc-button-icon icon-close" aria-hidden="true"></span><span>Auswahl zurücksetzen</span></button>
                    <button type="button" id="adc-open-meeting-finder" disabled><span class="adc-button-icon icon-calendar-dark" aria-hidden="true"></span><span>Meetinglücke finden</span></button>
                </div>
            </div>
            <div class="adc-filter-actions">
                <button type="button" id="adc-save-default">Zum Standard machen</button>
            </div>
        </div>
      </details>
      <section class="adc-overview" aria-labelledby="adc-overview-heading">
        <div class="adc-overview-header">
            <h2 id="adc-overview-heading">Wochenplan</h2>
            <nav aria-label="Kalendernavigation" class="adc-navigation">
                <div class="adc-period-toggle" role="group" aria-label="Ansichtszeitraum">
                    <button type="button" id="adc-period-week" aria-pressed="true">Woche</button>
                    <button type="button" id="adc-period-month" aria-pressed="false">Monat</button>
                </div>
                <button type="button" id="adc-previous-period">Vorherige Woche</button>
                <output id="adc-week-label" aria-live="polite"></output>
                <label id="adc-week-picker">KW <input id="adc-week-number" type="week"></label>
                <label id="adc-month-picker" hidden>Monat <input id="adc-month-number" type="month"></label>
                <button type="button" id="adc-next-period">Nächste Woche</button>
                <button type="button" id="adc-toggle-view" aria-pressed="false">Tage als Zeilen</button>
            </nav>
        </div>
        <div id="adc-calendar-tables" class="adc-calendar-tables">
            <p>Daten werden geladen.</p>
        </div>
      </section>
    </section>
    <?php echo $this->inc('partials/settings'); ?>
    <?php echo $this->inc('partials/entry-dialog'); ?>
    <?php echo $this->inc('partials/meeting-dialog'); ?>
</div>
