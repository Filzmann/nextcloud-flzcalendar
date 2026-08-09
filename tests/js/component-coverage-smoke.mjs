import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const load = (relativePath, context) => runInNewContext(
    readFileSync(new URL(relativePath, import.meta.url), 'utf8'),
    context,
    { filename: fileURLToPath(new URL(relativePath, import.meta.url)) },
);

class FakeNode {
    constructor(tag = 'div') {
        this.tagName = tag.toUpperCase();
        this.children = [];
        this.dataset = {};
        this.listeners = {};
        this.value = '';
        this.textContent = '';
        this.checked = false;
        this.disabled = false;
        this.hidden = false;
        this.valid = true;
        this.open = false;
        this.className = '';
        this.attributes = {};
    }

    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = children; }
    addEventListener(type, listener) { (this.listeners[type] ||= []).push(listener); }
    setAttribute(name, value) { this.attributes[name] = value; }
    showModal() { this.open = true; }
    close() { this.open = false; }
    focus() { this.focused = true; }
    remove() { this.removed = true; }
    reportValidity() { this.reported = true; return this.valid; }
    setCustomValidity(message) { this.validationMessage = message; this.valid = message === ''; }
    async fire(type, event = {}) {
        const payload = { preventDefault() {}, target: this, ...event };
        for (const listener of this.listeners[type] || []) await listener(payload);
    }
    querySelectorAll(selector) {
        const all = this.descendants();
        if (selector === '[data-weekday]') return all.filter(node => node.dataset.weekday !== undefined);
        return [];
    }
    querySelector(selector) {
        const match = selector.match(/^\[data-field="([^"]+)"\]$/);
        return match ? this.descendants().find(node => node.dataset.field === match[1]) || null : null;
    }
    descendants() { return this.children.flatMap(child => child instanceof FakeNode ? [child, ...child.descendants()] : []); }
}

const translated = (message, parameters = {}) => Object.entries(parameters)
    .reduce((text, [key, value]) => text.replaceAll(key.startsWith('{') ? key : `{${key}}`, String(value)), message);
const l10n = {
    locale: 'en-GB',
    t: translated,
    n: (singular, plural, count, parameters = {}) => translated(count === 1 ? singular : plural, parameters),
    lower: value => String(value).toLocaleLowerCase('en-GB'),
    date: date => new Date(date).toISOString().slice(0, 10),
    time: date => new Date(date).toISOString().slice(11, 16),
};
const documentFor = elements => ({
    body: new FakeNode('body'),
    createElement: tag => new FakeNode(tag),
    createTextNode: value => Object.assign(new FakeNode('#text'), { textContent: value }),
    getElementById: id => elements[id],
});

// CalendarRepository: every public adapter path, method and payload is executed.
class BaseRepository {
    constructor(client) { this.client = client; this.calls = []; }
    encode(value) { return encodeURIComponent(String(value)); }
    request(path, options = {}) { this.calls.push([path, options]); return Promise.resolve({ path, options }); }
}
const repositoryContext = { window: { LocalBase: { repositories: { Repository: BaseRepository } }, AdCalendar: {} }, JSON, Promise, encodeURIComponent };
load('../../js/repositories/calendar-repository.js', repositoryContext);
const repository = new repositoryContext.window.AdCalendar.repositories.CalendarRepository({});
await repository.week('2026-07-01');
await repository.range('2026-07-01', '2026-08-01');
await repository.save({ title: 'A' });
await repository.save({ title: 'B' }, 'id/1', 'series');
await repository.remove('id/1', 'detach', 'series');
await repository.savePreferences({ roles: ['role-a'] });
await repository.saveShiftDefaults({ 1: { start: '08:00' } });
await repository.saveCalendarSync(false);
await repository.externalCalendars();
await repository.connectCalDav('manual', 'https://calendar.example.test/', 'person-a', 'secret');
await repository.disconnectExternalCalendar('manual/provider');
await repository.startGoogleCalendarConnection();
await repository.meetingGaps('2026-07-06', ['a', 'b'], 45);
await repository.blockMeeting('start', 'end', ['a', 'b'], 'Planning');
await repository.updateMeeting('meeting/id', 'start-2', 'end-2', 'Updated');
await repository.removeMeeting('meeting/id');
if (repository.calls.length !== 16
    || repository.calls[3][0] !== '/api/entries/id%2F1'
    || repository.calls[3][1].method !== 'PUT'
    || JSON.parse(repository.calls[3][1].body).seriesScope !== 'series'
    || repository.calls[10][0] !== '/api/external-calendars/manual%2Fprovider'
    || repository.calls[15][0] !== '/api/meetings/meeting%2Fid') {
    throw new Error('CalendarRepository bildet Pfade, Methoden oder Payloads nicht vollständig stabil ab.');
}

