import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../js/admin.js', import.meta.url), 'utf8');
if (!source.includes('window.FlzCalendar.l10n') || !source.includes("l10n.t('The calendar defaults were saved.")) {
    throw new Error('Admininteraktionen verwenden nicht den zentralen L10N-Adapter.');
}
const element = (value = '') => ({
    value, textContent: '', hidden: false, disabled: false, dataset: {}, listeners: {},
    className: '', classList: { add() {}, remove() {} },
    addEventListener(type, listener) { this.listeners[type] = listener; },
});
const form = element();
const clientId = element('client-id');
const secret = element('new-secret');
const status = element();
const remove = element();
const copy = element();
const redirect = element('https://cloud.example.test/callback');
const calDavForm = element();
const calDavUrl = element('https://calendar.example.test');
const calDavUsername = element('person-a');
const calDavPassword = element('connection-secret');
const calDavStatus = element();
const calDavSubmit = element();
const defaultsForm = element();
const defaultsUrl = element('https://calendar.example.test/caldav/');
const defaultsName = element('Team & Dienst');
const defaultsStatus = element();
const defaultsSubmit = element();
const elements = {
    'flz-calendar-google-oauth-form': form,
    'flz-calendar-google-client-id': clientId,
    'flz-calendar-google-client-secret': secret,
    'flz-calendar-google-oauth-status': status,
    'flz-calendar-google-oauth-remove': remove,
    'flz-calendar-google-copy-redirect': copy,
    'flz-calendar-google-redirect-uri': redirect,
    'flz-calendar-kopano-test-form': calDavForm,
    'flz-calendar-kopano-test-url': calDavUrl,
    'flz-calendar-kopano-test-username': calDavUsername,
    'flz-calendar-kopano-test-password': calDavPassword,
    'flz-calendar-kopano-test-status': calDavStatus,
    'flz-calendar-kopano-test-submit': calDavSubmit,
    'flz-calendar-calendar-defaults-form': defaultsForm,
    'flz-calendar-calendar-default-kopano-url': defaultsUrl,
    'flz-calendar-calendar-default-name': defaultsName,
    'flz-calendar-calendar-defaults-status': defaultsStatus,
    'flz-calendar-calendar-defaults-submit': defaultsSubmit,
};
const calls = [];
let failCalDav = false;
class ApiClient {
    async request(path, options) {
        calls.push([path, options]);
        if (path.includes('/external-calendars/caldav/test')) {
            if (failCalDav) throw new Error('Der Kopano-Betreiber erlaubt keine CalDAV-Verbindung.');
            return { message: 'Kopano-CalDAV-Verbindung erfolgreich geprüft (HTTP 207).' };
        }
        if (path.includes('/calendar-defaults')) {
            const body = JSON.parse(options.body);
            return { calendarDefaults: { kopanoUrl: `${body.kopanoUrl.replace(/\/$/, '')}/`, calendarName: body.calendarName.trim() } };
        }
        return { googleOAuth: options.method === 'DELETE'
            ? { configured: false, clientId: '', secretConfigured: false, redirectUri: redirect.value }
            : { configured: true, clientId: clientId.value, secretConfigured: true, redirectUri: redirect.value } };
    }
}
let copied = '';
const translationCalls = [];
const l10n = { t(key, parameters = {}) {
    translationCalls.push([key, parameters]);
    return `translated:${key}`.replace(/\{(\w+)\}/g, (_, name) => String(parameters[name] ?? `{${name}}`));
} };
const context = {
    window: { LocalBase: { api: { ApiClient } }, FlzCalendar: { l10n }, confirm: () => true },
    document: { getElementById: id => elements[id] || null },
    navigator: { clipboard: { writeText: async value => { copied = value; } } },
};
runInNewContext(source, context, { filename: fileURLToPath(new URL('../../js/admin.js', import.meta.url)) });

await form.listeners.submit({ preventDefault() {} });
if (calls[0][0] !== '/api/admin/google-oauth' || calls[0][1].method !== 'PUT' || JSON.parse(calls[0][1].body).clientSecret !== 'new-secret') throw new Error('Google-OAuth-Adminformular speichert nicht über den geschützten API-Pfad.');
if (secret.value !== '' || remove.disabled || !status.textContent.includes('configured')) throw new Error('Google-OAuth-Adminformular behält das Secret oder aktualisiert den Status nicht.');
await copy.listeners.click();
if (copied !== redirect.value) throw new Error('Google-Redirect-URI kann nicht kopiert werden.');
await remove.listeners.click();
if (calls[1][1].method !== 'DELETE' || !remove.disabled || clientId.value !== '') throw new Error('Google-OAuth-Konfiguration kann nicht sicher entfernt werden.');
await calDavForm.listeners.submit({ preventDefault() {} });
const calDavCall = calls[2];
if (calDavCall[0] !== '/api/admin/external-calendars/caldav/test' || calDavCall[1].method !== 'POST' || JSON.parse(calDavCall[1].body).password !== 'connection-secret') throw new Error('Administrativer Kopano-Test verwendet nicht den geschützten API-Pfad.');
if (calDavPassword.value !== '' || calDavSubmit.disabled || !calDavStatus.textContent.includes('erfolgreich geprüft')) throw new Error('Administrativer Kopano-Test behält das Passwort oder zeigt kein Ergebnis.');
failCalDav = true; calDavPassword.value = 'retry-secret';
await calDavForm.listeners.submit({ preventDefault() {} });
if (calDavPassword.value !== '' || calDavSubmit.disabled || !calDavStatus.textContent.includes('Kopano-Betreiber')) throw new Error('Fehlgeschlagener Kopano-Test räumt das Passwort nicht auf oder verschweigt die Providerdiagnose.');

await defaultsForm.listeners.submit({ preventDefault() {} });
const defaultsCall = calls[4];
if (defaultsCall[0] !== '/api/admin/calendar-defaults' || defaultsCall[1].method !== 'PUT'
    || JSON.parse(defaultsCall[1].body).calendarName !== 'Team & Dienst') {
    throw new Error('Kalenderdefaults werden nicht über den geschützten Adminpfad gespeichert.');
}
if (defaultsUrl.value !== 'https://calendar.example.test/caldav/' || defaultsName.value !== 'Team & Dienst'
    || defaultsSubmit.disabled || !defaultsStatus.textContent.includes('saved')) {
    throw new Error('Kalenderdefaults aktualisieren Adminformular und Status nicht sicher.');
}
if (!translationCalls.some(([key]) => key === 'The calendar defaults were saved. Existing app-owned calendars will be renamed during the next synchronisation.')) {
    throw new Error('Adminerfolg wird nicht als stabiler englischer L10N-Schlüssel delegiert.');
}

console.log('Admin settings smoke: OK');
