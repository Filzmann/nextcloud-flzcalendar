(function() {
    'use strict';

    const client = new window.LocalBase.api.ApiClient({ appId: 'adcalendar' });

    function initGoogleOAuth() {
        const form = document.getElementById('adc-google-oauth-form');
        if (!form) return;
        const clientId = document.getElementById('adc-google-client-id');
        const secret = document.getElementById('adc-google-client-secret');
        const status = document.getElementById('adc-google-oauth-status');
        const remove = document.getElementById('adc-google-oauth-remove');
        const copy = document.getElementById('adc-google-copy-redirect');
        const redirect = document.getElementById('adc-google-redirect-uri');

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
            showStatus(googleOAuth.configured ? 'Google OAuth ist konfiguriert.' : 'Google OAuth ist noch nicht konfiguriert.', !googleOAuth.configured);
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
                showStatus(error.message || 'Die Google-OAuth-Konfiguration konnte nicht gespeichert werden.', true);
            } finally {
                secret.value = '';
                if (submit) submit.disabled = false;
            }
        });

        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(redirect.value);
                showStatus('Weiterleitungs-URI wurde kopiert.');
            } catch (error) {
                showStatus('Die Weiterleitungs-URI konnte nicht kopiert werden.', true);
            }
        });

        remove.addEventListener('click', async () => {
            if (!window.confirm('Google-OAuth-Konfiguration entfernen? Bereits erteilte Freigaben bei Google werden dadurch nicht widerrufen.')) return;
            remove.disabled = true;
            try {
                const response = await client.request('/api/admin/google-oauth', { method: 'DELETE', body: '{}' });
                applyStatus(response.googleOAuth);
            } catch (error) {
                showStatus(error.message || 'Die Google-OAuth-Konfiguration konnte nicht entfernt werden.', true);
                remove.disabled = status.dataset.configured !== 'true';
            } finally {
                secret.value = '';
            }
        });
    }

    function initCalDavTest() {
        const form = document.getElementById('adc-kopano-test-form');
        if (!form) return;
        const serverUrl = document.getElementById('adc-kopano-test-url');
        const username = document.getElementById('adc-kopano-test-username');
        const password = document.getElementById('adc-kopano-test-password');
        const status = document.getElementById('adc-kopano-test-status');
        const submit = document.getElementById('adc-kopano-test-submit');

        const showStatus = (message, error = false) => {
            status.textContent = message;
            status.classList.remove('is-success', 'is-error');
            status.classList.add(error ? 'is-error' : 'is-success');
        };

        form.addEventListener('submit', async event => {
            event.preventDefault();
            submit.disabled = true;
            showStatus('Kopano-CalDAV-Verbindung wird geprüft.');
            try {
                const response = await client.request('/api/admin/external-calendars/caldav/test', {
                    method: 'POST',
                    body: JSON.stringify({ serverUrl: serverUrl.value, username: username.value, password: password.value }),
                });
                showStatus(response.message || 'Kopano-CalDAV-Verbindung erfolgreich geprüft.');
            } catch (error) {
                showStatus(error.message || 'Die Kopano-CalDAV-Verbindung konnte nicht geprüft werden.', true);
            } finally {
                password.value = '';
                submit.disabled = false;
            }
        });
    }

    function initDemoPack() {
        const confirmation = document.getElementById('adc-demo-confirm');
        const button = document.getElementById('adc-demo-install');
        const notice = document.getElementById('adc-demo-notice');
        if (!confirmation || !button || !notice) return;

        confirmation.addEventListener('change', () => { button.disabled = !confirmation.checked; });
        button.addEventListener('click', async () => {
            if (!confirmation.checked) return;
            button.disabled = true;
            notice.hidden = false;
            notice.className = 'adc-admin-notice';
            notice.textContent = 'Demo-Pack wird geprüft und installiert …';
            try {
                const response = await client.request('/api/admin/demo-pack/install', { method: 'POST', body: '{}' });
                const result = response.result;
                notice.classList.add('is-success');
                notice.textContent = `${result.accounts.createdUsers} Konten und ${result.accounts.createdGroups} Gruppen angelegt; Kalenderdaten für ${result.createdCalendars} Personen erzeugt.`;
                confirmation.checked = false;
            } catch (error) {
                notice.classList.add('is-error');
                notice.textContent = error.message || 'Das Demo-Pack konnte nicht installiert werden.';
                button.disabled = false;
            }
        });
    }

    initGoogleOAuth();
    initCalDavTest();
    initDemoPack();
}());