// CalendarEntry: server payload hydration and stable serialisation defaults.
class BaseModel {}
const modelContext = { window: { LocalBase: { models: { Model: BaseModel } }, AdCalendar: {} }, Number, String, Boolean };
load('../../js/models/calendar-entry.js', modelContext);
const CalendarEntry = modelContext.window.AdCalendar.models.CalendarEntry;
const hydratedEntry = new CalendarEntry({
    id: '7', employeeUid: 42, start: null, end: 'end', type: 'appointment', title: 9,
    parentEntryId: '3', meetingUid: 5, seriesUid: 6, seriesTimezone: 7,
    canManageMeeting: false, isBlocked: 1, defaultDate: 20260706, defaultModified: 1, defaultDeleted: 0,
});
const serializedEntry = hydratedEntry.toArray();
if (serializedEntry.id !== 7 || serializedEntry.employeeUid !== '42' || serializedEntry.start !== ''
    || serializedEntry.parentEntryId !== 3 || serializedEntry.meetingUid !== '5'
    || hydratedEntry.canManageMeeting !== false || serializedEntry.defaultDate !== '20260706'
    || serializedEntry.defaultModified !== true || serializedEntry.defaultDeleted !== false) {
    throw new Error('CalendarEntry hydriert oder serialisiert den API-Vertrag nicht typstabil.');
}
const emptyEntry = new CalendarEntry();
if (emptyEntry.id !== null || emptyEntry.parentEntryId !== null || emptyEntry.meetingUid !== null || emptyEntry.canManageMeeting !== true) {
    throw new Error('CalendarEntry besitzt keine stabilen leeren Standardwerte.');
}

// ShiftDefaults: rendering, enable/disable behavior, collection and form validation.
const shiftElements = {
    'adc-shift-defaults': new FakeNode(),
    'adc-shift-defaults-form': new FakeNode('form'),
};
const shiftContext = { window: { AdCalendar: { l10n } }, document: documentFor(shiftElements), Array, Object };
load('../../js/components/shift-defaults.js', shiftContext);
const savedDefaults = [];
const shiftDefaults = new shiftContext.window.AdCalendar.components.ShiftDefaults({ onSave: async value => savedDefaults.push(value) });
shiftDefaults.set({ 1: { enabled: false, start: '09:00', end: '17:30' }, 2: { start: '07:30', end: '15:00' } });
const rows = shiftElements['adc-shift-defaults'].querySelectorAll('[data-weekday]');
const mondayEnabled = rows[0].querySelector('[data-field="enabled"]');
if (rows.length !== 7 || mondayEnabled.checked || !rows[0].querySelector('[data-field="start"]').disabled) {
    throw new Error('Standarddienstzeilen übernehmen deaktivierte Tageswerte nicht.');
}
mondayEnabled.checked = true;
await mondayEnabled.fire('change');
if (rows[0].querySelector('[data-field="start"]').disabled) throw new Error('Aktivierter Standarddienst bleibt gesperrt.');
shiftElements['adc-shift-defaults-form'].valid = false;
await shiftElements['adc-shift-defaults-form'].fire('submit');
shiftElements['adc-shift-defaults-form'].valid = true;
await shiftElements['adc-shift-defaults-form'].fire('submit');
if (savedDefaults.length !== 1 || savedDefaults[0]['1'].start !== '09:00' || savedDefaults[0]['2'].end !== '15:00') {
    throw new Error('Standarddienstformular validiert oder sammelt Tageswerte nicht korrekt.');
}

