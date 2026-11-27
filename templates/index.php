<?php
translation('flzcalendar');
\OCP\Util::addScript('flzcalendar', 'shared/api-client');
\OCP\Util::addScript('flzcalendar', 'shared/model');
\OCP\Util::addScript('flzcalendar', 'shared/repository');
\OCP\Util::addScript('flzcalendar', 'shared/ui');
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
            <h1><?php p($l->t('Filzmann Kalender')); ?></h1>
            <p><?php p($l->t('Dienste, Termine und Sperrtermine im Wochen- oder Monatsüberblick')); ?></p>
        </div>
    </header>
    <div id="flz-calendar-notice" role="status" aria-live="polite"></div>
    <nav class="flz-calendar-tabs" role="tablist" aria-label="<?php p($l->t('Filzmann-Kalender-Bereiche')); ?>">
        <button type="button" id="flz-calendar-tab-calendar" role="tab" aria-controls="flz-calendar-calendar-view" aria-selected="true"><?php p($l->t('Kalender')); ?></button>
        <button type="button" id="flz-calendar-tab-settings" role="tab" aria-controls="flz-calendar-settings-view" aria-selected="false"><?php p($l->t('Einstellungen')); ?></button>
    </nav>
    <section id="flz-calendar-calendar-view" role="tabpanel" aria-labelledby="flz-calendar-tab-calendar">
      <details id="flz-calendar-filters" class="flz-calendar-filters">
        <summary id="flz-calendar-filter-heading" aria-controls="flz-calendar-filter-panel"><?php p($l->t('Filter und Personenvergleich')); ?> <span id="flz-calendar-filter-status" class="flz-calendar-filter-status"><?php p($l->t('Alle Personen')); ?></span></summary>
        <div id="flz-calendar-filter-panel" class="flz-calendar-filter-grid" role="group" aria-labelledby="flz-calendar-filter-heading">
            <fieldset><legend><?php p($l->t('Rollen')); ?></legend><div id="flz-calendar-role-filters"></div></fieldset>
            <fieldset><legend><?php p($l->t('Bereiche')); ?></legend><div id="flz-calendar-area-filters"></div></fieldset>
            <div class="flz-calendar-person-filter">
                <label for="flz-calendar-person-search"><?php p($l->t('Person suchen')); ?></label>
                <input id="flz-calendar-person-search" type="search" autocomplete="off" aria-controls="flz-calendar-search-results">
                <ul id="flz-calendar-search-results" class="flz-calendar-search-results"></ul>
            </div>
            <div class="flz-calendar-selection-filter">
                <strong><?php p($l->t('Ausgewählte Personen')); ?></strong>
                <ul id="flz-calendar-selected-people" class="flz-calendar-selected-people"><li><?php p($l->t('Keine explizite Auswahl – Gruppenfilter gelten.')); ?></li></ul>
                <div class="flz-calendar-selection-actions">
                    <button type="button" id="flz-calendar-reset-selection" hidden><span class="flz-calendar-button-icon icon-close" aria-hidden="true"></span><span><?php p($l->t('Auswahl zurücksetzen')); ?></span></button>
                    <button type="button" id="flz-calendar-open-meeting-finder" disabled><span class="flz-calendar-button-icon icon-calendar-dark" aria-hidden="true"></span><span><?php p($l->t('Meetinglücke finden')); ?></span></button>
                </div>
            </div>
            <div class="flz-calendar-filter-actions">
                <button type="button" id="flz-calendar-save-default"><?php p($l->t('Zum Standard machen')); ?></button>
                <button type="button" id="flz-calendar-close-filters"><?php p($l->t('Schließen')); ?></button>
            </div>
        </div>
      </details>
      <section class="flz-calendar-overview" aria-labelledby="flz-calendar-overview-heading">
        <div class="flz-calendar-overview-header">
            <h2 id="flz-calendar-overview-heading"><?php p($l->t('Wochenplan')); ?></h2>
            <nav aria-label="<?php p($l->t('Kalendernavigation')); ?>" class="flz-calendar-navigation">
                <div class="flz-calendar-period-toggle" role="group" aria-label="<?php p($l->t('Ansichtszeitraum')); ?>">
                    <button type="button" id="flz-calendar-period-week" aria-pressed="true"><?php p($l->t('Woche')); ?></button>
                    <button type="button" id="flz-calendar-period-month" aria-pressed="false"><?php p($l->t('Monat')); ?></button>
                </div>
                <button type="button" id="flz-calendar-previous-period"><?php p($l->t('Vorherige Woche')); ?></button>
                <output id="flz-calendar-week-label" aria-live="polite"></output>
                <label id="flz-calendar-week-picker"><?php p($l->t('KW')); ?> <input id="flz-calendar-week-number" type="week"></label>
                <label id="flz-calendar-month-picker" hidden><?php p($l->t('Monat')); ?> <input id="flz-calendar-month-number" type="month"></label>
                <button type="button" id="flz-calendar-next-period"><?php p($l->t('Nächste Woche')); ?></button>
                <button type="button" id="flz-calendar-toggle-view" aria-pressed="false"><?php p($l->t('Tage als Zeilen')); ?></button>
            </nav>
        </div>
        <div id="flz-calendar-calendar-tables" class="flz-calendar-calendar-tables">
            <p><?php p($l->t('Daten werden geladen.')); ?></p>
        </div>
      </section>
    </section>
    <button type="button" id="flz-calendar-back-to-top" class="flz-calendar-back-to-top">
        <span aria-hidden="true">↑</span>
        <span><?php p($l->t('Nach oben')); ?></span>
    </button>
    <?php echo $this->inc('partials/settings'); ?>
    <?php echo $this->inc('partials/entry-dialog'); ?>
    <?php echo $this->inc('partials/meeting-dialog'); ?>
</div>
