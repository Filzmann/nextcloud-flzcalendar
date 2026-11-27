<?php
$calendarDefaults = $_['calendarDefaults'] ?? ['kopanoUrl' => '', 'calendarName' => 'Filzmann Dienste'];
?>
<section id="flz-calendar-settings-view" class="flz-calendar-settings-view" role="tabpanel" aria-labelledby="flz-calendar-tab-settings" hidden>
    <section aria-labelledby="flz-calendar-calendar-sync-heading">
        <h2 id="flz-calendar-calendar-sync-heading"><?php p($l->t('Meine Dienste im Nextcloud-Kalender')); ?></h2>
        <p><?php p($l->t('Der Abgleich ist standardmäßig aktiv. Sobald Dienste vorhanden sind, legt Filzmann Kalender den privaten Kalender „%s“ an und überträgt ausschließlich deine Dienste; Termine und Urlaube werden nicht synchronisiert. Du kannst den Abgleich hier deaktivieren. Filzmann Kalender bleibt die Quelle der Wahrheit: Änderungen im Zielkalender werden überschrieben und nicht zurückübertragen.', [$calendarDefaults['calendarName']])); ?></p>
        <form id="flz-calendar-calendar-sync-form" class="flz-calendar-calendar-sync-form">
            <label for="flz-calendar-calendar-sync-enabled"><input id="flz-calendar-calendar-sync-enabled" type="checkbox"> <?php p($l->t('Dienste in „%s“ anzeigen', [$calendarDefaults['calendarName']])); ?></label>
            <p id="flz-calendar-calendar-sync-status" role="status" aria-live="polite"><?php p($l->t('Kalenderstatus wird geladen.')); ?></p>
            <button type="submit" class="primary"><?php p($l->t('Kalendersynchronisation speichern')); ?></button>
        </form>
    </section>
    <section aria-labelledby="flz-calendar-external-calendars-heading">
        <h2 id="flz-calendar-external-calendars-heading"><?php p($l->t('Externe Kalender')); ?></h2>
        <p><?php p($l->t('Verbinde weitere persönliche Kalender. Filzmann Kalender legt beim Anbieter einen sichtbaren Kalender „%s“ an und überträgt ausschließlich deine Dienste. Anbieter-Kalender werden nicht in Filzmann Kalender eingeblendet; Änderungen beim Anbieter werden nicht zurückübertragen.', [$calendarDefaults['calendarName']])); ?></p>
        <div class="flz-calendar-provider-grid">
            <article class="flz-calendar-provider-card">
                <h3>Kopano</h3>
                <small><?php p($l->t('Vorgabe: %s (änderbar)', [$calendarDefaults['kopanoUrl']])); ?></small>
                <p class="flz-calendar-provider-requirement"><strong><?php p($l->t('Voraussetzung:')); ?></strong> <?php p($l->t('Der Kopano-Betreiber muss CalDAV per HTTPS erlauben. HTTP 405 bedeutet, dass der Betreiber den CalDAV-Zugriff an dieser Adresse nicht freigegeben hat.')); ?></p>
                <p id="flz-calendar-external-kopano-status" role="status"><?php p($l->t('Status wird geladen.')); ?></p>
                <div><button type="button" data-external-connect="kopano"><?php p($l->t('Kopano verbinden')); ?></button><button type="button" data-external-disconnect="kopano" hidden><?php p($l->t('Verbindung trennen')); ?></button></div>
            </article>
            <article class="flz-calendar-provider-card">
                <h3>Google</h3>
                <p id="flz-calendar-external-google-status" role="status"><?php p($l->t('Status wird geladen.')); ?></p>
                <div><button type="button" data-external-connect="google"><?php p($l->t('Mit Google verbinden')); ?></button><button type="button" data-external-disconnect="google" hidden><?php p($l->t('Verbindung trennen')); ?></button></div>
            </article>
            <article class="flz-calendar-provider-card">
                <h3>Apple</h3>
                <p id="flz-calendar-external-apple-status" role="status"><?php p($l->t('Status wird geladen.')); ?></p>
                <div><button type="button" data-external-connect="apple"><?php p($l->t('Apple verbinden')); ?></button><button type="button" data-external-disconnect="apple" hidden><?php p($l->t('Verbindung trennen')); ?></button></div>
            </article>
            <article class="flz-calendar-provider-card">
                <h3><?php p($l->t('Manuelles CalDAV')); ?></h3>
                <p id="flz-calendar-external-manual-status" role="status"><?php p($l->t('Status wird geladen.')); ?></p>
                <div><button type="button" data-external-connect="manual"><?php p($l->t('Manuell verbinden')); ?></button><button type="button" data-external-disconnect="manual" hidden><?php p($l->t('Verbindung trennen')); ?></button></div>
            </article>
        </div>
    </section>
    <section aria-labelledby="flz-calendar-shift-defaults-heading">
        <h2 id="flz-calendar-shift-defaults-heading"><?php p($l->t('Meine Standard-Dienstzeiten')); ?></h2>
        <p><?php p($l->t('Diese Zeiten erscheinen als feste Dienste im Kalender und werden beim Anlegen vorgeschlagen. Individuell bearbeitete oder gelöschte Tage bleiben Einzelabweichungen. Liegt das Ende vor dem Beginn, endet der Dienst am Folgetag.')); ?></p>
        <form id="flz-calendar-shift-defaults-form"><div id="flz-calendar-shift-defaults"></div><button type="submit" class="primary"><?php p($l->t('Dienstzeiten speichern')); ?></button></form>
    </section>
</section>
<dialog id="flz-calendar-external-calendar-dialog" class="flz-calendar-dialog flz-calendar-external-dialog" aria-labelledby="flz-calendar-external-dialog-heading" data-kopano-default="<?php p($calendarDefaults['kopanoUrl']); ?>" data-calendar-name="<?php p($calendarDefaults['calendarName']); ?>">
    <form id="flz-calendar-external-calendar-form">
        <div class="flz-calendar-dialog__header"><h2 id="flz-calendar-external-dialog-heading"><?php p($l->t('Kalender verbinden')); ?></h2><button id="flz-calendar-external-dialog-close" type="button" aria-label="<?php p($l->t('Dialog schließen')); ?>">×</button></div>
        <p id="flz-calendar-external-instruction"></p>
        <input id="flz-calendar-external-provider" type="hidden">
        <div class="flz-calendar-external-fields">
            <label><?php p($l->t('HTTPS-CalDAV-Adresse')); ?> <input id="flz-calendar-external-server-url" type="url" inputmode="url" autocomplete="url" required></label>
            <label><span id="flz-calendar-external-username-label"><?php p($l->t('Benutzername')); ?></span><input id="flz-calendar-external-username" type="text" autocomplete="username" required></label>
            <label><span id="flz-calendar-external-password-label"><?php p($l->t('Passwort')); ?></span><input id="flz-calendar-external-password" type="password" autocomplete="current-password" required></label>
        </div>
        <p class="flz-calendar-dialog__hint"><?php p($l->t('Zugangsdaten werden verschlüsselt als persönliche Nextcloud-Einstellung gespeichert.')); ?></p>
        <div class="flz-calendar-dialog__actions"><button id="flz-calendar-external-dialog-cancel" type="button"><?php p($l->t('Abbrechen')); ?></button><button type="submit" class="primary"><?php p($l->t('Verbindung prüfen und speichern')); ?></button></div>
    </form>
</dialog>
