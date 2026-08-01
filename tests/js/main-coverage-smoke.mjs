import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

class FakeElement {
    constructor() {
        this.listeners = {};
        this.textContent = '';
        this.disabled = false;
        this.title = '';
    }
    addEventListener(type, listener) { (this.listeners[type] ||= []).push(listener); }
    async fire(type) { for (const listener of this.listeners[type] || []) await listener({ preventDefault() {}, target: this }); }
}

const elements = new Map();
const document = { getElementById(id) { if (!elements.has(id)) elements.set(id, new FakeElement()); return elements.get(id); } };
const calls = [];
const data = () => ({
    entries: [{ id: 1, employeeUid: 'a', type: 'shift' }],
    employees: [{ uid: 'a', displayName: 'Alpha', canManage: true }],
    shiftDefaults: { 1: { enabled: true, start: '08:00', end: '16:00' } },
    calendarSync: { enabled: true, calendarName: 'Team calendar' },
    holidayCalendars: [{ year: 2026, publicHolidays: [] }],
    organization: { staffRoleGroups: ['staff-a'] },
});

class ApiClient {
    static last;
    constructor(options) { this.options = options; ApiClient.last = this; }
}
class CalendarRepository {
    static last;
    constructor(client) { this.client = client; this.fail = null; CalendarRepository.last = this; }
    async week(start) {
        calls.push(['week', start]);
        if (this.fail) throw this.fail;
        if (this.deferWeek) return new Promise(resolve => { this.releaseWeek = () => resolve(data()); });
        return data();
    }
    async range(start, end) { calls.push(['range', start, end]); if (this.fail) throw this.fail; return data(); }
    async savePreferences(preference) { calls.push(['savePreferences', preference]); if (this.fail) throw this.fail; return {}; }
    async saveShiftDefaults(defaults) { calls.push(['saveShiftDefaults', defaults]); if (this.fail) throw this.fail; return { shiftDefaults: defaults }; }
    async saveCalendarSync(enabled) { calls.push(['saveCalendarSync', enabled]); if (this.fail) throw this.fail; return { calendarSync: { enabled, calendarName: 'Team calendar' } }; }
}
class Notice {
    static last;
    constructor(id, options) { this.id = id; this.options = options; this.messages = []; Notice.last = this; }
    success(message) { this.messages.push(['success', message]); }
    error(message) { this.messages.push(['error', message]); }
    clear() { this.messages.push(['clear']); }
}
class CalendarEntry {
    static get_all(entries) { calls.push(['hydrate', entries.length]); return entries.map(entry => ({ ...entry, hydrated: true })); }
}
class Organization {
    static get(value) { calls.push(['organization', value]); return new Organization(value); }
    constructor(value) { this.value = value; }
    staffRoleGroups() { return this.value.staffRoleGroups || []; }
}
class CalendarState {
    static last;
    constructor(leadershipRoles) {
        this.leadershipRoles = leadershipRoles;
        this.period = 'week'; this.activeTab = 'calendar'; this.selected = new Set();
        this.monday = new Date('2026-07-06T12:00:00Z'); this.month = new Date('2026-07-01T12:00:00Z');
        this.data = null; this.persisted = 0; CalendarState.last = this;
    }
    restore() { calls.push(['restore']); return this; }
    persist() { this.persisted += 1; }
    visibleRange() {
        const start = new Date(this.period === 'month' ? this.month : this.monday);
        const end = new Date(start);
        if (this.period === 'month') end.setMonth(end.getMonth() + 1);
        else end.setDate(end.getDate() + 7);
        return { start, end };
    }
    applyInitialFilters() { calls.push(['applyInitialFilters']); }
    availableEmployees() { return this.data?.employees || []; }
    isUnfiltered() { return this.forceFiltered !== true; }
    toPreference() { return { period: this.period, selected: [...this.selected] }; }
}
class MeetingCapabilities { apply(entries, employees) { calls.push(['capabilities', entries.length, employees.length]); } }

