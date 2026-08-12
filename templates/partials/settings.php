<?php
$calendarDefaults = $_['calendarDefaults'] ?? ['kopanoUrl' => 'https://mail.adberlin.org/', 'calendarName' => 'AD Dienste'];
?>
<section id="adc-settings-view" class="adc-settings-view" role="tabpanel" aria-labelledby="adc-tab-settings" hidden>
    <section aria-labelledby="adc-calendar-sync-heading">
        <h2 id="adc-calendar-sync-heading"><?php p($l->t('My shifts in Nextcloud Calendar')); ?></h2>
        <p><?php p($l->t('Synchronisation is enabled by default. As soon as shifts exist, AD Calendar creates the private calendar “%s” and transfers only your shifts; appointments and absences are not synchronised. You can disable synchronisation here. AD Calendar remains the source of truth: changes in the target calendar are overwritten and are not imported.', [$calendarDefaults['calendarName']])); ?></p>
        <form id="adc-calendar-sync-form" class="adc-calendar-sync-form">
            <label for="adc-calendar-sync-enabled"><input id="adc-calendar-sync-enabled" type="checkbox"> <?php p($l->t('Show shifts in “%s”', [$calendarDefaults['calendarName']])); ?></label>
            <p id="adc-calendar-sync-status" role="status" aria-live="polite"><?php p($l->t('Calendar status is being loaded.')); ?></p>
            <button type="submit" class="primary"><?php p($l->t('Save calendar synchronisation')); ?></button>
        </form>
    </section>
    <section aria-labelledby="adc-external-calendars-heading">
        <h2 id="adc-external-calendars-heading"><?php p($l->t('External calendars')); ?></h2>
        <p><?php p($l->t('Connect additional personal calendars. AD Calendar creates the visible calendar “%s” with the provider and transfers only your shifts. Provider calendars are not displayed in AD Calendar; changes made by the provider are not imported.', [$calendarDefaults['calendarName']])); ?></p>
        <div class="adc-provider-grid">
            <article class="adc-provider-card">
                <h3>Kopano</h3>
                <small><?php p($l->t('Default: %s (editable)', [$calendarDefaults['kopanoUrl']])); ?></small>
                <p class="adc-provider-requirement"><strong><?php p($l->t('Requirement:')); ?></strong> <?php p($l->t('The Kopano provider must allow CalDAV over HTTPS. HTTP 405 means that the provider has not enabled CalDAV access at this address.')); ?></p>
                <p id="adc-external-kopano-status" role="status"><?php p($l->t('Status is being loaded.')); ?></p>
                <div><button type="button" data-external-connect="kopano"><?php p($l->t('Connect Kopano')); ?></button><button type="button" data-external-disconnect="kopano" hidden><?php p($l->t('Disconnect')); ?></button></div>
            </article>
            <article class="adc-provider-card">
                <h3>Google</h3>
                <p id="adc-external-google-status" role="status"><?php p($l->t('Status is being loaded.')); ?></p>
                <div><button type="button" data-external-connect="google"><?php p($l->t('Connect with Google')); ?></button><button type="button" data-external-disconnect="google" hidden><?php p($l->t('Disconnect')); ?></button></div>
            </article>
            <article class="adc-provider-card">
                <h3>Apple</h3>
                <p id="adc-external-apple-status" role="status"><?php p($l->t('Status is being loaded.')); ?></p>
                <div><button type="button" data-external-connect="apple"><?php p($l->t('Connect Apple')); ?></button><button type="button" data-external-disconnect="apple" hidden><?php p($l->t('Disconnect')); ?></button></div>
            </article>
            <article class="adc-provider-card">
                <h3><?php p($l->t('Manual CalDAV')); ?></h3>
                <p id="adc-external-manual-status" role="status"><?php p($l->t('Status is being loaded.')); ?></p>
                <div><button type="button" data-external-connect="manual"><?php p($l->t('Connect manually')); ?></button><button type="button" data-external-disconnect="manual" hidden><?php p($l->t('Disconnect')); ?></button></div>
            </article>
        </div>
    </section>
    <section aria-labelledby="adc-shift-defaults-heading">
        <h2 id="adc-shift-defaults-heading"><?php p($l->t('My default shift times')); ?></h2>
        <p><?php p($l->t('These times appear as fixed shifts in the calendar and are suggested when creating an entry. Individually edited or deleted days remain single exceptions. If the end is before the start, the shift ends on the following day.')); ?></p>
        <form id="adc-shift-defaults-form"><div id="adc-shift-defaults"></div><button type="submit" class="primary"><?php p($l->t('Save shift times')); ?></button></form>
    </section>
</section>
<dialog id="adc-external-calendar-dialog" class="adc-dialog adc-external-dialog" aria-labelledby="adc-external-dialog-heading" data-kopano-default="<?php p($calendarDefaults['kopanoUrl']); ?>" data-calendar-name="<?php p($calendarDefaults['calendarName']); ?>">
    <form id="adc-external-calendar-form">
        <div class="adc-dialog__header"><h2 id="adc-external-dialog-heading"><?php p($l->t('Connect calendar')); ?></h2><button id="adc-external-dialog-close" type="button" aria-label="<?php p($l->t('Close dialog')); ?>">×</button></div>
        <p id="adc-external-instruction"></p>
        <input id="adc-external-provider" type="hidden">
        <div class="adc-external-fields">
            <label><?php p($l->t('HTTPS CalDAV address')); ?> <input id="adc-external-server-url" type="url" inputmode="url" autocomplete="url" required></label>
            <label><span id="adc-external-username-label"><?php p($l->t('Username')); ?></span><input id="adc-external-username" type="text" autocomplete="username" required></label>
            <label><span id="adc-external-password-label"><?php p($l->t('Password')); ?></span><input id="adc-external-password" type="password" autocomplete="current-password" required></label>
        </div>
        <p class="adc-dialog__hint"><?php p($l->t('Credentials are stored encrypted as a personal Nextcloud setting.')); ?></p>
        <div class="adc-dialog__actions"><button id="adc-external-dialog-cancel" type="button"><?php p($l->t('Cancel')); ?></button><button type="submit" class="primary"><?php p($l->t('Test and save connection')); ?></button></div>
    </form>
</dialog>
