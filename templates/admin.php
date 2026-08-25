<?php
translation('adcalendar');
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adcalendar', 'modules/localization');
\OCP\Util::addScript('adcalendar', 'admin');
\OCP\Util::addStyle('adcalendar', 'admin');
$calendarSyncStatus = $_['calendarSyncStatus'] ?? ['hasRun' => false, 'lastRunAt' => 0, 'lastRunLabel' => $l->t('No background run recorded'), 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending'];
$googleOAuth = $_['googleOAuth'] ?? ['configured' => false, 'clientId' => '', 'secretConfigured' => false, 'redirectUri' => ''];
$calendarDefaults = $_['calendarDefaults'] ?? ['kopanoUrl' => 'https://mail.adberlin.org/', 'calendarName' => 'AD Dienste'];
?>
<section id="adcalendar-admin" class="section adc-admin" aria-labelledby="adc-admin-heading">
    <h2 id="adc-admin-heading"><?php p($l->t('AD Calendar')); ?></h2>
    <section class="adc-admin-panel" aria-labelledby="adc-full-access-heading">
        <h3 id="adc-full-access-heading">Zeitlich begrenzter Admin-Vollzugriff</h3>
        <p>Native Nextcloud-Administration erteilt keinen automatischen Zugriff auf Mitarbeiterkalender. Eine Freigabe gilt nur für das angegebene Administrationskonto. Maximal 24 Stunden sind zulässig.</p>
        <form id="adc-full-access-form">
            <label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label>
            <label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label>
            <label><input id="adc-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label>
            <button type="submit" class="primary">Freigabe aktivieren</button>
        </form>
        <p id="adc-full-access-status" role="status" aria-live="polite"></p>
        <table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="adc-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-calendar-sync-heading">
        <h3 id="adc-calendar-sync-heading"><?php p($l->t('Shift calendar synchronisation')); ?></h3>
        <p><?php p($l->t('The background job synchronises approved shift entries one-way from AD Calendar to personal DAV calendars. The technical structure remains open to a later bidirectional extension.')); ?></p>
        <?php if ($calendarSyncStatus['hasRun']): ?>
            <p class="adc-sync-state adc-sync-state--<?php p($calendarSyncStatus['state']); ?>"><?php p($l->t('Last run:')); ?> <time datetime="<?php p(gmdate(DATE_ATOM, $calendarSyncStatus['lastRunAt'])); ?>"><?php p($calendarSyncStatus['lastRunLabel']); ?></time></p>
            <dl class="adc-sync-summary">
                <div><dt><?php p($l->t('Checked')); ?></dt><dd><?php p((string)$calendarSyncStatus['attempted']); ?></dd></div>
                <div><dt><?php p($l->t('Successful')); ?></dt><dd><?php p((string)$calendarSyncStatus['succeeded']); ?></dd></div>
                <div><dt><?php p($l->t('Failed')); ?></dt><dd><?php p((string)$calendarSyncStatus['failed']); ?></dd></div>
            </dl>
        <?php else: ?>
            <p class="adc-sync-state"><?php p($calendarSyncStatus['lastRunLabel']); ?>.</p>
        <?php endif; ?>
        <p class="adc-sync-privacy"><?php p($l->t('No account or calendar identifiers are stored or displayed in this status.')); ?></p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-kopano-caldav-heading">
        <h3 id="adc-kopano-caldav-heading"><?php p($l->t('Kopano and CalDAV')); ?></h3>
        <p><strong><?php p($l->t('Requirement:')); ?></strong> <?php p($l->t('The Kopano provider must provide CalDAV over HTTPS and forward methods such as PROPFIND to the Kopano CalDAV service. AD Calendar cannot enable this access on the external server.')); ?></p>
        <p><?php p($l->t('HTTP 405 means that the provider does not allow a CalDAV connection at this address. Without credentials, HTTP 401 is an expected login request; a successful authenticated PROPFIND usually responds with HTTP 207.')); ?></p>
        <form id="adc-calendar-defaults-form" class="adc-calendar-defaults-form">
            <label for="adc-calendar-default-kopano-url"><?php p($l->t('Kopano / CalDAV default')); ?></label>
            <input id="adc-calendar-default-kopano-url" type="url" value="<?php p($calendarDefaults['kopanoUrl']); ?>" inputmode="url" autocomplete="url" required>
            <label for="adc-calendar-default-name"><?php p($l->t('Visible target calendar name')); ?></label>
            <input id="adc-calendar-default-name" type="text" value="<?php p($calendarDefaults['calendarName']); ?>" maxlength="255" required>
            <small><?php p($l->t('Personally saved server addresses remain unchanged. Existing app-owned calendars are renamed during the next successful synchronisation; technical calendar and object identifiers remain stable.')); ?></small>
            <div><button id="adc-calendar-defaults-submit" type="submit" class="primary"><?php p($l->t('Save calendar defaults')); ?></button></div>
        </form>
        <p id="adc-calendar-defaults-status" class="adc-calendar-defaults-status" role="status" aria-live="polite"><?php p($l->t('Calendar defaults are loaded.')); ?></p>
        <p><?php p($l->t('The default %s remains editable in the personal connection dialog. If the published CalDAV address differs, enter the full HTTPS address there.', [$calendarDefaults['kopanoUrl']])); ?></p>
        <form id="adc-kopano-test-form" class="adc-kopano-test-form">
            <label for="adc-kopano-test-url"><?php p($l->t('HTTPS CalDAV address')); ?></label>
            <input id="adc-kopano-test-url" type="url" value="<?php p($calendarDefaults['kopanoUrl']); ?>" inputmode="url" autocomplete="url" required>
            <label for="adc-kopano-test-username"><?php p($l->t('Kopano username')); ?></label>
            <input id="adc-kopano-test-username" type="text" autocomplete="username" maxlength="320" required>
            <label for="adc-kopano-test-password"><?php p($l->t('Kopano password')); ?></label>
            <input id="adc-kopano-test-password" type="password" autocomplete="off" maxlength="4096" required>
            <small><?php p($l->t('The test performs only a read-only CalDAV request. Credentials are not stored and no calendar is created.')); ?></small>
            <div><button id="adc-kopano-test-submit" type="submit" class="primary"><?php p($l->t('Test connection')); ?></button></div>
        </form>
        <p id="adc-kopano-test-status" class="adc-kopano-test-status" role="status" aria-live="polite"><?php p($l->t('Not tested yet.')); ?></p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-google-oauth-heading">
        <h3 id="adc-google-oauth-heading"><?php p($l->t('Google Calendar OAuth')); ?></h3>
        <p><?php p($l->t('This system-wide web client configuration allows all users to connect their own Google account in the personal settings tab.')); ?></p>
        <details class="adc-google-registration-guide">
            <summary><?php p($l->t('Register a Google app – step by step')); ?></summary>
            <div class="adc-google-registration-guide__content">
                <p><?php p($l->t('A Google Cloud project is required for registration. The following steps are performed once by an administrator:')); ?></p>
                <ol>
                    <li><?php p($l->t('Select or create a project in the')); ?> <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>.</li>
                    <li><?php p($l->t('Under APIs and services → Library, search for and enable the Google Calendar API.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Branding, enter the app name, support email and contact address.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Audience, select Internal if only accounts from the same Google Workspace organisation may access it. Otherwise select External. In test mode, add the intended accounts as test users; refresh tokens may then expire after only seven days.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Data access, add exactly the scope')); ?> <code>https://www.googleapis.com/auth/calendar.app.created</code>. <?php p($l->t('It allows AD Calendar to access only secondary calendars created by the app and their appointments.')); ?></li>
                    <li><?php p($l->t('Copy the authorised redirect URI shown below using “Copy URI”.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Clients, create an OAuth client of type Web application and enter the copied URI exactly under Authorised redirect URIs. Do not add authorised JavaScript origins.')); ?></li>
                    <li><?php p($l->t('Enter and save the Google client ID and Google client secret here. The secret is not displayed afterwards.')); ?></li>
                    <li><?php p($l->t('Users can then select Connect for Google under AD Calendar → Settings → External calendars.')); ?></li>
                </ol>
                <p><strong><?php p($l->t('Important:')); ?></strong> <?php p($l->t('The protocol, domain, path and any trailing slash of the redirect URI must match exactly. Google may additionally require verification for an external production app.')); ?></p>
                <p class="adc-google-registration-guide__sources"><?php p($l->t('Official documentation:')); ?> <a href="https://developers.google.com/workspace/calendar/api/auth" target="_blank" rel="noopener noreferrer"><?php p($l->t('Calendar permissions')); ?></a> · <a href="https://developers.google.com/identity/protocols/oauth2/web-server" target="_blank" rel="noopener noreferrer"><?php p($l->t('OAuth for web server applications')); ?></a></p>
            </div>
        </details>
        <p id="adc-google-oauth-status" class="adc-google-oauth-status" role="status" aria-live="polite" data-configured="<?php p($googleOAuth['configured'] ? 'true' : 'false'); ?>">
            <?php p($googleOAuth['configured'] ? $l->t('Google OAuth is configured.') : $l->t('Google OAuth is not configured yet.')); ?>
        </p>
        <form id="adc-google-oauth-form" class="adc-google-oauth-form">
            <label for="adc-google-redirect-uri"><?php p($l->t('Authorised redirect URI')); ?></label>
            <div class="adc-google-redirect-row">
                <input id="adc-google-redirect-uri" type="url" value="<?php p($googleOAuth['redirectUri']); ?>" readonly>
                <button id="adc-google-copy-redirect" type="button"><?php p($l->t('Copy URI')); ?></button>
            </div>
            <small><?php p($l->t('This URI must be entered exactly as an authorised redirect URI in the Google Cloud OAuth client.')); ?></small>

            <label for="adc-google-client-id"><?php p($l->t('Google client ID')); ?></label>
            <input id="adc-google-client-id" type="text" value="<?php p($googleOAuth['clientId']); ?>" maxlength="512" autocomplete="off" spellcheck="false" required>

            <label for="adc-google-client-secret"><?php p($l->t('Google client secret')); ?></label>
            <input id="adc-google-client-secret" type="password" value="" maxlength="4096" autocomplete="new-password" <?php if (!$googleOAuth['secretConfigured']): ?>required<?php endif; ?>>
            <small><?php p($googleOAuth['secretConfigured'] ? $l->t('A secret is stored securely. Leave blank to keep it unchanged.') : $l->t('The secret is stored as sensitive and lazy Nextcloud AppConfig and is never displayed again.')); ?></small>

            <div class="adc-google-oauth-actions">
                <button type="submit" class="primary"><?php p($l->t('Save Google configuration')); ?></button>
                <button id="adc-google-oauth-remove" type="button" <?php if (!$googleOAuth['configured']): ?>disabled<?php endif; ?>><?php p($l->t('Remove configuration')); ?></button>
            </div>
        </form>
        <p class="adc-sync-privacy"><?php p($l->t('Removing the configuration deletes the client ID and client secret from Nextcloud. User grants already issued by Google are not automatically revoked.')); ?></p>
    </section>
    <section class="adc-admin-panel" aria-labelledby="adc-demo-heading">
        <h3 id="adc-demo-heading"><?php p($l->t('Demo pack')); ?></h3>
        <p><?php p($l->t('The demo pack creates only synthetic local accounts, local groups where required, and neutral shifts and appointments. It is not installed automatically and does not import existing WordPress data.')); ?></p>
        <p><?php p($l->t('Existing foreign accounts and read-only LDAP groups cause the operation to stop before the first change.')); ?></p>
        <p id="adc-demo-notice" class="adc-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="adc-demo-confirm"><input id="adc-demo-confirm" type="checkbox"> <?php p($l->t('I confirm the installation of synthetic demo data.')); ?></label>
        <button id="adc-demo-install" type="button" class="primary" disabled><?php p($l->t('Install calendar demo pack')); ?></button>
    </section>
</section>