class TabNavigation {
    static last;
    constructor(options) { this.options = options; this.shown = []; TabNavigation.last = this; }
    show(...args) { this.shown.push(args); }
}
class CalendarCell {}
class WeekTable {
    static last;
    constructor(options) { this.options = options; this.renders = []; this.holidays = []; WeekTable.last = this; }
    render(employees, state) { this.renders.push([employees, state.period, state.visibleRange().start.toISOString().slice(0, 10)]); }
    setHolidays(value) { this.holidays.push(value); }
}
class EntryDialog {
    static last;
    constructor(options) { this.options = options; this.employeeSets = []; EntryDialog.last = this; }
    setEmployees(value) { this.employeeSets.push(value); }
}
class MeetingFinder {
    static last;
    constructor(options) { this.options = options; this.opens = []; MeetingFinder.last = this; }
    open(...args) { this.opens.push(args); }
}
class ShiftDefaults {
    static last;
    constructor(options) { this.options = options; this.values = []; ShiftDefaults.last = this; }
    set(value) { this.values.push(value); }
}
class ShiftCalendarSync {
    static last;
    constructor(options) { this.options = options; this.values = []; ShiftCalendarSync.last = this; }
    set(value) { this.values.push(value); }
}
class ExternalCalendars {
    static last;
    constructor(options) { this.options = options; this.loads = 0; ExternalCalendars.last = this; }
    load() { this.loads += 1; return Promise.resolve(); }
}
class CalendarFilters {
    static last;
    constructor(options) { this.options = options; this.renders = 0; CalendarFilters.last = this; }
    render() { this.renders += 1; }
}
class WeekNavigation {
    static last;
    constructor(options) { this.options = options; this.renders = 0; WeekNavigation.last = this; }
    render() { this.renders += 1; }
}
class EntryWorkflow {
    static last;
    constructor(options) { this.options = options; EntryWorkflow.last = this; }
    async save(value) { calls.push(['workflowSave', value]); }
}

const l10n = {
    t: (message, parameters = {}) => Object.entries(parameters).reduce((text, [key, value]) => text.replaceAll(`{${key}}`, String(value)), message),
    n: (singular, plural, count, parameters = {}) => Object.entries(parameters).reduce((text, [key, value]) => text.replaceAll(`{${key}}`, String(value)), count === 1 ? singular : plural),
};
const context = {
    window: {
        location: { search: '?calendarConnection=google-connected' },
        LocalBase: { api: { ApiClient }, ui: { Notice } },
        AdCalendar: {
            l10n,
            repositories: { CalendarRepository }, models: { CalendarEntry, Organization },
            modules: {
                CalendarDate: { isoDay: value => new Date(value).toISOString().slice(0, 10) },
                CalendarState, MeetingCapabilities, EntryWorkflow,
            },
            components: {
                TabNavigation, CalendarCell, WeekTable, EntryDialog, MeetingFinder, ShiftDefaults,
                ShiftCalendarSync, ExternalCalendars, CalendarFilters, WeekNavigation,
            },
        },
    },
    document, URLSearchParams, Set, Date, Promise, Error, Object,
};
runInNewContext(readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8'), context, {
    filename: fileURLToPath(new URL('../../js/main.js', import.meta.url)),
});
const settle = () => new Promise(resolve => setImmediate(resolve));
await settle();

const state = CalendarState.last;
const repository = CalendarRepository.last;
if (!calls.some(call => call[0] === 'week') || !state.data?.entries[0].hydrated
    || WeekTable.last.renders.length !== 1 || CalendarFilters.last.renders !== 1
    || ShiftDefaults.last.values.length !== 1 || ShiftCalendarSync.last.values.length !== 1
    || ExternalCalendars.last.loads !== 1 || !Notice.last.messages.some(item => item[1] === 'Google Calendar was connected.')) {
    throw new Error('Initiale App-Orchestrierung lädt, hydriert oder rendert den Wochenzustand nicht vollständig.');
}
if (ApiClient.last.options.errorMessage({ error: 'safe error' }, 400) !== 'safe error'
    || ApiClient.last.options.errorMessage({ message: 'message' }, 400) !== 'message'
    || ApiClient.last.options.errorMessage({}, 503) !== 'HTTP 503') {
    throw new Error('API-Fehleradapter besitzt keinen stabilen öffentlichen Fallback.');
}