// CalendarFilters: ordered facets, leadership switch, search selection and reset.
const filterElements = Object.fromEntries([
    'adc-role-filters', 'adc-area-filters', 'adc-person-search', 'adc-search-results', 'adc-selected-people', 'adc-reset-selection',
].map(id => [id, new FakeNode()]));
const filterState = {
    data: { employees: [
        { uid: 'a', displayName: 'Alpha', roles: ['role-b'], areas: ['area-b'] },
        { uid: 'b', displayName: 'Beta', roles: ['role-a', 'staff'], areas: ['area-a'] },
    ] },
    roles: new Set(['staff']), areas: new Set(), selected: new Set(),
    showLeadershipStaff: true, leadershipStaffOnly: true, persisted: 0,
    isUnfiltered: () => false,
    persist() { this.persisted += 1; },
};
const organization = {
    staffBlockLabel: 'Leadership',
    roleOrder: value => ({ 'role-a': 1, 'role-b': 2 }[value] ?? 99),
    areaOrder: value => ({ 'area-a': 1, 'area-b': 2 }[value] ?? 99),
    roleLabel: value => value.toUpperCase(), areaLabel: value => value.toUpperCase(),
};
let filterChanges = 0;
const filterContext = { window: { AdCalendar: { l10n } }, document: documentFor(filterElements), Set };
load('../../js/components/calendar-filters.js', filterContext);
const filters = new filterContext.window.AdCalendar.components.CalendarFilters({
    state: filterState, organization: () => organization, leadershipStaffRoles: new Set(['staff']), onChange: () => { filterChanges += 1; },
});
filters.render();
if (filterElements['adc-role-filters'].children.length !== 3
    || filterElements['adc-role-filters'].children[0].children[1].textContent !== ' ROLE-A') {
    throw new Error('Rollenfilter werden nicht ohne Stabsrollen in Organisationsreihenfolge gerendert.');
}
const leadershipInput = filterElements['adc-role-filters'].children[2].children[0];
leadershipInput.checked = false;
await leadershipInput.fire('change');
if (filterState.roles.has('staff') || filterState.leadershipStaffOnly || filterState.showLeadershipStaff) {
    throw new Error('Ausblenden des Leitungs-/Stabsblocks bereinigt den Filterzustand nicht.');
}
filterElements['adc-person-search'].value = 'alp';
await filterElements['adc-person-search'].fire('input');
const selectAlpha = filterElements['adc-search-results'].children[0].children[0];
await selectAlpha.fire('click');
if (!filterState.selected.has('a') || filterElements['adc-person-search'].value !== '') throw new Error('Personensuche übernimmt die Auswahl nicht.');
const removeAlpha = filterElements['adc-selected-people'].children[0].children[0];
await removeAlpha.fire('click');
filterState.selected.add('b');
await filterElements['adc-reset-selection'].fire('click');
if (filterState.selected.size !== 0 || filterState.persisted < 4 || filterChanges < 4) throw new Error('Filteränderungen werden nicht persistiert und gemeldet.');

