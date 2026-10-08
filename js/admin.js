(function() {
    'use strict';

    const client = new window.LocalBase.api.ApiClient({ appId: 'flzcalendar' });
    const l10n = window.FlzCalendar.l10n;

    function initCalendarDefaults() {
        const form = document.getElementById('flz-calendar-calendar-defaults-form');
        if (!form) return;
        const kopanoUrl = document.getElementById('flz-calendar-calendar-default-kopano-url');
        const calendarName = document.getElementById('flz-calendar-calendar-default-name');
        const status = document.getElementById('flz-calendar-calendar-defaults-status');
        const submit = document.getElementById('flz-calendar-calendar-defaults-submit');
        const showStatus = (message, error = false) => {
            status.textContent = message;
            status.classList.remove('is-success', 'is-error');
            status.classList.add(error ? 'is-error' : 'is-success');
        };
        form.addEventListener('submit', async event => {
            event.preventDefault();
            submit.disabled = true;
            try {
                const response = await client.request('/api/admin/calendar-defaults', {
                    method: 'PUT',
                    body: JSON.stringify({ kopanoUrl: kopanoUrl.value, calendarName: calendarName.value }),
                });
                kopanoUrl.value = response.calendarDefaults.kopanoUrl;
                calendarName.value = response.calendarDefaults.calendarName;
                showStatus(l10n.t('The calendar defaults were saved. Existing app-owned calendars will be renamed during the next synchronisation.'));
            } catch (error) {
                showStatus(error.message || l10n.t('The calendar defaults could not be saved.'), true);
            } finally {
                submit.disabled = false;
            }
        });
    }

    function initGoogleOAuth() {
        const form = document.getElementById('flz-calendar-google-oauth-form');
        if (!form) return;
        const clientId = document.getElementById('flz-calendar-google-client-id');
        const secret = document.getElementById('flz-calendar-google-client-secret');
        const status = document.getElementById('flz-calendar-google-oauth-status');
        const remove = document.getElementById('flz-calendar-google-oauth-remove');
        const copy = document.getElementById('flz-calendar-google-copy-redirect');
        const redirect = document.getElementById('flz-calendar-google-redirect-uri');

        const showStatus = (message, error = false) => {
            status.textContent = message;
            status.classList.remove('is-success', 'is-error');
            status.classList.add(error ? 'is-error' : 'is-success');
        };
        const applyStatus = googleOAuth => {
            clientId.value = googleOAuth.clientId || '';
            secret.required = !googleOAuth.secretConfigured;
            remove.disabled = !googleOAuth.configured;
            status.dataset.configured = String(Boolean(googleOAuth.configured));
            showStatus(googleOAuth.configured ? l10n.t('Google OAuth is configured.') : l10n.t('Google OAuth is not configured yet.'), !googleOAuth.configured);
        };

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const submit = form.querySelector?.('button[type="submit"]');
            if (submit) submit.disabled = true;
            try {
                const response = await client.request('/api/admin/google-oauth', {
                    method: 'PUT',
                    body: JSON.stringify({ clientId: clientId.value, clientSecret: secret.value }),
                });
                applyStatus(response.googleOAuth);
            } catch (error) {
                showStatus(error.message || l10n.t('The Google OAuth configuration could not be saved.'), true);
            } finally {
                secret.value = '';
                if (submit) submit.disabled = false;
            }
        });

        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(redirect.value);
                showStatus(l10n.t('The redirect URI was copied.'));
            } catch (error) {
                showStatus(l10n.t('The redirect URI could not be copied.'), true);
            }
        });

        remove.addEventListener('click', async () => {
            if (!window.confirm(l10n.t('Remove the Google OAuth configuration? Grants already issued by Google will not be revoked.'))) return;
            remove.disabled = true;
            try {
                const response = await client.request('/api/admin/google-oauth', { method: 'DELETE', body: '{}' });
                applyStatus(response.googleOAuth);
            } catch (error) {
                showStatus(error.message || l10n.t('The Google OAuth configuration could not be removed.'), true);
                remove.disabled = status.dataset.configured !== 'true';
            } finally {
                secret.value = '';
            }
        });
    }

    function initCalDavTest() {
        const form = document.getElementById('flz-calendar-kopano-test-form');
        if (!form) return;
        const serverUrl = document.getElementById('flz-calendar-kopano-test-url');
        const username = document.getElementById('flz-calendar-kopano-test-username');
        const password = document.getElementById('flz-calendar-kopano-test-password');
        const status = document.getElementById('flz-calendar-kopano-test-status');
        const submit = document.getElementById('flz-calendar-kopano-test-submit');

        const showStatus = (message, error = false) => {
            status.textContent = message;
            status.classList.remove('is-success', 'is-error');
            status.classList.add(error ? 'is-error' : 'is-success');
        };

        form.addEventListener('submit', async event => {
            event.preventDefault();
            submit.disabled = true;
            showStatus(l10n.t('The Kopano CalDAV connection is being tested.'));
            try {
                const response = await client.request('/api/admin/external-calendars/caldav/test', {
                    method: 'POST',
                    body: JSON.stringify({ serverUrl: serverUrl.value, username: username.value, password: password.value }),
                });
                showStatus(response.message || l10n.t('The Kopano CalDAV connection was tested successfully.'));
            } catch (error) {
                showStatus(error.message || l10n.t('The Kopano CalDAV connection could not be tested.'), true);
            } finally {
                password.value = '';
                submit.disabled = false;
            }
        });
    }

    function initDemoPack() {
        const confirmation = document.getElementById('flz-calendar-demo-confirm');
        const button = document.getElementById('flz-calendar-demo-install');
        const notice = document.getElementById('flz-calendar-demo-notice');
        if (!confirmation || !button || !notice) return;

        confirmation.addEventListener('change', () => { button.disabled = !confirmation.checked; });
        button.addEventListener('click', async () => {
            if (!confirmation.checked) return;
            button.disabled = true;
            notice.hidden = false;
            notice.className = 'flz-calendar-admin-notice';
            notice.textContent = l10n.t('The demo pack is being checked and installed …');
            try {
                const response = await client.request('/api/admin/demo-pack/install', { method: 'POST', body: '{}' });
                const result = response.result;
                notice.classList.add('is-success');
                notice.textContent = l10n.t('{users} accounts and {groups} groups created; calendar data generated for {people} people.', {
                    users: result.accounts.createdUsers,
                    groups: result.accounts.createdGroups,
                    people: result.createdCalendars,
                });
                confirmation.checked = false;
            } catch (error) {
                notice.classList.add('is-error');
                notice.textContent = error.message || l10n.t('The demo pack could not be installed.');
                button.disabled = false;
            }
        });
    }

    initCalendarDefaults();
    initGoogleOAuth();
    initCalDavTest();
    initDemoPack();
}());