await elements.get('adc-open-meeting-finder').fire('click');
if (MeetingFinder.last.opens[0][0] !== '2026-07-06' || MeetingFinder.last.opens[0][1].length !== 1) {
    throw new Error('Meeting-Suche erhält nicht Woche, Mitarbeitende und Auswahl aus dem Zustand.');
}
state.selected.add('a');
CalendarFilters.last.options.onChange();
if (!elements.get('adc-filter-status').textContent.includes('1 selected')) throw new Error('Explizite Auswahl wird nicht im Filterstatus gerendert.');
state.selected.clear(); state.forceFiltered = true;
CalendarFilters.last.options.onChange();
if (!elements.get('adc-filter-status').textContent.includes('1 filtered')) throw new Error('Gruppenfilter wird nicht im Filterstatus gerendert.');
WeekNavigation.last.options.onViewChange();

repository.deferWeek = true;
state.monday.setDate(state.monday.getDate() + 7);
const pendingWeekLoad = WeekNavigation.last.options.onWeekChange();
if (WeekTable.last.renders.at(-1)?.[2] !== '2026-07-13') {
    throw new Error('Die Übersichtstabelle wechselt beim Navigieren nicht unmittelbar auf den neuen Wochenzeitraum.');
}
repository.releaseWeek();
await pendingWeekLoad;
repository.deferWeek = false;

state.period = 'month';
state.month = new Date('2026-08-01T12:00:00Z');
const pendingNextMonthLoad = WeekNavigation.last.options.onWeekChange();
if (WeekTable.last.renders.at(-1)?.[1] !== 'month' || WeekTable.last.renders.at(-1)?.[2] !== '2026-08-01') {
    throw new Error('Die Übersichtstabelle wechselt beim Navigieren nicht unmittelbar auf den neuen Monatszeitraum.');
}
await pendingNextMonthLoad;
state.month = new Date('2026-07-01T12:00:00Z');
const pendingPreviousMonthLoad = WeekNavigation.last.options.onWeekChange();
if (WeekTable.last.renders.at(-1)?.[2] !== '2026-07-01') {
    throw new Error('Die Übersichtstabelle folgt der Rückwärtsnavigation im Monatszeitraum nicht.');
}
await pendingPreviousMonthLoad;
state.period = 'week';
await WeekNavigation.last.options.onPeriodChange();

await ShiftDefaults.last.options.onSave({ 1: { enabled: true, start: '09:00', end: '17:00' } });
if (!calls.some(call => call[0] === 'saveShiftDefaults') || !Notice.last.messages.some(item => item[1] === 'Personal default shift times saved.')) {
    throw new Error('Persönliche Standarddienste werden nicht gespeichert, neu geladen und bestätigt.');
}
await ShiftCalendarSync.last.options.onSave(false);
await ShiftCalendarSync.last.options.onSave(true);
if (!Notice.last.messages.some(item => String(item[1]).includes('disabled'))
    || !Notice.last.messages.some(item => String(item[1]).includes('Team calendar'))) {
    throw new Error('Kalendersynchronisation zeigt Aktivierung und Opt-out nicht an.');
}
await elements.get('adc-save-default').fire('click');
if (!calls.some(call => call[0] === 'savePreferences')) throw new Error('Persönlicher Ansichtsstandard wird nicht gespeichert.');

state.period = 'month';
await WeekNavigation.last.options.onPeriodChange();
if (!calls.some(call => call[0] === 'range') || elements.get('adc-open-meeting-finder').disabled !== true) {
    throw new Error('Monatswechsel lädt keinen Bereich oder sperrt die wöchentliche Meeting-Suche nicht.');
}
repository.fail = new Error('load failed');
state.period = 'week';
await WeekNavigation.last.options.onWeekChange();
await ShiftDefaults.last.options.onSave({});
await ShiftCalendarSync.last.options.onSave(true);
await elements.get('adc-save-default').fire('click');
if (Notice.last.messages.filter(item => item[0] === 'error').length < 4) {
    throw new Error('Lade- und Speicherfehler werden nicht einheitlich als Fehlermeldung angezeigt.');
}
MeetingFinder.last.options.onError(new Error('meeting failed'));
repository.fail = null;
await MeetingFinder.last.options.onBlocked();
if (!Notice.last.messages.some(item => item[1] === 'The meeting was blocked for all selected people.')) {
    throw new Error('Erfolgreiche Meetingblockierung lädt den Kalender nicht neu und bestätigt sie nicht.');
}
TabNavigation.last.options.onChange('settings');
if (state.activeTab !== 'settings' || state.persisted === 0) throw new Error('Aktiver Tab wird nicht im persönlichen Zustand persistiert.');

console.log('Main coverage smoke: OK');
