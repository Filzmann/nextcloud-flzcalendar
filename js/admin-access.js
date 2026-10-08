(function() {
    'use strict';

    const form = document.getElementById('flz-calendar-full-access-form');
    const history = document.getElementById('flz-calendar-full-access-history');
    const status = document.getElementById('flz-calendar-full-access-status');
    if (!form || !history || !status) return;

    const client = new window.LocalBase.api.ApiClient({ appId: 'flzcalendar' });
    const showStatus = (message, error = false) => {
        status.textContent = message;
        status.className = error ? 'is-error' : 'is-success';
        status.setAttribute('role', error ? 'alert' : 'status');
    };
    const formatDate = value => value ? new Intl.DateTimeFormat('de-DE', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—';
    const renderGrant = grant => {
        const row = document.createElement('tr');
        const now = Date.now();
        const active = !grant.revokedAt && new Date(grant.startsAt).getTime() <= now && new Date(grant.endsAt).getTime() > now;
        [grant.targetUid, grant.grantedBy, formatDate(grant.startsAt), formatDate(grant.endsAt), grant.revokedAt ? formatDate(grant.revokedAt) : (active ? 'Aktiv' : 'Planmäßig beendet')].forEach(value => {
            const cell = document.createElement('td');
            cell.textContent = value;
            row.append(cell);
        });
        const action = document.createElement('td');
        if (active) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = 'Widerrufen';
            button.dataset.revokeUid = grant.targetUid;
            action.append(button);
        }
        row.append(action);
        return row;
    };
    const load = async () => {
        try {
            const state = await client.request('/api/admin/full-access');
            history.replaceChildren();
            if (!state.history?.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 6;
                cell.textContent = 'Noch keine Freigaben protokolliert.';
                row.append(cell);
                history.append(row);
                return;
            }
            state.history.forEach(grant => history.append(renderGrant(grant)));
        } catch (error) {
            showStatus(error.message || 'Die Vollzugriffshistorie konnte nicht geladen werden.', true);
        }
    };

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const fields = new FormData(form);
        if (fields.get('enabled') !== 'on') return;
        try {
            await client.request('/api/admin/full-access', {
                method: 'POST',
                body: JSON.stringify({
                    targetUid: String(fields.get('targetUid') || '').trim(),
                    durationMinutes: Number(fields.get('durationMinutes')),
                }),
            });
            form.elements.enabled.checked = false;
            showStatus('Der zeitlich begrenzte Vollzugriff wurde aktiviert.');
            await load();
        } catch (error) {
            showStatus(error.message || 'Der Vollzugriff konnte nicht aktiviert werden.', true);
        }
    });
    history.addEventListener('click', async event => {
        const button = event.target.closest('button[data-revoke-uid]');
        if (!button) return;
        button.disabled = true;
        try {
            await client.request(`/api/admin/full-access/${encodeURIComponent(button.dataset.revokeUid)}`, { method: 'DELETE' });
            showStatus('Der Vollzugriff wurde widerrufen.');
            await load();
        } catch (error) {
            button.disabled = false;
            showStatus(error.message || 'Der Vollzugriff konnte nicht widerrufen werden.', true);
        }
    });
    load();
}());