// MeetingFinder: validation, search, permission note, week advance, deselection and blocking.
const meetingIds = [
    'adc-meeting-dialog', 'adc-meeting-form', 'adc-meeting-search', 'adc-meeting-people', 'adc-meeting-duration',
    'adc-meeting-title', 'adc-meeting-results', 'adc-meeting-week', 'adc-meeting-close', 'adc-meeting-cancel',
];
const meetingElements = Object.fromEntries(meetingIds.map(id => [id, new FakeNode(id.includes('form') ? 'form' : 'div')]));
const gapCalls = []; const blockCalls = []; const meetingErrors = [];
let gapResponse = { gaps: [], canBlockAll: false };
const meetingRepository = {
    async meetingGaps(...args) { gapCalls.push(args); if (gapResponse instanceof Error) throw gapResponse; return gapResponse; },
    async blockMeeting(...args) { blockCalls.push(args); if (args[3] === 'fail') throw new Error('block failed'); },
};
const calendarDate = { isoDay: date => new Date(date).toISOString().slice(0, 10) };
const meetingDocument = documentFor(meetingElements);
const meetingOpener = Object.assign(new FakeNode('button'), { isConnected: true });
meetingDocument.activeElement = meetingOpener;
const meetingContext = { window: { AdCalendar: { l10n, modules: { CalendarDate: calendarDate } } }, document: meetingDocument, Date, Number, Set };
load('../../js/components/meeting-finder.js', meetingContext);
let blocked = 0;
const finder = new meetingContext.window.AdCalendar.components.MeetingFinder({
    repository: meetingRepository, onError: error => meetingErrors.push(error.message), onBlocked: async () => { blocked += 1; },
});
const employees = [{ uid: 'a', displayName: 'Alpha' }, { uid: 'b', displayName: 'Beta' }, { uid: 'c', displayName: 'Gamma' }];
finder.open('2026-07-06', employees, ['a']);
if (!meetingElements['adc-meeting-dialog'].open || !meetingElements['adc-meeting-search'].focused || finder.selected.size !== 1) {
    throw new Error('Meetingdialog initialisiert Auswahl, Fokus oder Modalzustand nicht.');
}
await finder.searchWeek();
if (!meetingElements['adc-meeting-results'].textContent.includes('at least two')) throw new Error('Meeting-Suche akzeptiert zu wenige Personen.');
finder.selected.add('b'); meetingElements['adc-meeting-duration'].value = '10';
await finder.searchWeek();
if (!meetingElements['adc-meeting-results'].textContent.includes('15 and 480')) throw new Error('Meeting-Suche akzeptiert eine ungültige Dauer.');
meetingElements['adc-meeting-duration'].value = '30';
gapResponse = { gaps: [{ start: '2026-07-06T10:00:00Z' }], canBlockAll: true };
await finder.searchWeek();
const blockButton = meetingElements['adc-meeting-results'].children[1].children[0].children[1];
await blockButton.fire('click');
if (!meetingElements['adc-meeting-title'].reported) throw new Error('Leerer Meetingtitel wird nicht validiert.');
meetingElements['adc-meeting-title'].value = 'Planning';
await blockButton.fire('click');
if (blockCalls.length !== 1 || blocked !== 1 || !meetingElements['adc-meeting-results'].textContent.includes('blocked')) {
    throw new Error('Gemeinsame Meetingblockierung wird nicht vollständig ausgeführt.');
}
gapResponse = { gaps: [{ start: '2026-07-06T11:00:00Z' }], canBlockAll: false };
await finder.searchWeek();
if (meetingElements['adc-meeting-results'].children.length !== 3) throw new Error('Fehlendes gemeinsames Bearbeitungsrecht wird nicht erklärt.');
gapResponse = { gaps: [], canBlockAll: false };
await finder.searchWeek();
const actions = meetingElements['adc-meeting-results'].children[1];
await actions.children[0].fire('click');
if (finder.start !== '2026-07-13') throw new Error('Weitersuche springt nicht exakt eine Kalenderwoche.');
await actions.children[1].fire('click');
if (finder.selected.has('a')) throw new Error('Person kann aus der erfolglosen Meeting-Suche nicht abgewählt werden.');
finder.selected.add('a');
gapResponse = new Error('gap failed');
await finder.searchWeek();
meetingElements['adc-meeting-title'].value = 'fail';
await finder.block(new Date('2026-07-06T10:00:00Z'), new Date('2026-07-06T10:30:00Z'), blockButton);
if (!meetingErrors.includes('gap failed') || !meetingErrors.includes('block failed') || blockButton.disabled) {
    throw new Error('Meeting-Suche oder -Blockierung meldet Providerfehler nicht bedienbar zurück.');
}
await meetingElements['adc-meeting-close'].fire('click');
if (meetingElements['adc-meeting-dialog'].open || !meetingOpener.focused) throw new Error('Meetingdialog schließt nicht oder gibt den Fokus nicht an den Auslöser zurück.');

