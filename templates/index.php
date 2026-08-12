<?php
translation('adcalendar');
\OCP\Util::addScript('localbase', 'api/api-client');
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
