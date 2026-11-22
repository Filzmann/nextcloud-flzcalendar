import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import vm from 'node:vm';

const appRoot = resolve(import.meta.dirname, '../..');
const requests = [];
const noticeElement = { className: '', hidden: true, textContent: '' };
const window = {
    fetch: async (url, options) => {
        requests.push({ url, options });
        return { ok: true, status: 200, text: async () => '{"entries":[]}' };
    },
};
window.window = window;
window.OC = {
    generateUrl: path => `/index.php${path}`,
    requestToken: 'calendar-token',
};
window.FlzCalendar = {
    l10n: {
        t: (message, replacements = {}) => Object.entries(replacements).reduce((text, [key, value]) => text.replace(`{${key}}`, value), message),
        time: value => value.toISOString().slice(11, 16),
    },
};

const context = vm.createContext({
    Boolean,
    document: { getElementById: id => id === 'notice' ? noticeElement : null },
    encodeURIComponent,
    Error,
    JSON,
    Number,
    Object,
    OC: window.OC,
    Set,
    String,
    window,
});

for (const relative of [
    'js/shared/api-client.js',
    'js/shared/model.js',
    'js/shared/repository.js',
    'js/shared/ui.js',
    'js/models/calendar-entry.js',
    'js/models/organization.js',
    'js/repositories/calendar-repository.js',
    'js/components/calendar-cell.js',
]) {
    const filename = resolve(appRoot, relative);
    vm.runInContext(readFileSync(filename, 'utf8'), context, { filename });
}

const shared = window.OCA?.FlzCalendar?.Shared;
if (!shared?.api?.ApiClient || !shared?.models?.Model || !shared?.repositories?.Repository || !shared?.ui?.Notice) {
    throw new Error('Das app-lokale Kategorie-A-Bundle ist nicht vollständig unter OCA.FlzCalendar.Shared verfügbar.');
}
if ('LocalBase' in window) {
    throw new Error('Der Kalender erzeugt weiterhin das private window.LocalBase-Global.');
}

const client = new shared.api.ApiClient({ appId: 'flzcalendar' });
const repository = new window.FlzCalendar.repositories.CalendarRepository(client);
await repository.week('2026-11-02');
if (requests.length !== 1
    || requests[0].url !== '/index.php/apps/flzcalendar/api/week?start=2026-11-02'
    || requests[0].options.headers.requesttoken !== 'calendar-token') {
    throw new Error('ApiClient und CalendarRepository bewahren URL- und CSRF-Vertrag nicht gemeinsam.');
}

const entry = window.FlzCalendar.models.CalendarEntry.get({ id: 7, employeeUid: 'person-a', type: 'shift' });
if (entry.toArray().employeeUid !== 'person-a' || entry.to_array().id !== 7) {
    throw new Error('Das Kalender-Modell bewahrt Hydration und Serialisierung mit dem gebündelten Model nicht.');
}

const notice = new shared.ui.Notice('notice', { baseClass: 'notice', typeClassPrefix: 'notice--' });
notice.success('Bereit');
if (noticeElement.textContent !== 'Bereit' || noticeElement.className !== 'notice notice--success') {
    throw new Error('Die app-lokale Notice bewahrt ihren sichtbaren Erfolgszustand nicht.');
}

const cell = new window.FlzCalendar.components.CalendarCell();
const rendered = cell.render([{
    id: 9,
    type: 'appointment',
    start: '2026-11-02T10:00:00Z',
    end: '2026-11-02T11:00:00Z',
    title: '<script>alert(1)</script>',
    parentEntryId: null,
}], { canManage: true }, []);
if (!rendered.includes('data-action="add-entry"')
    || !rendered.includes('&lt;script&gt;alert(1)&lt;/script&gt;')
    || rendered.includes('<script>')) {
    throw new Error('CalendarCell arbeitet nicht unverändert mit dem app-lokalen Escaping-Primitive.');
}

console.log('Shared browser consumer contract: OK');