// EntryWorkflow: save/delete variants, meeting permissions, delegated clicks and choice dialogs.
const workflowDocument = documentFor({});
const workflowContext = {
    window: { AdCalendar: { l10n }, confirm: () => true },
    document: workflowDocument,
    Element: FakeNode,
    Date, Number, Promise, Error,
};
load('../../js/modules/entry-workflow.js', workflowContext);
const workflowCalls = [];
let workflowFailure = null;
const workflowRepository = {
    async save(payload, id, scope) { workflowCalls.push(['save', payload, id, scope]); if (workflowFailure) throw workflowFailure; return { seriesCount: 3 }; },
    async updateMeeting(...args) { workflowCalls.push(['updateMeeting', ...args]); if (workflowFailure) throw workflowFailure; },
    async remove(...args) {
        workflowCalls.push(['remove', ...args]);
        if (workflowFailure) throw workflowFailure;
    },
    async removeMeeting(...args) { workflowCalls.push(['removeMeeting', ...args]); if (workflowFailure) throw workflowFailure; },
};
const workflowState = { data: { employees: [{ uid: 'a', canManage: true }], entries: [] } };
const workflowDialog = { opened: [], closed: 0, open(value) { this.opened.push(value); }, close() { this.closed += 1; } };
const workflowMessages = []; let workflowReloads = 0;
const workflowBody = new FakeNode();
const workflow = new workflowContext.window.AdCalendar.modules.EntryWorkflow({
    repository: workflowRepository, state: workflowState, dialog: workflowDialog, body: workflowBody,
    show: (message, error = false) => workflowMessages.push([message, error]), reload: async () => { workflowReloads += 1; },
});
const entryPayload = {
    id: null, employeeUid: 'a', type: 'appointment', start: '2026-07-06T10:00:00Z', end: '2026-07-06T11:00:00Z', title: 'Planning',
    recurrenceFrequency: 'weekly', recurrenceInterval: 1, recurrenceUntil: '2026-08-01', recurrenceWeekdays: [1], recurrenceTimezone: 'Europe/Berlin',
};
await workflow.save(entryPayload);
if (workflowCalls[0][0] !== 'save' || !String(workflowMessages[0][0]).includes('3 recurring') || workflowDialog.closed !== 1) {
    throw new Error('Eintragsworkflow speichert neue Terminserien nicht vollständig.');
}
workflowState.data.entries = [{ id: 8, meetingUid: 'meeting-1', canManageMeeting: false }];
await workflow.save({ ...entryPayload, id: 8 });
if (!workflowMessages.at(-1)[1]) throw new Error('Nicht gemeinsam bearbeitbares Meeting wird beim Speichern nicht abgewiesen.');
workflowState.data.entries[0].canManageMeeting = true;
await workflow.save({ ...entryPayload, id: 8 });
if (workflowCalls.at(-1)[0] !== 'updateMeeting') throw new Error('Gemeinsames Meeting wird nicht über den atomaren Endpunkt geändert.');
workflowState.data.entries = [{ id: 9, seriesUid: 'series-1' }];
workflow.seriesChoice = async () => 'series';
await workflow.save({ ...entryPayload, id: 9 });
if (workflowCalls.at(-1)[3] !== 'series') throw new Error('Serienbearbeitung reicht den gewählten Scope nicht weiter.');
workflow.seriesChoice = async () => null;
const callsBeforeCancelledSave = workflowCalls.length;
await workflow.save({ ...entryPayload, id: 9 });
if (workflowCalls.length !== callsBeforeCancelledSave) throw new Error('Abgebrochene Serienbearbeitung persistiert trotzdem.');

