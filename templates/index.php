<?php
translation('flzcalendar');
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('flzcalendar', 'admin-access');
\OCP\Util::addScript('localbase', 'models/model');
\OCP\Util::addScript('localbase', 'repositories/repository');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('flzcalendar', 'models/calendar-entry');
\OCP\Util::addScript('flzcalendar', 'models/organization');
\OCP\Util::addScript('flzcalendar', 'repositories/calendar-repository');
\OCP\Util::addScript('flzcalendar', 'modules/localization');
\OCP\Util::addScript('flzcalendar', 'modules/calendar-date');
\OCP\Util::addScript('flzcalendar', 'modules/holiday-calendar');
\OCP\Util::addScript('flzcalendar', 'modules/calendar-state');
\OCP\Util::addScript('flzcalendar', 'modules/calendar-timeline');
\OCP\Util::addScript('flzcalendar', 'modules/entry-workflow');
\OCP\Util::addScript('flzcalendar', 'modules/meeting-capabilities');
\OCP\Util::addScript('flzcalendar', 'components/calendar-filters');
\OCP\Util::addScript('flzcalendar', 'components/calendar-cell');
\OCP\Util::addScript('flzcalendar', 'components/entry-dialog');
\OCP\Util::addScript('flzcalendar', 'components/meeting-finder');
\OCP\Util::addScript('flzcalendar', 'components/shift-defaults');
\OCP\Util::addScript('flzcalendar', 'components/shift-calendar-sync');
\OCP\Util::addScript('flzcalendar', 'components/external-calendars');
\OCP\Util::addScript('flzcalendar', 'components/tab-navigation');
\OCP\Util::addScript('flzcalendar', 'components/week-navigation');
\OCP\Util::addScript('flzcalendar', 'components/week-table');
\OCP\Util::addScript('flzcalendar', 'main');
\OCP\Util::addStyle('flzcalendar', 'style');
?>
<div id="flzcalendar-app" class="flz-calendar-app">
    <div class="orgsuite-host" data-orgsuite data-suite="flz" data-current-app="flzcalendar"></div>
    <header class="flz-calendar-header">
        <div class="flz-calendar-title-row">
            <h1><?php p($l->t('Filzmann Calendar')); ?></h1>
            <?php if ($_['showMissingAdminGrant'] ?? false): ?>
                <details class="flz-calendar-admin-access-warning">
                    <summary aria-label="<?php p($l->t('Informationen zum fehlenden fachlichen Admin-Vollzugriff')); ?>"><span aria-hidden="true">⚠</span></summary>
                    <div class="flz-calendar-admin-access-warning__panel">
                        <strong><?php p($l->t('Kein fachlicher Admin-Vollzugriff aktiv.')); ?></strong>
                        <p><?php p($l->t('Native Nextcloud-Administration erteilt keinen fachlichen Vollzugriff. Mitglieder der Gruppe Datenschutzbeauftragte können eine app-lokale Freigabe von höchstens 24 Stunden erteilen.')); ?></p>
                        <?php if ($_['showAdminAccessLink'] ?? false): ?><a href="#flz-calendar-full-access" target="_blank" rel="noopener noreferrer"><?php p($l->t('Freigabesteuerung in neuem Tab öffnen')); ?></a><?php endif; ?>
                    </div>
                </details>
            <?php endif; ?>
            <p><?php p($l->t('Shifts, appointments and blocked times in a weekly or monthly overview')); ?></p>
        </div>
    </header>
    <?php if ($_['canManageAdminAccess'] ?? false): ?>
        <section id="flz-calendar-full-access" class="flz-calendar-admin-access" aria-labelledby="flz-calendar-full-access-heading">
            <h2 id="flz-calendar-full-access-heading"><?php p($l->t('Zeitlich begrenzter Admin-Vollzugriff')); ?></h2>
            <p><?php p($l->t('Nur Mitglieder der Nextcloud-Gruppe Datenschutzbeauftragte dürfen Freigaben für aktive native Administrationskonten verwalten. Maximal 24 Stunden sind zulässig.')); ?></p>
            <form id="flz-calendar-full-access-form">
                <label><?php p($l->t('Admin-Benutzerkennung')); ?> <input name="targetUid" required maxlength="64" autocomplete="off"></label>
                <label><?php p($l->t('Dauer')); ?> <select name="durationMinutes" required><option value="60"><?php p($l->t('1 Stunde')); ?></option><option value="240"><?php p($l->t('4 Stunden')); ?></option><option value="480"><?php p($l->t('8 Stunden')); ?></option><option value="1440"><?php p($l->t('24 Stunden')); ?></option></select></label>
                <label><input id="flz-calendar-full-access-enabled" name="enabled" type="checkbox" required> <?php p($l->t('Vollzugriff für diesen Zeitraum aktivieren')); ?></label>
                <button type="submit" class="primary"><?php p($l->t('Freigabe aktivieren')); ?></button>
            </form>
            <p id="flz-calendar-full-access-status" role="status" aria-live="polite"></p>
            <div class="flz-calendar-table-wrap">
                <table><caption><?php p($l->t('Protokollierte Admin-Vollzugriffszeiträume')); ?></caption><thead><tr><th><?php p($l->t('Ziel-Admin')); ?></th><th><?php p($l->t('Freigegeben von')); ?></th><th><?php p($l->t('Von')); ?></th><th><?php p($l->t('Geplant bis')); ?></th><th><?php p($l->t('Tatsächlich bis / Status')); ?></th><th><?php p($l->t('Aktion')); ?></th></tr></thead><tbody id="flz-calendar-full-access-history"><tr><td colspan="6"><?php p($l->t('Freigaben werden geladen.')); ?></td></tr></tbody></table>
            </div>
        </section>
    <?php endif; ?>
    <div id="flz-calendar-notice" role="status" aria-live="polite"></div>
    <nav class="flz-calendar-tabs" role="tablist" aria-label="<?php p($l->t('Filzmann Calendar sections')); ?>">
        <button type="button" id="flz-calendar-tab-calendar" role="tab" aria-controls="flz-calendar-calendar-view" aria-selected="true"><?php p($l->t('Calendar')); ?></button>
        <button type="button" id="flz-calendar-tab-settings" role="tab" aria-controls="flz-calendar-settings-view" aria-selected="false"><?php p($l->t('Settings')); ?></button>
    </nav>
    <section id="flz-calendar-calendar-view" role="tabpanel" aria-labelledby="flz-calendar-tab-calendar">
      <details class="flz-calendar-filters">
        <summary id="flz-calendar-filter-heading"><?php p($l->t('Filter and compare people')); ?> <span id="flz-calendar-filter-status" class="flz-calendar-filter-status"><?php p($l->t('All people')); ?></span></summary>
        <div class="flz-calendar-filter-grid" aria-labelledby="flz-calendar-filter-heading">
            <fieldset><legend><?php p($l->t('Roles')); ?></legend><div id="flz-calendar-role-filters"></div></fieldset>
            <fieldset><legend><?php p($l->t('Areas')); ?></legend><div id="flz-calendar-area-filters"></div></fieldset>
            <div class="flz-calendar-person-filter">
                <label for="flz-calendar-person-search"><?php p($l->t('Search for a person')); ?></label>
                <input id="flz-calendar-person-search" type="search" autocomplete="off" aria-controls="flz-calendar-search-results">
                <ul id="flz-calendar-search-results" class="flz-calendar-search-results"></ul>
            </div>
            <div class="flz-calendar-selection-filter">
                <strong><?php p($l->t('Selected people')); ?></strong>
                <ul id="flz-calendar-selected-people" class="flz-calendar-selected-people"><li><?php p($l->t('No explicit selection – group filters apply.')); ?></li></ul>
                <div class="flz-calendar-selection-actions">
                    <button type="button" id="flz-calendar-reset-selection" hidden><span class="flz-calendar-button-icon icon-close" aria-hidden="true"></span><span><?php p($l->t('Reset selection')); ?></span></button>
                    <button type="button" id="flz-calendar-open-meeting-finder" disabled><span class="flz-calendar-button-icon icon-calendar-dark" aria-hidden="true"></span><span><?php p($l->t('Find a meeting gap')); ?></span></button>
                </div>
            </div>
            <div class="flz-calendar-filter-actions">
                <button type="button" id="flz-calendar-save-default"><?php p($l->t('Make default')); ?></button>
            </div>
        </div>
      </details>
      <section class="flz-calendar-overview" aria-labelledby="flz-calendar-overview-heading">
        <div class="flz-calendar-overview-header">
            <h2 id="flz-calendar-overview-heading"><?php p($l->t('Weekly schedule')); ?></h2>
            <nav aria-label="<?php p($l->t('Calendar navigation')); ?>" class="flz-calendar-navigation">
                <div class="flz-calendar-period-toggle" role="group" aria-label="<?php p($l->t('View period')); ?>">
                    <button type="button" id="flz-calendar-period-week" aria-pressed="true"><?php p($l->t('Week')); ?></button>
                    <button type="button" id="flz-calendar-period-month" aria-pressed="false"><?php p($l->t('Month')); ?></button>
                </div>
                <button type="button" id="flz-calendar-previous-period"><?php p($l->t('Previous week')); ?></button>
                <output id="flz-calendar-week-label" aria-live="polite"></output>
                <label id="flz-calendar-week-picker"><?php p($l->t('CW')); ?> <input id="flz-calendar-week-number" type="week"></label>
                <label id="flz-calendar-month-picker" hidden><?php p($l->t('Month')); ?> <input id="flz-calendar-month-number" type="month"></label>
                <button type="button" id="flz-calendar-next-period"><?php p($l->t('Next week')); ?></button>
                <button type="button" id="flz-calendar-toggle-view" aria-pressed="false"><?php p($l->t('Days as rows')); ?></button>
            </nav>
        </div>
        <div id="flz-calendar-calendar-tables" class="flz-calendar-calendar-tables">
            <p><?php p($l->t('Data is being loaded.')); ?></p>
        </div>
      </section>
    </section>
    <button type="button" id="flz-calendar-back-to-top" class="flz-calendar-back-to-top">
        <span aria-hidden="true">↑</span>
        <span><?php p($l->t('Back to top')); ?></span>
    </button>
    <?php echo $this->inc('partials/settings'); ?>
    <?php echo $this->inc('partials/entry-dialog'); ?>
    <?php echo $this->inc('partials/meeting-dialog'); ?>
</div>
