<?php
translation('flzcalendar');
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('flzcalendar', 'modules/localization');
\OCP\Util::addScript('flzcalendar', 'admin');
\OCP\Util::addStyle('flzcalendar', 'admin');
$calendarSyncStatus = $_['calendarSyncStatus'] ?? ['hasRun' => false, 'lastRunAt' => 0, 'lastRunLabel' => $l->t('No background run recorded'), 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending'];
$googleOAuth = $_['googleOAuth'] ?? ['configured' => false, 'clientId' => '', 'secretConfigured' => false, 'redirectUri' => ''];
$calendarDefaults = $_['calendarDefaults'] ?? ['kopanoUrl' => '', 'calendarName' => 'Filzmann Dienste'];
?>
<section id="flzcalendar-admin" class="section flz-calendar-admin" aria-labelledby="flz-calendar-admin-heading">
    <h2 id="flz-calendar-admin-heading"><?php p($l->t('Filzmann Calendar')); ?></h2>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-calendar-sync-heading">
        <h3 id="flz-calendar-calendar-sync-heading"><?php p($l->t('Shift calendar synchronisation')); ?></h3>
        <p><?php p($l->t('The background job synchronises approved shift entries one-way from Filzmann Calendar to personal DAV calendars. The technical structure remains open to a later bidirectional extension.')); ?></p>
        <?php if ($calendarSyncStatus['hasRun']): ?>
            <p class="flz-calendar-sync-state flz-calendar-sync-state--<?php p($calendarSyncStatus['state']); ?>"><?php p($l->t('Last run:')); ?> <time datetime="<?php p(gmdate(DATE_ATOM, $calendarSyncStatus['lastRunAt'])); ?>"><?php p($calendarSyncStatus['lastRunLabel']); ?></time></p>
            <dl class="flz-calendar-sync-summary">
                <div><dt><?php p($l->t('Checked')); ?></dt><dd><?php p((string)$calendarSyncStatus['attempted']); ?></dd></div>
                <div><dt><?php p($l->t('Successful')); ?></dt><dd><?php p((string)$calendarSyncStatus['succeeded']); ?></dd></div>
                <div><dt><?php p($l->t('Failed')); ?></dt><dd><?php p((string)$calendarSyncStatus['failed']); ?></dd></div>
            </dl>
        <?php else: ?>
            <p class="flz-calendar-sync-state"><?php p($calendarSyncStatus['lastRunLabel']); ?>.</p>
        <?php endif; ?>
        <p class="flz-calendar-sync-privacy"><?php p($l->t('No account or calendar identifiers are stored or displayed in this status.')); ?></p>
    </section>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-kopano-caldav-heading">
        <h3 id="flz-calendar-kopano-caldav-heading"><?php p($l->t('Kopano and CalDAV')); ?></h3>
        <p><strong><?php p($l->t('Requirement:')); ?></strong> <?php p($l->t('The Kopano provider must provide CalDAV over HTTPS and forward methods such as PROPFIND to the Kopano CalDAV service. Filzmann Calendar cannot enable this access on the external server.')); ?></p>
        <p><?php p($l->t('HTTP 405 means that the provider does not allow a CalDAV connection at this address. Without credentials, HTTP 401 is an expected login request; a successful authenticated PROPFIND usually responds with HTTP 207.')); ?></p>
        <form id="flz-calendar-calendar-defaults-form" class="flz-calendar-calendar-defaults-form">
            <label for="flz-calendar-calendar-default-kopano-url"><?php p($l->t('Kopano / CalDAV default')); ?></label>
            <input id="flz-calendar-calendar-default-kopano-url" type="url" value="<?php p($calendarDefaults['kopanoUrl']); ?>" inputmode="url" autocomplete="url" required>
            <label for="flz-calendar-calendar-default-name"><?php p($l->t('Visible target calendar name')); ?></label>
            <input id="flz-calendar-calendar-default-name" type="text" value="<?php p($calendarDefaults['calendarName']); ?>" maxlength="255" required>
            <small><?php p($l->t('Personally saved server addresses remain unchanged. Existing app-owned calendars are renamed during the next successful synchronisation; technical calendar and object identifiers remain stable.')); ?></small>
            <div><button id="flz-calendar-calendar-defaults-submit" type="submit" class="primary"><?php p($l->t('Save calendar defaults')); ?></button></div>
        </form>
        <p id="flz-calendar-calendar-defaults-status" class="flz-calendar-calendar-defaults-status" role="status" aria-live="polite"><?php p($l->t('Calendar defaults are loaded.')); ?></p>
        <p><?php p($l->t('The default %s remains editable in the personal connection dialog. If the published CalDAV address differs, enter the full HTTPS address there.', [$calendarDefaults['kopanoUrl']])); ?></p>
        <form id="flz-calendar-kopano-test-form" class="flz-calendar-kopano-test-form">
            <label for="flz-calendar-kopano-test-url"><?php p($l->t('HTTPS CalDAV address')); ?></label>
            <input id="flz-calendar-kopano-test-url" type="url" value="<?php p($calendarDefaults['kopanoUrl']); ?>" inputmode="url" autocomplete="url" required>
            <label for="flz-calendar-kopano-test-username"><?php p($l->t('Kopano username')); ?></label>
            <input id="flz-calendar-kopano-test-username" type="text" autocomplete="username" maxlength="320" required>
            <label for="flz-calendar-kopano-test-password"><?php p($l->t('Kopano password')); ?></label>
            <input id="flz-calendar-kopano-test-password" type="password" autocomplete="off" maxlength="4096" required>
            <small><?php p($l->t('The test performs only a read-only CalDAV request. Credentials are not stored and no calendar is created.')); ?></small>
            <div><button id="flz-calendar-kopano-test-submit" type="submit" class="primary"><?php p($l->t('Test connection')); ?></button></div>
        </form>
        <p id="flz-calendar-kopano-test-status" class="flz-calendar-kopano-test-status" role="status" aria-live="polite"><?php p($l->t('Not tested yet.')); ?></p>
    </section>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-google-oauth-heading">
        <h3 id="flz-calendar-google-oauth-heading"><?php p($l->t('Google Calendar OAuth')); ?></h3>
        <p><?php p($l->t('This system-wide web client configuration allows all users to connect their own Google account in the personal settings tab.')); ?></p>
        <details class="flz-calendar-google-registration-guide">
            <summary><?php p($l->t('Register a Google app – step by step')); ?></summary>
            <div class="flz-calendar-google-registration-guide__content">
                <p><?php p($l->t('A Google Cloud project is required for registration. The following steps are performed once by an administrator:')); ?></p>
                <ol>
                    <li><?php p($l->t('Select or create a project in the')); ?> <a href="https://console.cloud.google.com/" target="_blank" rel="noopener noreferrer">Google Cloud Console</a>.</li>
                    <li><?php p($l->t('Under APIs and services → Library, search for and enable the Google Calendar API.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Branding, enter the app name, support email and contact address.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Audience, select Internal if only accounts from the same Google Workspace organisation may access it. Otherwise select External. In test mode, add the intended accounts as test users; refresh tokens may then expire after only seven days.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Data access, add exactly the scope')); ?> <code>https://www.googleapis.com/auth/calendar.app.created</code>. <?php p($l->t('It allows Filzmann Calendar to access only secondary calendars created by the app and their appointments.')); ?></li>
                    <li><?php p($l->t('Copy the authorised redirect URI shown below using “Copy URI”.')); ?></li>
                    <li><?php p($l->t('Under Google Auth Platform → Clients, create an OAuth client of type Web application and enter the copied URI exactly under Authorised redirect URIs. Do not add authorised JavaScript origins.')); ?></li>
                    <li><?php p($l->t('Enter and save the Google client ID and Google client secret here. The secret is not displayed afterwards.')); ?></li>
                    <li><?php p($l->t('Users can then select Connect for Google under Filzmann Calendar → Settings → External calendars.')); ?></li>
                </ol>
                <p><strong><?php p($l->t('Important:')); ?></strong> <?php p($l->t('The protocol, domain, path and any trailing slash of the redirect URI must match exactly. Google may additionally require verification for an external production app.')); ?></p>
                <p class="flz-calendar-google-registration-guide__sources"><?php p($l->t('Official documentation:')); ?> <a href="https://developers.google.com/workspace/calendar/api/auth" target="_blank" rel="noopener noreferrer"><?php p($l->t('Calendar permissions')); ?></a> · <a href="https://developers.google.com/identity/protocols/oauth2/web-server" target="_blank" rel="noopener noreferrer"><?php p($l->t('OAuth for web server applications')); ?></a></p>
            </div>
        </details>
        <p id="flz-calendar-google-oauth-status" class="flz-calendar-google-oauth-status" role="status" aria-live="polite" data-configured="<?php p($googleOAuth['configured'] ? 'true' : 'false'); ?>">
            <?php p($googleOAuth['configured'] ? $l->t('Google OAuth is configured.') : $l->t('Google OAuth is not configured yet.')); ?>
        </p>
        <form id="flz-calendar-google-oauth-form" class="flz-calendar-google-oauth-form">
            <label for="flz-calendar-google-redirect-uri"><?php p($l->t('Authorised redirect URI')); ?></label>
            <div class="flz-calendar-google-redirect-row">
                <input id="flz-calendar-google-redirect-uri" type="url" value="<?php p($googleOAuth['redirectUri']); ?>" readonly>
                <button id="flz-calendar-google-copy-redirect" type="button"><?php p($l->t('Copy URI')); ?></button>
            </div>
            <small><?php p($l->t('This URI must be entered exactly as an authorised redirect URI in the Google Cloud OAuth client.')); ?></small>

            <label for="flz-calendar-google-client-id"><?php p($l->t('Google client ID')); ?></label>
            <input id="flz-calendar-google-client-id" type="text" value="<?php p($googleOAuth['clientId']); ?>" maxlength="512" autocomplete="off" spellcheck="false" required>

            <label for="flz-calendar-google-client-secret"><?php p($l->t('Google client secret')); ?></label>
            <input id="flz-calendar-google-client-secret" type="password" value="" maxlength="4096" autocomplete="new-password" <?php if (!$googleOAuth['secretConfigured']): ?>required<?php endif; ?>>
            <small><?php p($googleOAuth['secretConfigured'] ? $l->t('A secret is stored securely. Leave blank to keep it unchanged.') : $l->t('The secret is stored as sensitive and lazy Nextcloud AppConfig and is never displayed again.')); ?></small>

            <div class="flz-calendar-google-oauth-actions">
                <button type="submit" class="primary"><?php p($l->t('Save Google configuration')); ?></button>
                <button id="flz-calendar-google-oauth-remove" type="button" <?php if (!$googleOAuth['configured']): ?>disabled<?php endif; ?>><?php p($l->t('Remove configuration')); ?></button>
            </div>
        </form>
        <p class="flz-calendar-sync-privacy"><?php p($l->t('Removing the configuration deletes the client ID and client secret from Nextcloud. User grants already issued by Google are not automatically revoked.')); ?></p>
    </section>
    <section class="flz-calendar-admin-panel" aria-labelledby="flz-calendar-demo-heading">
        <h3 id="flz-calendar-demo-heading"><?php p($l->t('Demo pack')); ?></h3>
        <p><?php p($l->t('The demo pack creates only synthetic local accounts, local groups where required, and neutral shifts and appointments. It is not installed automatically and does not import existing WordPress data.')); ?></p>
        <p><?php p($l->t('Existing foreign accounts and read-only LDAP groups cause the operation to stop before the first change.')); ?></p>
        <p id="flz-calendar-demo-notice" class="flz-calendar-admin-notice" role="status" aria-live="polite" hidden></p>
        <label class="flz-calendar-demo-confirm"><input id="flz-calendar-demo-confirm" type="checkbox"> <?php p($l->t('I confirm the installation of synthetic demo data.')); ?></label>
        <button id="flz-calendar-demo-install" type="button" class="primary" disabled><?php p($l->t('Install calendar demo pack')); ?></button>
    </section>
</section>
