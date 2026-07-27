<section id="adc-settings-view" class="adc-settings-view" role="tabpanel" aria-labelledby="adc-tab-settings" hidden>
    <section aria-labelledby="adc-calendar-sync-heading">
        <h2 id="adc-calendar-sync-heading">Meine Dienste im Nextcloud-Kalender</h2>
        <p>Der Abgleich ist standardmäßig aktiv. Sobald Dienste vorhanden sind, legt AD Kalender den privaten Kalender „AD Dienste“ an und überträgt ausschließlich deine Dienste; Termine und Urlaube werden nicht synchronisiert. Du kannst den Abgleich hier deaktivieren. AD Kalender bleibt die Quelle der Wahrheit: Änderungen im Zielkalender werden überschrieben und nicht zurückübertragen.</p>
        <form id="adc-calendar-sync-form" class="adc-calendar-sync-form">
            <label for="adc-calendar-sync-enabled"><input id="adc-calendar-sync-enabled" type="checkbox"> Dienste in „AD Dienste“ anzeigen</label>
            <p id="adc-calendar-sync-status" role="status" aria-live="polite">Kalenderstatus wird geladen.</p>
            <button type="submit" class="primary">Kalendersynchronisation speichern</button>
        </form>
    </section>
    <section aria-labelledby="adc-external-calendars-heading">
        <h2 id="adc-external-calendars-heading">Externe Kalender</h2>
        <p>Verbinde weitere persönliche Kalender. AD Calendar legt beim Anbieter einen sichtbaren Kalender „AD Dienste“ an und überträgt ausschließlich deine Dienste. Anbieter-Kalender werden nicht in AD Calendar eingeblendet; Änderungen beim Anbieter werden nicht zurückübertragen.</p>
        <div class="adc-provider-grid">
            <article class="adc-provider-card">
                <h3>Kopano</h3>
                <small>Vorgabe: https://mail.adberlin.org (änderbar)</small>
                <p class="adc-provider-requirement"><strong>Voraussetzung:</strong> Der Kopano-Betreiber muss CalDAV per HTTPS erlauben. HTTP 405 bedeutet, dass der Betreiber den CalDAV-Zugriff an dieser Adresse nicht freigegeben hat.</p>
                <p id="adc-external-kopano-status" role="status">Status wird geladen.</p>
                <div><button type="button" data-external-connect="kopano">Kopano verbinden</button><button type="button" data-external-disconnect="kopano" hidden>Verbindung trennen</button></div>
            </article>
            <article class="adc-provider-card">
                <h3>Google</h3>
                <p id="adc-external-google-status" role="status">Status wird geladen.</p>
                <div><button type="button" data-external-connect="google">Mit Google verbinden</button><button type="button" data-external-disconnect="google" hidden>Verbindung trennen</button></div>
            </article>
            <article class="adc-provider-card">
                <h3>Apple</h3>
                <p id="adc-external-apple-status" role="status">Status wird geladen.</p>
                <div><button type="button" data-external-connect="apple">Apple verbinden</button><button type="button" data-external-disconnect="apple" hidden>Verbindung trennen</button></div>
            </article>
            <article class="adc-provider-card">
                <h3>Manuelles CalDAV</h3>
                <p id="adc-external-manual-status" role="status">Status wird geladen.</p>
                <div><button type="button" data-external-connect="manual">Manuell verbinden</button><button type="button" data-external-disconnect="manual" hidden>Verbindung trennen</button></div>
            </article>
        </div>
    </section>
    <section aria-labelledby="adc-shift-defaults-heading">
        <h2 id="adc-shift-defaults-heading">Meine Standard-Dienstzeiten</h2>
        <p>Diese Zeiten erscheinen als feste Dienste im Kalender und werden beim Anlegen vorgeschlagen. Individuell bearbeitete oder gelöschte Tage bleiben Einzelabweichungen. Liegt das Ende vor dem Beginn, endet der Dienst am Folgetag.</p>
        <form id="adc-shift-defaults-form"><div id="adc-shift-defaults"></div><button type="submit" class="primary">Dienstzeiten speichern</button></form>
    </section>
</section>
<dialog id="adc-external-calendar-dialog" class="adc-dialog adc-external-dialog" aria-labelledby="adc-external-dialog-heading">
    <form id="adc-external-calendar-form">
        <div class="adc-dialog__header"><h2 id="adc-external-dialog-heading">Kalender verbinden</h2><button id="adc-external-dialog-close" type="button" aria-label="Dialog schließen">×</button></div>
        <p id="adc-external-instruction"></p>
        <input id="adc-external-provider" type="hidden">
        <div class="adc-external-fields">
            <label>HTTPS-CalDAV-Adresse <input id="adc-external-server-url" type="url" inputmode="url" autocomplete="url" required></label>
            <label><span id="adc-external-username-label">Benutzername</span><input id="adc-external-username" type="text" autocomplete="username" required></label>
            <label><span id="adc-external-password-label">Passwort</span><input id="adc-external-password" type="password" autocomplete="current-password" required></label>
        </div>
        <p class="adc-dialog__hint">Zugangsdaten werden verschlüsselt als persönliche Nextcloud-Einstellung gespeichert.</p>
        <div class="adc-dialog__actions"><button id="adc-external-dialog-cancel" type="button">Abbrechen</button><button type="submit" class="primary">Verbindung prüfen und speichern</button></div>
    </form>
</dialog>