workflowFailure = null;
await workflow.remove({ id: 10, type: 'shift', meetingUid: null, seriesUid: null });
if (workflowCalls.at(-1)[0] !== 'remove' || !String(workflowMessages.at(-1)[0]).includes('Shift deleted')) {
    throw new Error('Einfacher Dienst wird nicht direkt gelöscht.');
}
workflowFailure = Object.assign(new Error('confirmation'), { status: 409, data: { confirmationRequired: true, children: [1, 2] } });
workflow.deletionChoice = async count => count === 2 ? 'detach' : null;
const originalRemove = workflowRepository.remove;
let firstConflict = true;
workflowRepository.remove = async (...args) => {
    workflowCalls.push(['remove', ...args]);
    if (firstConflict) { firstConflict = false; throw workflowFailure; }
};
await workflow.remove({ id: 11, type: 'shift', meetingUid: null, seriesUid: null });
if (workflowCalls.at(-1)[2] !== 'detach') throw new Error('Diensttermine werden nach Bestätigung nicht als Sperrzeiten erhalten.');
workflowRepository.remove = originalRemove;
workflowFailure = Object.assign(new Error('delete failed'), { status: 500 });
await workflow.remove({ id: 12, type: 'shift', meetingUid: null, seriesUid: null });
if (!workflowMessages.at(-1)[1]) throw new Error('Nicht bestätigbarer Dienstlöschfehler wird nicht angezeigt.');
workflowFailure = null;
workflowContext.window.confirm = () => false;
const callsBeforeCancelledDelete = workflowCalls.length;
await workflow.remove({ id: 13, type: 'appointment', meetingUid: null, seriesUid: null });
if (workflowCalls.length !== callsBeforeCancelledDelete) throw new Error('Abgebrochene Terminlöschung persistiert trotzdem.');
workflowContext.window.confirm = () => true;
await workflow.remove({ id: 13, type: 'appointment', meetingUid: null, seriesUid: null });
workflow.seriesChoice = async () => 'series';
await workflow.remove({ id: 14, type: 'appointment', meetingUid: null, seriesUid: 'series-1' });
if (!String(workflowMessages.at(-1)[0]).includes('Recurring appointment deleted')) throw new Error('Vollständige Serie meldet keinen passenden Löschstatus.');
await workflow.removeMeeting({ meetingUid: 'm-denied', canManageMeeting: false });
workflowContext.window.confirm = () => false;
await workflow.removeMeeting({ meetingUid: 'm-cancel', canManageMeeting: true });
workflowContext.window.confirm = () => true;
await workflow.removeMeeting({ meetingUid: 'm-ok', canManageMeeting: true });
workflowFailure = new Error('meeting delete failed');
await workflow.removeMeeting({ meetingUid: 'm-fail', canManageMeeting: true });
if (!workflowMessages.at(-1)[1] || workflowCalls.at(-1)[0] !== 'removeMeeting') throw new Error('Meetinglöschfehler wird nicht angezeigt.');
workflowFailure = null;

const cell = Object.assign(new FakeNode('td'), { dataset: { employeeUid: 'a', day: '2026-07-06' } });
const addButton = Object.assign(new FakeNode('button'), {
    dataset: { action: 'add-entry', entryType: 'shift' },
    closest(selector) { return selector === 'button[data-action]' ? this : cell; },
});
await workflowBody.fire('click', { target: addButton });
if (workflowDialog.opened.at(-1).type !== 'shift') throw new Error('Delegierte Schnellaktion öffnet keinen Dienstdialog.');
workflowState.data.entries = [{ id: 20, type: 'appointment', start: '2026-07-06T10:00:00Z' }];
const editButton = Object.assign(new FakeNode('button'), {
    dataset: { action: 'edit-entry', entryId: '20' },
    closest(selector) { return selector === 'button[data-action]' ? this : cell; },
});
await workflowBody.fire('click', { target: editButton });
if (workflowDialog.opened.at(-1).entry.id !== 20) throw new Error('Delegierte Bearbeitung findet den Eintrag nicht.');

delete workflow.deletionChoice;
const workflowOpener = Object.assign(new FakeNode('button'), { isConnected: true });
workflowDocument.activeElement = workflowOpener;
const deletionPromise = workflow.deletionChoice(2);
const deletionDialog = workflowDocument.body.children.at(-1);
await deletionDialog.children[3].fire('click');
if (await deletionPromise !== 'detach' || !deletionDialog.removed || !workflowOpener.focused) throw new Error('Dienstlöschdialog liefert die Wahl nicht zurück oder verliert den Fokusauslöser.');
delete workflow.seriesChoice;
const seriesPromise = workflow.seriesChoice('edit');
const seriesDialog = workflowDocument.body.children.at(-1);
await seriesDialog.children[3].fire('click');
if (await seriesPromise !== 'series' || !seriesDialog.removed) throw new Error('Seriendialog liefert den gewählten Scope nicht zurück.');
if (workflowReloads < 5) throw new Error('Erfolgreiche Mutationen laden den Kalender nicht erneut.');

console.log('Component coverage smoke: OK');
