(function() {
    'use strict';

    const client = new window.LocalBase.api.ApiClient({ appId: 'adcalendar' });
    const l10n = window.AdCalendar.l10n;

    function initCalendarDefaults() {
        const form = document.getElementById('adc-calendar-defaults-form');
        if (!form) return;
        const kopanoUrl = document.getElementById('adc-calendar-default-kopano-url');
        const calendarName = document.getElementById('adc-calendar-default-name');
        const status = document.getElementById('adc-calendar-defaults-status');
        const submit = document.getElementById('adc-calendar-defaults-submit');
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

    function initFullAccess() {
        const form = document.getElementById('adc-full-access-form');
        const history = document.getElementById('adc-full-access-history');
        const status = document.getElementById('adc-full-access-status');
        if (!form || !history || !status) return;
        const show = (message, error = false) => { status.textContent = message; status.className = error ? 'is-error' : 'is-success'; };
        const date = value => value ? new Date(value).toLocaleString() : '—';
        const render = grant => {
            const row = document.createElement('tr');
            const now = Date.now();
            const active = !grant.revokedAt && new Date(grant.startsAt).getTime() <= now && new Date(grant.endsAt).getTime() > now;
            [grant.targetUid, grant.grantedBy, date(grant.startsAt), date(grant.endsAt), grant.revokedAt ? date(grant.revokedAt) : (active ? 'Aktiv' : 'Planmäßig beendet')].forEach(value => {
                const cell = document.createElement('td'); cell.textContent = value; row.append(cell);
            });
            const action = document.createElement('td');
            if (active) { const button = document.createElement('button'); button.type = 'button'; button.textContent = 'Widerrufen'; button.dataset.revokeUid = grant.targetUid; action.append(button); }
            row.append(action); return row;
        };
        const load = async () => {
            try {
                const state = await client.request('/api/admin/full-access'); history.replaceChildren();
                if (!state.history?.length) { const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 6; cell.textContent = 'Noch keine Freigaben protokolliert.'; row.append(cell); history.append(row); return; }
                state.history.forEach(grant => history.append(render(grant)));
            } catch (error) { show(error.message || 'Die Vollzugriffshistorie konnte nicht geladen werden.', true); }
        };
        form.addEventListener('submit', async event => {
            event.preventDefault(); const fields = new FormData(form); if (fields.get('enabled') !== 'on') return;
            try {
                await client.request('/api/admin/full-access', { method: 'POST', body: JSON.stringify({ targetUid: String(fields.get('targetUid') || '').trim(), durationMinutes: Number(fields.get('durationMinutes')) }) });
                form.elements.enabled.checked = false; show('Der zeitlich begrenzte Vollzugriff wurde aktiviert.'); await load();
            } catch (error) { show(error.message || 'Der Vollzugriff konnte nicht aktiviert werden.', true); }
        });
        history.addEventListener('click', async event => {
            const button = event.target.closest('button[data-revoke-uid]'); if (!button) return; button.disabled = true;
            try { await client.request(`/api/admin/full-access/${encodeURIComponent(button.dataset.revokeUid)}`, { method: 'DELETE' }); show('Der Vollzugriff wurde widerrufen.'); await load(); }
            catch (error) { button.disabled = false; show(error.message || 'Der Vollzugriff konnte nicht widerrufen werden.', true); }
        });
        load();
    }

    initCalendarDefaults();
    initGoogleOAuth();
    initCalDavTest();
    initDemoPack();
    initFullAccess();
}());
