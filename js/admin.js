(function() {
    'use strict';

    const client = new window.OCA.FlzCalendar.Shared.api.ApiClient({ appId: 'flzcalendar' });
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
                showStatus(l10n.t('Die Kalenderdefaults wurden gespeichert. Vorhandene app-eigene Kalender werden beim nächsten Abgleich umbenannt.'));
            } catch (error) {
                showStatus(error.message || l10n.t('Die Kalendervorgaben konnten nicht gespeichert werden.'), true);
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
            showStatus(googleOAuth.configured ? l10n.t('Google OAuth ist konfiguriert.') : l10n.t('Google OAuth ist noch nicht konfiguriert.'), !googleOAuth.configured);
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
                showStatus(error.message || l10n.t('Die Google-OAuth-Konfiguration konnte nicht gespeichert werden.'), true);
            } finally {
                secret.value = '';
                if (submit) submit.disabled = false;
            }
        });

        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(redirect.value);
                showStatus(l10n.t('Die Weiterleitungs-URI wurde kopiert.'));
            } catch (error) {
                showStatus(l10n.t('Die Weiterleitungs-URI konnte nicht kopiert werden.'), true);
            }
        });

        remove.addEventListener('click', async () => {
            if (!window.confirm(l10n.t('Google-OAuth-Konfiguration entfernen? Bereits erteilte Freigaben bei Google werden dadurch nicht widerrufen.'))) return;
            remove.disabled = true;
            try {
                const response = await client.request('/api/admin/google-oauth', { method: 'DELETE', body: '{}' });
                applyStatus(response.googleOAuth);
            } catch (error) {
                showStatus(error.message || l10n.t('Die Google-OAuth-Konfiguration konnte nicht entfernt werden.'), true);
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
            showStatus(l10n.t('Die Kopano-CalDAV-Verbindung wird geprüft.'));
            try {
                const response = await client.request('/api/admin/external-calendars/caldav/test', {
                    method: 'POST',
                    body: JSON.stringify({ serverUrl: serverUrl.value, username: username.value, password: password.value }),
                });
                showStatus(response.message || l10n.t('Die Kopano-CalDAV-Verbindung wurde erfolgreich geprüft.'));
            } catch (error) {
                showStatus(error.message || l10n.t('Die Kopano-CalDAV-Verbindung konnte nicht geprüft werden.'), true);
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
            notice.textContent = l10n.t('Das Demo-Pack wird geprüft und installiert …');
            try {
                const response = await client.request('/api/admin/demo-pack/install', { method: 'POST', body: '{}' });
                const result = response.result;
                notice.classList.add('is-success');
                notice.textContent = l10n.t('{users} Konten und {groups} Gruppen angelegt; Kalenderdaten für {people} Personen erzeugt.', {
                    users: result.accounts.createdUsers,
                    groups: result.accounts.createdGroups,
                    people: result.createdCalendars,
                });
                confirmation.checked = false;
            } catch (error) {
                notice.classList.add('is-error');
                notice.textContent = error.message || l10n.t('Das Demo-Pack konnte nicht installiert werden.');
                button.disabled = false;
            }
        });
    }

    initCalendarDefaults();
    initGoogleOAuth();
    initCalDavTest();
    initDemoPack();
}());
