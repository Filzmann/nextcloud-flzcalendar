<?php
translation('flzcalendar');
\OCP\Util::addScript('flzcalendar', 'shared/api-client');
\OCP\Util::addScript('flzcalendar', 'modules/localization');
\OCP\Util::addScript('flzcalendar', 'admin');
\OCP\Util::addStyle('flzcalendar', 'admin');
$calendarSyncStatus = $_['calendarSyncStatus'] ?? ['hasRun' => false, 'lastRunAt' => 0, 'lastRunLabel' => $l->t('Noch kein Hintergrundlauf erfasst'), 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending'];
$googleOAuth = $_['googleOAuth'] ?? ['configured' => false, 'clientId' => '', 'secretConfigured' => false, 'redirectUri' => ''];
$calendarDefaults = $_['calendarDefaults'] ?? ['kopanoUrl' => '', 'calendarName' => 'Filzmann Dienste'];
?>
<section id="flzcalendar-admin" class="section flz-calendar-admin" aria-labelledby="flz-calendar-admin-heading">
    <h2 id="flz-calendar-admin-heading"><?php p($l->t('Filzmann Kalender')); ?></h2>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-calendar-sync-heading">
        <h3 id="flz-calendar-calendar-sync-heading"><?php p($l->t('Dienstkalender-Abgleich')); ?></h3>
        <p><?php p($l->t('Der Hintergrundlauf gleicht freigegebene Dienstplaneinträge einseitig aus Filzmann Kalender in die persönlichen DAV-Kalender ab. Die technische Struktur hält eine spätere bidirektionale Erweiterung offen.')); ?></p>
        <?php if ($calendarSyncStatus['hasRun']): ?>
            <p class="flz-calendar-sync-state flz-calendar-sync-state--<?php p($calendarSyncStatus['state']); ?>"><?php p($l->t('Letzter Lauf:')); ?> <time datetime="<?php p(gmdate(DATE_ATOM, $calendarSyncStatus['lastRunAt'])); ?>"><?php p($calendarSyncStatus['lastRunLabel']); ?></time></p>
            <dl class="flz-calendar-sync-summary">
                <div><dt><?php p($l->t('Geprüft')); ?></dt><dd><?php p((string)$calendarSyncStatus['attempted']); ?></dd></div>
                <div><dt><?php p($l->t('Erfolgreich')); ?></dt><dd><?php p((string)$calendarSyncStatus['succeeded']); ?></dd></div>
                <div><dt><?php p($l->t('Fehlgeschlagen')); ?></dt><dd><?php p((string)$calendarSyncStatus['failed']); ?></dd></div>
            </dl>
        <?php else: ?>
            <p class="flz-calendar-sync-state"><?php p($calendarSyncStatus['lastRunLabel']); ?>.</p>
        <?php endif; ?>
        <p class="flz-calendar-sync-privacy"><?php p($l->t('Keine Konten- oder Kalenderkennungen werden in diesem Status gespeichert oder angezeigt.')); ?></p>
    </section>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-kopano-caldav-heading">
        <h3 id="flz-calendar-kopano-caldav-heading"><?php p($l->t('Kopano und CalDAV')); ?></h3>
        <p><strong><?php p($l->t('Voraussetzung:')); ?></strong> <?php p($l->t('Der Kopano-Betreiber muss CalDAV per HTTPS bereitstellen und Methoden wie PROPFIND an den Kopano-CalDAV-Dienst weiterleiten. Filzmann Kalender kann diesen Zugang nicht selbst auf dem fremden Server freischalten.')); ?></p>
        <p><?php p($l->t('HTTP 405 bedeutet: Der Betreiber erlaubt an dieser Adresse keine CalDAV-Verbindung. Ohne Zugangsdaten ist HTTP 401 eine erwartbare Anmeldeaufforderung; ein erfolgreicher authentifizierter PROPFIND antwortet üblicherweise mit HTTP 207.')); ?></p>
        <form id="flz-calendar-calendar-defaults-form" class="flz-calendar-calendar-defaults-form">
            <label for="flz-calendar-calendar-default-kopano-url"><?php p($l->t('Kopano-/CalDAV-Vorgabe')); ?></label>
            <input id="flz-calendar-calendar-default-kopano-url" type="url" value="<?php p($calendarDefaults['kopanoUrl']); ?>" inputmode="url" autocomplete="url" required>
            <label for="flz-calendar-calendar-default-name"><?php p($l->t('Sichtbarer Zielkalendername')); ?></label>
            <input id="flz-calendar-calendar-default-name" type="text" value="<?php p($calendarDefaults['calendarName']); ?>" maxlength="255" required>
            <small><?php p($l->t('Persönlich gespeicherte Serveradressen bleiben unverändert. Vorhandene app-eigene Kalender werden beim nächsten erfolgreichen Abgleich umbenannt; technische Kalender- und Objektkennungen bleiben stabil.')); ?></small>
            <div><button id="flz-calendar-calendar-defaults-submit" type="submit" class="primary"><?php p($l->t('Kalenderdefaults speichern')); ?></button></div>
        </form>
        <p id="flz-calendar-calendar-defaults-status" class="flz-calendar-calendar-defaults-status" role="status" aria-live="polite"><?php p($l->t('Kalenderdefaults sind geladen.')); ?></p>
        <p><?php p($l->t('Die Vorgabe %s bleibt im persönlichen Verbindungsdialog änderbar. Bei einer abweichenden veröffentlichten CalDAV-Adresse muss dort die vollständige HTTPS-Adresse eingetragen werden.', [$calendarDefaults['kopanoUrl']])); ?></p>
        <form id="flz-calendar-kopano-test-form" class="flz-calendar-kopano-test-form">
            <label for="flz-calendar-kopano-test-url"><?php p($l->t('HTTPS-CalDAV-Adresse')); ?></label>
            <input id="flz-calendar-kopano-test-url" type="url" value="<?php p($calendarDefaults['kopanoUrl']); ?>" inputmode="url" autocomplete="url" required>
            <label for="flz-calendar-kopano-test-username"><?php p($l->t('Kopano-Benutzername')); ?></label>
            <input id="flz-calendar-kopano-test-username" type="text" autocomplete="username" maxlength="320" required>
            <label for="flz-calendar-kopano-test-password"><?php p($l->t('Kopano-Passwort')); ?></label>
            <input id="flz-calendar-kopano-test-password" type="password" autocomplete="off" maxlength="4096" required>
            <small><?php p($l->t('Der Test führt ausschließlich eine lesende CalDAV-Anfrage aus. Zugangsdaten werden nicht gespeichert und kein Kalender wird angelegt.')); ?></small>
            <div><button id="flz-calendar-kopano-test-submit" type="submit" class="primary"><?php p($l->t('Verbindung testen')); ?></button></div>
        </form>
        <p id="flz-calendar-kopano-test-status" class="flz-calendar-kopano-test-status" role="status" aria-live="polite"><?php p($l->t('Noch nicht getestet.')); ?></p>
    </section>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-google-oauth-heading">
        <h3 id="flz-calendar-google-oauth-heading"><?php p($l->t('Google Calendar OAuth')); ?></h3>
        <p><?php p($l->t('Diese systemweite Webclient-Konfiguration ermöglicht allen Nutzer*innen, ihr eigenes Google-Konto im persönlichen Einstellungs-Tab zu verbinden.')); ?></p>
        <details class="flz-calendar-google-registration-guide">
            <summary><?php p($l->t('Google-App registrieren – Schritt für Schritt')); ?></summary>
            <div class="flz-calendar-google-registration-guide__content">
                <p><?php p($l->t('Für die Registrierung wird ein Google-Cloud-Projekt benötigt. Die folgenden Schritte werden einmalig von einer Administration ausgeführt:')); ?></p>
                <ol>
                    <li><?php p($l->t('In der folgenden Konsole ein Projekt auswählen oder neu anlegen:')); ?> <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>.</li>
                    <li><?php p($l->t('Unter APIs und Dienste → Bibliothek die Google Calendar API suchen und aktivieren.')); ?></li>
                    <li><?php p($l->t('Unter Google Auth Platform → Branding App-Name, Support-E-Mail und Kontaktadresse eintragen.')); ?></li>
                    <li><?php p($l->t('Unter Google Auth Platform → Zielgruppe Intern wählen, wenn ausschließlich Konten derselben Google-Workspace-Organisation zugreifen. Andernfalls Extern wählen. Im Testmodus müssen die vorgesehenen Konten als Testnutzer*innen eingetragen werden; Refresh-Tokens können dann bereits nach sieben Tagen ablaufen.')); ?></li>
                    <li><?php p($l->t('Unter Google Auth Platform → Datenzugriff exakt den folgenden Scope hinzufügen:')); ?> <code>https://www.googleapis.com/auth/calendar.app.created</code>. <?php p($l->t('Er erlaubt Filzmann Kalender nur den Zugriff auf von der App erstellte sekundäre Kalender und deren Termine.')); ?></li>
                    <li><?php p($l->t('Die unten angezeigte autorisierte Weiterleitungs-URI über „URI kopieren“ übernehmen.')); ?></li>
                    <li><?php p($l->t('Unter Google Auth Platform → Clients einen OAuth-Client vom Typ Webanwendung erstellen und die kopierte URI exakt unter Autorisierte Weiterleitungs-URIs eintragen. Keine autorisierten JavaScript-Quellen hinzufügen.')); ?></li>
                    <li><?php p($l->t('Google-Client-ID und Google-Client-Secret hier eintragen und speichern. Das Secret wird anschließend nicht mehr angezeigt.')); ?></li>
                    <li><?php p($l->t('Danach können Nutzer*innen unter Filzmann Kalender → Einstellungen → Externe Kalender bei Google auf „Verbinden“ klicken.')); ?></li>
                </ol>
                <p><strong><?php p($l->t('Wichtig:')); ?></strong> <?php p($l->t('Protokoll, Domain, Pfad und ein möglicher abschließender Schrägstrich der Weiterleitungs-URI müssen exakt übereinstimmen. Für eine externe produktive App kann Google zusätzlich eine Verifizierung verlangen.')); ?></p>
                <p class="flz-calendar-google-registration-guide__sources"><?php p($l->t('Offizielle Dokumentation:')); ?> <a href="https://developers.google.com/workspace/calendar/api/auth" target="_blank" rel="noopener noreferrer"><?php p($l->t('Calendar-Berechtigungen')); ?></a> · <a href="https://developers.google.com/identity/protocols/oauth2/web-server" target="_blank" rel="noopener noreferrer"><?php p($l->t('OAuth für Webserver-Anwendungen')); ?></a></p>
            </div>
        </details>
        <p id="flz-calendar-google-oauth-status" class="flz-calendar-google-oauth-status" role="status" aria-live="polite" data-configured="<?php p($googleOAuth['configured'] ? 'true' : 'false'); ?>">
            <?php p($googleOAuth['configured'] ? $l->t('Google OAuth ist konfiguriert.') : $l->t('Google OAuth ist noch nicht konfiguriert.')); ?>
        </p>
        <form id="flz-calendar-google-oauth-form" class="flz-calendar-google-oauth-form">
            <label for="flz-calendar-google-redirect-uri"><?php p($l->t('Autorisierte Weiterleitungs-URI')); ?></label>
            <div class="flz-calendar-google-redirect-row">
                <input id="flz-calendar-google-redirect-uri" type="url" value="<?php p($googleOAuth['redirectUri']); ?>" readonly>
                <button id="flz-calendar-google-copy-redirect" type="button"><?php p($l->t('URI kopieren')); ?></button>
            </div>
            <small><?php p($l->t('Diese URI muss im Google-Cloud-OAuth-Client exakt als autorisierte Weiterleitungs-URI hinterlegt werden.')); ?></small>

            <label for="flz-calendar-google-client-id"><?php p($l->t('Google-Client-ID')); ?></label>
            <input id="flz-calendar-google-client-id" type="text" value="<?php p($googleOAuth['clientId']); ?>" maxlength="512" autocomplete="off" spellcheck="false" required>

            <label for="flz-calendar-google-client-secret"><?php p($l->t('Google-Client-Secret')); ?></label>
            <input id="flz-calendar-google-client-secret" type="password" value="" maxlength="4096" autocomplete="new-password" <?php if (!$googleOAuth['secretConfigured']): ?>required<?php endif; ?>>
            <small><?php p($googleOAuth['secretConfigured'] ? $l->t('Ein Secret ist sicher gespeichert. Leer lassen, um es unverändert zu behalten.') : $l->t('Das Secret wird sensitiv und lazy in der Nextcloud-AppConfig gespeichert und nie wieder angezeigt.')); ?></small>

            <div class="flz-calendar-google-oauth-actions">
                <button type="submit" class="primary"><?php p($l->t('Google-Konfiguration speichern')); ?></button>
                <button id="flz-calendar-google-oauth-remove" type="button" <?php if (!$googleOAuth['configured']): ?>disabled<?php endif; ?>><?php p($l->t('Konfiguration entfernen')); ?></button>
            </div>
        </form>
        <p class="flz-calendar-sync-privacy"><?php p($l->t('Beim Entfernen werden Client-ID und Client-Secret aus Nextcloud gelöscht. Bereits bei Google erteilte Nutzerfreigaben werden dadurch nicht automatisch widerrufen.')); ?></p>
    </section>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-demo-heading">
        <h3 id="flz-calendar-demo-heading"><?php p($l->t('Demo-Pack')); ?></h3>
        <p><?php p($l->t('Das Demo-Pack legt ausschließlich synthetische lokale Konten, bei Bedarf lokale Gruppen sowie neutrale Dienste und Termine an. Es wird nicht automatisch installiert und importiert keine WordPress-Bestandsdaten.')); ?></p>
        <p><?php p($l->t('Bereits vorhandene fremde Konten und schreibgeschützte LDAP-Gruppen führen vor der ersten Änderung zum Abbruch.')); ?></p>
        <p id="flz-calendar-demo-notice" class="flz-calendar-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="flz-calendar-demo-confirm"><input id="flz-calendar-demo-confirm" type="checkbox"> <?php p($l->t('Ich bestätige die Installation synthetischer Demodaten.')); ?></label>
        <button id="flz-calendar-demo-install" type="button" class="primary" disabled><?php p($l->t('Kalender-Demo-Pack installieren')); ?></button>
    </section>
</section>
