<?php
translation('adcalendar');
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adcalendar', 'admin-access');
\OCP\Util::addScript('localbase', 'models/model');
\OCP\Util::addScript('localbase', 'repositories/repository');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('adcalendar', 'models/calendar-entry');
\OCP\Util::addScript('adcalendar', 'models/organization');
\OCP\Util::addScript('adcalendar', 'repositories/calendar-repository');
\OCP\Util::addScript('adcalendar', 'modules/localization');
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
            <h1><?php p($l->t('AD Calendar')); ?></h1>
            <p><?php p($l->t('Shifts, appointments and blocked times in a weekly or monthly overview')); ?></p>
        </div>
    </header>
    <?php if ($_['showMissingAdminGrant'] ?? false): ?>
        <section class="adc-admin-access adc-admin-access--warning" aria-labelledby="adc-missing-admin-grant-heading">
            <h2 id="adc-missing-admin-grant-heading"><?php p($l->t('Kein fachlicher Admin-Vollzugriff')); ?></h2>
            <p><?php p($l->t('Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Für geschützte Kalenderverwaltung und fachliche Demodaten fehlt eine aktive app-lokale Freigabe.')); ?></p>
            <?php if ($_['showAdminAccessLink'] ?? false): ?>
                <p><a href="#adc-full-access"><?php p($l->t('Freigabesteuerung öffnen')); ?></a></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($_['canManageAdminAccess'] ?? false): ?>
        <section id="adc-full-access" class="adc-admin-access" aria-labelledby="adc-full-access-heading">
            <h2 id="adc-full-access-heading"><?php p($l->t('Zeitlich begrenzter Admin-Vollzugriff')); ?></h2>
            <p><?php p($l->t('Nur Mitglieder der Nextcloud-Gruppe Datenschutzbeauftragte dürfen Freigaben für aktive native Administrationskonten verwalten. Maximal 24 Stunden sind zulässig.')); ?></p>
            <form id="adc-full-access-form">
                <label><?php p($l->t('Admin-Benutzerkennung')); ?> <input name="targetUid" required maxlength="64" autocomplete="off"></label>
                <label><?php p($l->t('Dauer')); ?> <select name="durationMinutes" required><option value="60"><?php p($l->t('1 Stunde')); ?></option><option value="240"><?php p($l->t('4 Stunden')); ?></option><option value="480"><?php p($l->t('8 Stunden')); ?></option><option value="1440"><?php p($l->t('24 Stunden')); ?></option></select></label>
                <label><input id="adc-full-access-enabled" name="enabled" type="checkbox" required> <?php p($l->t('Vollzugriff für diesen Zeitraum aktivieren')); ?></label>
                <button type="submit" class="primary"><?php p($l->t('Freigabe aktivieren')); ?></button>
            </form>
            <p id="adc-full-access-status" role="status" aria-live="polite"></p>
            <div class="adc-table-wrap">
                <table><caption><?php p($l->t('Protokollierte Admin-Vollzugriffszeiträume')); ?></caption><thead><tr><th><?php p($l->t('Ziel-Admin')); ?></th><th><?php p($l->t('Freigegeben von')); ?></th><th><?php p($l->t('Von')); ?></th><th><?php p($l->t('Geplant bis')); ?></th><th><?php p($l->t('Tatsächlich bis / Status')); ?></th><th><?php p($l->t('Aktion')); ?></th></tr></thead><tbody id="adc-full-access-history"><tr><td colspan="6"><?php p($l->t('Freigaben werden geladen.')); ?></td></tr></tbody></table>
            </div>
        </section>
    <?php endif; ?>
    <div id="adc-notice" role="status" aria-live="polite"></div>
    <nav class="adc-tabs" role="tablist" aria-label="<?php p($l->t('AD Calendar sections')); ?>">
        <button type="button" id="adc-tab-calendar" role="tab" aria-controls="adc-calendar-view" aria-selected="true"><?php p($l->t('Calendar')); ?></button>
        <button type="button" id="adc-tab-settings" role="tab" aria-controls="adc-settings-view" aria-selected="false"><?php p($l->t('Settings')); ?></button>
    </nav>
    <section id="adc-calendar-view" role="tabpanel" aria-labelledby="adc-tab-calendar">
      <details class="adc-filters">
        <summary id="adc-filter-heading"><?php p($l->t('Filter and compare people')); ?> <span id="adc-filter-status" class="adc-filter-status"><?php p($l->t('All people')); ?></span></summary>
        <div class="adc-filter-grid" aria-labelledby="adc-filter-heading">
            <fieldset><legend><?php p($l->t('Roles')); ?></legend><div id="adc-role-filters"></div></fieldset>
            <fieldset><legend><?php p($l->t('Areas')); ?></legend><div id="adc-area-filters"></div></fieldset>
            <div class="adc-person-filter">
                <label for="adc-person-search"><?php p($l->t('Search for a person')); ?></label>
                <input id="adc-person-search" type="search" autocomplete="off" aria-controls="adc-search-results">
                <ul id="adc-search-results" class="adc-search-results"></ul>
            </div>
            <div class="adc-selection-filter">
                <strong><?php p($l->t('Selected people')); ?></strong>
                <ul id="adc-selected-people" class="adc-selected-people"><li><?php p($l->t('No explicit selection – group filters apply.')); ?></li></ul>
                <div class="adc-selection-actions">
                    <button type="button" id="adc-reset-selection" hidden><span class="adc-button-icon icon-close" aria-hidden="true"></span><span><?php p($l->t('Reset selection')); ?></span></button>
                    <button type="button" id="adc-open-meeting-finder" disabled><span class="adc-button-icon icon-calendar-dark" aria-hidden="true"></span><span><?php p($l->t('Find a meeting gap')); ?></span></button>
                </div>
            </div>
            <div class="adc-filter-actions">
                <button type="button" id="adc-save-default"><?php p($l->t('Make default')); ?></button>
            </div>
        </div>
      </details>
      <section class="adc-overview" aria-labelledby="adc-overview-heading">
        <div class="adc-overview-header">
            <h2 id="adc-overview-heading"><?php p($l->t('Weekly schedule')); ?></h2>
            <nav aria-label="<?php p($l->t('Calendar navigation')); ?>" class="adc-navigation">
                <div class="adc-period-toggle" role="group" aria-label="<?php p($l->t('View period')); ?>">
                    <button type="button" id="adc-period-week" aria-pressed="true"><?php p($l->t('Week')); ?></button>
                    <button type="button" id="adc-period-month" aria-pressed="false"><?php p($l->t('Month')); ?></button>
                </div>
                <button type="button" id="adc-previous-period"><?php p($l->t('Previous week')); ?></button>
                <output id="adc-week-label" aria-live="polite"></output>
                <label id="adc-week-picker"><?php p($l->t('CW')); ?> <input id="adc-week-number" type="week"></label>
                <label id="adc-month-picker" hidden><?php p($l->t('Month')); ?> <input id="adc-month-number" type="month"></label>
                <button type="button" id="adc-next-period"><?php p($l->t('Next week')); ?></button>
                <button type="button" id="adc-toggle-view" aria-pressed="false"><?php p($l->t('Days as rows')); ?></button>
            </nav>
        </div>
        <div id="adc-calendar-tables" class="adc-calendar-tables">
            <p><?php p($l->t('Data is being loaded.')); ?></p>
        </div>
      </section>
    </section>
    <button type="button" id="adc-back-to-top" class="adc-back-to-top">
        <span aria-hidden="true">↑</span>
        <span><?php p($l->t('Back to top')); ?></span>
    </button>
    <?php echo $this->inc('partials/settings'); ?>
    <?php echo $this->inc('partials/entry-dialog'); ?>
    <?php echo $this->inc('partials/meeting-dialog'); ?>
</div>
