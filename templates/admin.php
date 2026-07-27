<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adcalendar', 'admin');
\OCP\Util::addStyle('adcalendar', 'admin');
$calendarSyncStatus = $_['calendarSyncStatus'] ?? ['hasRun' => false, 'lastRunAt' => 0, 'lastRunLabel' => 'Noch kein Hintergrundlauf erfasst', 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending'];
$googleOAuth = $_['googleOAuth'] ?? ['configured' => false, 'clientId' => '', 'secretConfigured' => false, 'redirectUri' => ''];
?>
<section id="adcalendar-admin" class="section adc-admin" aria-labelledby="adc-admin-heading">
    <h2 id="adc-admin-heading">AD Kalender</h2>
    <section class="adc-admin-panel" aria-labelledby="adc-calendar-sync-heading">
        <h3 id="adc-calendar-sync-heading">Dienstkalender-Abgleich</h3>
        <p>Der Hintergrundlauf gleicht freigegebene Dienstplaneinträge einseitig aus AD Kalender in die persönlichen DAV-Kalender ab. Die technische Struktur hält eine spätere bidirektionale Erweiterung offen.</p>
        <?php if ($calendarSyncStatus['hasRun']): ?>
            <p class="adc-sync-state adc-sync-state--<?php p($calendarSyncStatus['state']); ?>">Letzter Lauf: <time datetime="<?php p(gmdate(DATE_ATOM, $calendarSyncStatus['lastRunAt'])); ?>"><?php p($calendarSyncStatus['lastRunLabel']); ?></time></p>
            <dl class="adc-sync-summary">
                <div><dt>Geprüft</dt><dd><?php p((string)$calendarSyncStatus['attempted']); ?></dd></div>
                <div><dt>Erfolgreich</dt><dd><?php p((string)$calendarSyncStatus['succeeded']); ?></dd></div>
                <div><dt>Fehlgeschlagen</dt><dd><?php p((string)$calendarSyncStatus['failed']); ?></dd></div>
            </dl>
        <?php else: ?>
            <p class="adc-sync-state"><?php p($calendarSyncStatus['lastRunLabel']); ?>.</p>
        <?php endif; ?>
        <p class="adc-sync-privacy">Keine Konten- oder Kalenderkennungen werden in diesem Status gespeichert oder angezeigt.</p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-kopano-caldav-heading">
        <h3 id="adc-kopano-caldav-heading">Kopano und CalDAV</h3>
        <p><strong>Voraussetzung:</strong> Der Kopano-Betreiber muss CalDAV per HTTPS bereitstellen und Methoden wie <code>PROPFIND</code> an den Kopano-CalDAV-Dienst weiterleiten. AD Kalender kann diesen Zugang nicht selbst auf dem fremden Server freischalten.</p>
        <p>HTTP 405 bedeutet: Der Betreiber erlaubt an dieser Adresse keine CalDAV-Verbindung. Ohne Zugangsdaten ist HTTP 401 eine erwartbare Anmeldeaufforderung; ein erfolgreicher authentifizierter <code>PROPFIND</code> antwortet üblicherweise mit HTTP 207.</p>
        <p>Die Vorgabe <code>https://mail.adberlin.org</code> bleibt im persönlichen Verbindungsdialog änderbar. Bei einer abweichenden veröffentlichten CalDAV-Adresse muss dort die vollständige HTTPS-Adresse eingetragen werden.</p>
        <form id="adc-kopano-test-form" class="adc-kopano-test-form">
            <label for="adc-kopano-test-url">HTTPS-CalDAV-Adresse</label>
            <input id="adc-kopano-test-url" type="url" value="https://mail.adberlin.org" inputmode="url" autocomplete="url" required>
            <label for="adc-kopano-test-username">Kopano-Benutzername</label>
            <input id="adc-kopano-test-username" type="text" autocomplete="username" maxlength="320" required>
            <label for="adc-kopano-test-password">Kopano-Passwort</label>
            <input id="adc-kopano-test-password" type="password" autocomplete="off" maxlength="4096" required>
            <small>Der Test führt ausschließlich eine lesende CalDAV-Anfrage aus. Zugangsdaten werden nicht gespeichert und kein Kalender wird angelegt.</small>
            <div><button id="adc-kopano-test-submit" type="submit" class="primary">Verbindung testen</button></div>
        </form>
        <p id="adc-kopano-test-status" class="adc-kopano-test-status" role="status" aria-live="polite">Noch nicht getestet.</p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-google-oauth-heading">
        <h3 id="adc-google-oauth-heading">Google Calendar OAuth</h3>
        <p>Diese systemweite Webclient-Konfiguration ermöglicht allen Nutzer*innen, ihr eigenes Google-Konto im persönlichen Einstellungs-Tab zu verbinden.</p>
        <details class="adc-google-registration-guide">
            <summary>Google-App registrieren – Schritt für Schritt</summary>
            <div class="adc-google-registration-guide__content">
                <p>Für die Registrierung wird ein Google-Cloud-Projekt benötigt. Die folgenden Schritte werden einmalig von einer Administration ausgeführt:</p>
                <ol>
                    <li>In der <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a> ein Projekt auswählen oder neu anlegen.</li>
                    <li>Unter <strong>APIs und Dienste → Bibliothek</strong> die <strong>Google Calendar API</strong> suchen und aktivieren.</li>
                    <li>Unter <strong>Google Auth Platform → Branding</strong> App-Name, Support-E-Mail und Kontaktadresse eintragen.</li>
                    <li>Unter <strong>Google Auth Platform → Zielgruppe</strong> <strong>Intern</strong> wählen, wenn ausschließlich Konten derselben Google-Workspace-Organisation zugreifen. Andernfalls <strong>Extern</strong> wählen. Im Testmodus müssen die vorgesehenen Konten als <strong>Testnutzer*innen</strong> eingetragen werden; Refresh-Tokens können dann bereits nach sieben Tagen ablaufen.</li>
                    <li>Unter <strong>Google Auth Platform → Datenzugriff</strong> exakt den Scope <code>https://www.googleapis.com/auth/calendar.app.created</code> hinzufügen. Er erlaubt AD Kalender nur den Zugriff auf von der App erstellte sekundäre Kalender und deren Termine.</li>
                    <li>Die unten angezeigte <strong>Autorisierte Weiterleitungs-URI</strong> über „URI kopieren“ übernehmen.</li>
                    <li>Unter <strong>Google Auth Platform → Clients</strong> einen OAuth-Client vom Typ <strong>Webanwendung</strong> erstellen und die kopierte URI exakt unter <strong>Autorisierte Weiterleitungs-URIs</strong> eintragen. Keine autorisierten JavaScript-Quellen hinzufügen.</li>
                    <li>Google-Client-ID und Google-Client-Secret hier eintragen und speichern. Das Secret wird anschließend nicht mehr angezeigt.</li>
                    <li>Danach können Nutzer*innen in <strong>AD Kalender → Einstellungen → Externe Kalender</strong> bei Google auf „Verbinden“ klicken.</li>
                </ol>
                <p><strong>Wichtig:</strong> Protokoll, Domain, Pfad und ein möglicher abschließender Schrägstrich der Weiterleitungs-URI müssen exakt übereinstimmen. Für eine externe produktive App kann Google zusätzlich eine Verifizierung verlangen.</p>
                <p class="adc-google-registration-guide__sources">Offizielle Dokumentation: <a href="https://developers.google.com/workspace/calendar/api/auth" target="_blank" rel="noopener noreferrer">Calendar-Berechtigungen</a> · <a href="https://developers.google.com/identity/protocols/oauth2/web-server" target="_blank" rel="noopener noreferrer">OAuth für Webserver-Anwendungen</a></p>
            </div>
        </details>
        <p id="adc-google-oauth-status" class="adc-google-oauth-status" role="status" aria-live="polite" data-configured="<?php p($googleOAuth['configured'] ? 'true' : 'false'); ?>">
            <?php p($googleOAuth['configured'] ? 'Google OAuth ist konfiguriert.' : 'Google OAuth ist noch nicht konfiguriert.'); ?>
        </p>
        <form id="adc-google-oauth-form" class="adc-google-oauth-form">
            <label for="adc-google-redirect-uri">Autorisierte Weiterleitungs-URI</label>
            <div class="adc-google-redirect-row">
                <input id="adc-google-redirect-uri" type="url" value="<?php p($googleOAuth['redirectUri']); ?>" readonly>
                <button id="adc-google-copy-redirect" type="button">URI kopieren</button>
            </div>
            <small>Diese URI muss im Google-Cloud-OAuth-Client exakt als autorisierte Weiterleitungs-URI hinterlegt werden.</small>

            <label for="adc-google-client-id">Google-Client-ID</label>
            <input id="adc-google-client-id" type="text" value="<?php p($googleOAuth['clientId']); ?>" maxlength="512" autocomplete="off" spellcheck="false" required>

            <label for="adc-google-client-secret">Google-Client-Secret</label>
            <input id="adc-google-client-secret" type="password" value="" maxlength="4096" autocomplete="new-password" <?php if (!$googleOAuth['secretConfigured']): ?>required<?php endif; ?>>
            <small><?php p($googleOAuth['secretConfigured'] ? 'Ein Secret ist sicher gespeichert. Leer lassen, um es unverändert zu behalten.' : 'Das Secret wird sensitiv und lazy in der Nextcloud-AppConfig gespeichert und nie wieder angezeigt.'); ?></small>

            <div class="adc-google-oauth-actions">
                <button type="submit" class="primary">Google-Konfiguration speichern</button>
                <button id="adc-google-oauth-remove" type="button" <?php if (!$googleOAuth['configured']): ?>disabled<?php endif; ?>>Konfiguration entfernen</button>
            </div>
        </form>
        <p class="adc-sync-privacy">Beim Entfernen werden Client-ID und Client-Secret aus Nextcloud gelöscht. Bereits bei Google erteilte Nutzerfreigaben werden dadurch nicht automatisch widerrufen.</p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-demo-heading">
        <h3 id="adc-demo-heading">Demo-Pack</h3>
        <p>Das Demo-Pack legt ausschließlich synthetische lokale Konten, bei Bedarf lokale Gruppen sowie neutrale Dienste und Termine an. Es wird nicht automatisch installiert und importiert keine WordPress-Bestandsdaten.</p>
        <p>Bereits vorhandene fremde Konten und schreibgeschützte LDAP-Gruppen führen vor der ersten Änderung zum Abbruch.</p>
        <p id="adc-demo-notice" class="adc-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="adc-demo-confirm"><input id="adc-demo-confirm" type="checkbox"> Ich bestätige die Installation synthetischer Demodaten.</label>
        <button id="adc-demo-install" type="button" class="primary" disabled>Kalender-Demo-Pack installieren</button>
    </section>
</section>
