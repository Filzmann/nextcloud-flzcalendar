import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../js/main.js', import.meta.url), 'utf8');
const repository = readFileSync(new URL('../../js/repositories/calendar-repository.js', import.meta.url), 'utf8');
const model = readFileSync(new URL('../../js/models/calendar-entry.js', import.meta.url), 'utf8');
const organizationModel = readFileSync(new URL('../../js/models/organization.js', import.meta.url), 'utf8');
const calendarCell = readFileSync(new URL('../../js/components/calendar-cell.js', import.meta.url), 'utf8');
const calendarFilters = readFileSync(new URL('../../js/components/calendar-filters.js', import.meta.url), 'utf8');
const entryDialog = readFileSync(new URL('../../js/components/entry-dialog.js', import.meta.url), 'utf8');
const meetingFinder = readFileSync(new URL('../../js/components/meeting-finder.js', import.meta.url), 'utf8');
const shiftDefaults = readFileSync(new URL('../../js/components/shift-defaults.js', import.meta.url), 'utf8');
const shiftCalendarSync = readFileSync(new URL('../../js/components/shift-calendar-sync.js', import.meta.url), 'utf8');
const externalCalendars = readFileSync(new URL('../../js/components/external-calendars.js', import.meta.url), 'utf8');
const dateSource = readFileSync(new URL('../../js/modules/calendar-date.js', import.meta.url), 'utf8');
const publicHolidaysSource = readFileSync(new URL('../../js/modules/holiday-calendar.js', import.meta.url), 'utf8');
const timelineSource = readFileSync(new URL('../../js/modules/calendar-timeline.js', import.meta.url), 'utf8');
const stateSource = readFileSync(new URL('../../js/modules/calendar-state.js', import.meta.url), 'utf8');
const entryWorkflow = readFileSync(new URL('../../js/modules/entry-workflow.js', import.meta.url), 'utf8');
const meetingCapabilities = readFileSync(new URL('../../js/modules/meeting-capabilities.js', import.meta.url), 'utf8');
const weekTable = readFileSync(new URL('../../js/components/week-table.js', import.meta.url), 'utf8');
const weekNavigation = readFileSync(new URL('../../js/components/week-navigation.js', import.meta.url), 'utf8');
const tabNavigation = readFileSync(new URL('../../js/components/tab-navigation.js', import.meta.url), 'utf8');
for (const contract of [
    'weekTable.render(employees, state)',
    'entryWorkflow.save(data)',
    'repository.savePreferences(state.toPreference())',
    'applyOrganization(data.organization)',
    'tabs.show(state.activeTab, false)',
    'shiftDefaults.set(state.data.shiftDefaults || {})',
    'repository.saveShiftDefaults(defaults)',
    'shiftCalendarSync.set(state.data.calendarSync || {})',
    'repository.saveCalendarSync(enabled)',
    'new window.AdCalendar.components.ExternalCalendars',
    'externalCalendars.load()',
    'meetingFinder.open(CalendarDate.isoDay(state.monday)',
    'repository.range(range.start, range.end)',
    "state.period === 'month'",
    'meetingCapabilities.apply(data.entries, data.employees)',
    'weekTable.setHolidays(data.holidayCalendars || [])',
    "state.isUnfiltered() ? 'Alle Personen'",
    'const sequence = ++loadSequence',
    'if (sequence !== loadSequence) return;',
    'if (sequence === loadSequence) show(error, true)',
]) {
    if (!source.includes(contract)) throw new Error(`Frontend-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class EntryWorkflow', "['delete', 'Dienst und Termine löschen']", "['detach', 'Nur Dienst löschen; Termine als Sperrtermine behalten']", "dialog.addEventListener('cancel'", 'this.dialog.open({ employee', 'this.repository.updateMeeting(existing.meetingUid', 'this.repository.removeMeeting(entry.meetingUid)', 'existing?.seriesUid', "['occurrence', 'Nur dieses Vorkommen']", "['series', 'Gesamte Serie']", 'if (!employee?.canManage) return;', 'this.show(error, true)']) {
    if (!entryWorkflow.includes(contract)) throw new Error(`Eintragsworkflow-Vertrag fehlt: ${contract}`);
}
const workflowContext = { window: { confirm: () => true }, document: {}, Element: class {}, Date, Number, Promise };
runInNewContext(entryWorkflow, workflowContext);
const workflow = Object.create(workflowContext.window.AdCalendar.modules.EntryWorkflow.prototype);
let deletionError = null;
workflow.repository = { remove: async () => { throw new Error('Löschen fehlgeschlagen'); } };
workflow.show = (error, isError) => { if (isError) deletionError = error; };
workflow.reload = async () => {};
await workflow.remove({ id: 7, type: 'appointment', meetingUid: null });
if (deletionError?.message !== 'Löschen fehlgeschlagen') throw new Error('Fehler beim abschließenden Löschen wird nicht angezeigt.');
const capabilitiesContext = { window: {}, Map };
runInNewContext(meetingCapabilities, capabilitiesContext);
const capabilities = new capabilitiesContext.window.AdCalendar.modules.MeetingCapabilities();
const mixedMeeting = [{ employeeUid: 'a', meetingUid: 'meeting-1' }, { employeeUid: 'b', meetingUid: 'meeting-1' }];
capabilities.apply(mixedMeeting, [{ uid: 'a', canManage: true }, { uid: 'b', canManage: false }]);
if (mixedMeeting.some(entry => entry.canManageMeeting !== false)) throw new Error('Meeting mit nicht bearbeitbarer Person wurde in der UI freigegeben.');
const manageableMeeting = [{ employeeUid: 'a', meetingUid: 'meeting-2' }, { employeeUid: 'b', meetingUid: 'meeting-2' }];
capabilities.apply(manageableMeeting, [{ uid: 'a', canManage: true }, { uid: 'b', canManage: true }]);
if (manageableMeeting.some(entry => entry.canManageMeeting !== true)) throw new Error('Vollständig bearbeitbares Meeting wurde in der UI gesperrt.');
for (const contract of ['class CalendarFilters', 'this.renderLeadershipStaffCheckbox()', 'this.state.selected.clear()', 'this.state.persist()', 'this.onChange()', 'Keine explizite Auswahl – Gruppenfilter gelten.', 'this.organization().staffBlockLabel', 'organization.areaOrder(a) - organization.areaOrder(b)']) {
    if (!calendarFilters.includes(contract)) throw new Error(`Kalenderfilter-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class TabNavigation', "addEventListener('click'", "this.show('settings')", "this.onChange(active)"]) {
    if (!tabNavigation.includes(contract)) throw new Error(`Tab-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class WeekTable', 'adc-group-heading', 'adc-week-block', 'adc-outside-month', 'this.calendarCell.render(entries, employee, absences, layout, day, this.timeline)', 'this.timeline.layout(employeeEntries, days)', 'this.timeline.layout(dayEntries, [day])', 'organization.staffBlockLabel', 'organization.roleLabel(value)', 'organization.areaLabel(value)', 'groupCell.colSpan = 8', 'this.orderedEmployees(employees)', 'this.staffRank(a) - this.staffRank(b)', 'roleNames.slice(1)', "join(' / ')"]) {
    if (!weekTable.includes(contract)) throw new Error(`Wochenmatrix-Komponentenvertrag fehlt: ${contract}`);
}
const timelineContext = { window: {}, Date, Set, Math };
runInNewContext(timelineSource, timelineContext);
const CalendarTimeline = timelineContext.window.AdCalendar.modules.CalendarTimeline;
const calendarTimeline = new CalendarTimeline();
const timelineDay = new Date(2026, 6, 6);
const timelineEntries = [
    { start: '2026-07-06T08:00:00', end: '2026-07-06T16:00:00' },
    { start: '2026-07-06T10:00:00', end: '2026-07-06T11:00:00' },
];
const calendarLayout = calendarTimeline.layout(timelineEntries, [timelineDay]);
if (calendarTimeline.gridRow(calendarLayout, timelineEntries[0], timelineDay) !== '2 / 5' || !calendarLayout.rows.includes('48px')) {
    throw new Error('Kalendereinträge werden nicht auf das gemeinsame kompakte Zeitraster abgebildet.');
}
for (const contract of ['class WeekNavigation', "this.setPeriod('week')", "this.setPeriod('month')", 'this.move(-7)', 'this.moveMonth(', 'this.state.persist()', 'this.onWeekChange', 'this.onViewChange', 'CalendarDate.isoWeekValue', 'CalendarDate.monthValue', 'Tage als Zeilen', 'Personen als Zeilen']) {
    if (!weekNavigation.includes(contract)) throw new Error(`Wochennavigations-Komponentenvertrag fehlt: ${contract}`);
}
const navigationContext = { window: {}, document: {}, Date, Number, String };
runInNewContext(dateSource, navigationContext);
runInNewContext(weekNavigation, navigationContext);
const navigation = Object.create(navigationContext.window.AdCalendar.components.WeekNavigation.prototype);
let navigationPersisted = false; let navigationLoaded = false;
navigation.state = { monday: new Date(2026, 0, 5), persist: () => { navigationPersisted = true; } };
navigation.render = () => {};
navigation.onWeekChange = () => { navigationLoaded = true; };
navigation.select('2026-W29');
if (navigationContext.window.AdCalendar.modules.CalendarDate.isoWeekValue(navigation.state.monday) !== '2026-W29' || !navigationPersisted || !navigationLoaded) {
    throw new Error('Ausgewählte Kalenderwoche wird nicht korrekt berechnet, persistiert und geladen.');
}
navigation.state = {
    monday: new Date(2026, 6, 13), month: new Date(2026, 6, 1), period: 'month',
    persist: () => { navigationPersisted = true; },
};
navigation.onWeekChange = () => { navigationLoaded = true; };
navigation.moveMonth(1);
if (navigation.state.month.getFullYear() !== 2026 || navigation.state.month.getMonth() !== 7) {
    throw new Error('Monatsnavigation wechselt nicht stabil zum Folgemonat.');
}
navigation.setPeriod('week');
if (navigation.state.period !== 'week' || navigation.state.monday.getDay() !== 1) {
    throw new Error('Umschalter stellt beim Wechsel zur Woche keinen gültigen Wochenanfang her.');
}
const navigationElement = () => ({ hidden: null, value: '', textContent: '', attributes: {}, setAttribute(name, value) { this.attributes[name] = value; } });
const monthNavigation = Object.create(navigationContext.window.AdCalendar.components.WeekNavigation.prototype);
monthNavigation.state = { monday: new Date(2026, 6, 13), month: new Date(2026, 6, 1), period: 'month', vertical: false };
for (const property of ['label', 'weekNumber', 'monthNumber', 'weekPicker', 'monthPicker', 'previous', 'next', 'weekButton', 'monthButton', 'toggleView', 'heading']) monthNavigation[property] = navigationElement();
monthNavigation.render();
if (monthNavigation.toggleView.hidden !== false || monthNavigation.toggleView.textContent !== 'Personen als Zeilen' || monthNavigation.toggleView.attributes['aria-pressed'] !== 'true') {
    throw new Error('Ausrichtungsumschalter bleibt in der Monatsansicht nicht sichtbar oder verliert seinen Zustand.');
}
class FakeNode {
    constructor(tag = 'div') {
        this.tagName = tag.toUpperCase(); this.children = []; this.dataset = {}; this.className = ''; this.textContent = '';
        this.classList = {
            add: value => { if (!this.className.split(' ').includes(value)) this.className = `${this.className} ${value}`.trim(); },
            toggle: (value, enabled) => { if (enabled) this.classList.add(value); else this.className = this.className.split(' ').filter(item => item !== value).join(' '); },
        };
    }
    append(...children) { this.children.push(...children); }
    replaceChildren(...children) { this.children = children; }
}
const tableDocument = { createElement: tag => new FakeNode(tag) };
const tableContext = { window: {}, document: tableDocument, Date, Number, Set, Math };
runInNewContext(dateSource, tableContext);
runInNewContext(publicHolidaysSource, tableContext);
runInNewContext(timelineSource, tableContext);
runInNewContext(weekTable, tableContext);
const publicHolidays = new tableContext.window.AdCalendar.modules.HolidayCalendar([{
    year: 2026,
    publicHolidays: [
        { name: 'Karfreitag', startDate: '2026-04-03', endDate: '2026-04-03' },
        { name: 'Tag der Arbeit', startDate: '2026-05-01', endDate: '2026-05-01' },
    ],
}, {
    year: 2028,
    publicHolidays: [{ name: '75. Jahrestag des Aufstandes vom 17. Juni 1953', startDate: '2028-06-17', endDate: '2028-06-17' }],
}]);
if (publicHolidays.name(new Date(2026, 4, 1)) !== 'Tag der Arbeit'
    || publicHolidays.name(new Date(2026, 3, 3)) !== 'Karfreitag'
    || publicHolidays.name(new Date(2028, 5, 17)) !== '75. Jahrestag des Aufstandes vom 17. Juni 1953'
    || publicHolidays.name(new Date(2026, 4, 2)) !== '') {
    throw new Error('Gemeinsame gesetzliche Feiertage werden nicht vollständig und datumsstabil gelesen.');
}
const clusterTable = Object.create(tableContext.window.AdCalendar.components.WeekTable.prototype);
clusterTable.organization = () => ({
    staffRoleGroups: () => [], staffBlockLabel: 'Leitungen',
    roleLabel: value => ({'ad-EB':'Einsatzbegleitung','ad-StvBL':'Stellvertretende Büroleitung','ad-Buero':'Büroorganisation'}[value] || value),
    areaLabel: value => ({'ad-Bereich-Nordost':'Nordost','ad-Bereich-West':'West'}[value] || value),
    roleOrder: value => ({'ad-EB':10,'ad-Buero':20,'ad-StvBL':30}[value] ?? 999),
    areaOrder: value => ({'ad-Bereich-West':10,'ad-Bereich-Nordost':20}[value] ?? 999),
});
if (clusterTable.clusterLabel({ roles: ['ad-StvBL', 'ad-EB'], areas: ['ad-Bereich-Nordost', 'ad-Bereich-West'] }) !== 'Einsatzbegleitung (Stellvertretende Büroleitung) · West / Nordost') {
    throw new Error('Mehrfachrollen und -bereiche folgen im Gruppentitel nicht der Backend-Reihenfolge.');
}
const backendOrderedEmployees = clusterTable.orderedEmployees([
    { uid: 'office-ne', displayName: 'Büro Nordost', roles: ['ad-Buero'], areas: ['ad-Bereich-Nordost'] },
    { uid: 'eb-ne', displayName: 'EB Nordost', roles: ['ad-EB'], areas: ['ad-Bereich-Nordost'] },
    { uid: 'eb-west', displayName: 'EB West', roles: ['ad-EB'], areas: ['ad-Bereich-West'] },
]);
if (backendOrderedEmployees.map(employee => employee.uid).join(',') !== 'eb-west,eb-ne,office-ne') {
    throw new Error('Kalendergruppen folgen nicht der im Backend festgelegten Rollen- und Bereichsreihenfolge.');
}
const monthContainer = new FakeNode();
const monthTable = new tableContext.window.AdCalendar.components.WeekTable({
    container: monthContainer,
    calendarCell: { render: () => '' },
    organization: clusterTable.organization,
});
monthTable.setHolidays([{ year: 2026, publicHolidays: [{ name: 'Tag der Arbeit', startDate: '2026-05-01', endDate: '2026-05-01' }] }]);
const monthDate = tableContext.window.AdCalendar.modules.CalendarDate;
monthTable.render([{ uid: 'person-a', displayName: 'Person A', roles: ['ad-Buero'], areas: ['ad-Bereich-West'] }], {
    period: 'month', month: new Date(2026, 6, 1), vertical: false, selected: new Set(),
    data: { entries: [], absences: [] },
    visibleRange: () => monthDate.monthRange(new Date(2026, 6, 1)),
});
const flattenNodes = node => [node, ...node.children.flatMap(flattenNodes)];
const renderedNodes = flattenNodes(monthContainer);
if (monthContainer.children.length !== 5 || !monthContainer.className.includes('adc-month-weeks')) throw new Error('Monatsansicht rendert nicht alle betroffenen Wochenblöcke.');
if (!renderedNodes.some(node => node.className.includes('adc-outside-month'))) throw new Error('Randtage der Monatsansicht werden nicht gekennzeichnet.');
if (!renderedNodes.some(node => node.tagName === 'TH' && node.scope === 'col' && node.className.includes('adc-person-heading') && node.textContent === 'Person A')) throw new Error('Monatsansicht übernimmt die gewählte Ausrichtung mit Personen als Spalten nicht.');
if (renderedNodes.some(node => node.tagName === 'TH' && node.scope === 'row' && node.textContent === 'Person A')) throw new Error('Monatsansicht erzwingt trotz Umschaltung weiterhin Personen als Zeilen.');
if (!renderedNodes.some(node => node.tagName === 'TH' && node.scope === 'row' && node.className.includes('adc-weekend') && node.textContent.includes('Wochenende'))) throw new Error('Wochenenden werden in der Tagesbeschriftung nicht barrierefrei gekennzeichnet.');
if (!renderedNodes.some(node => node.tagName === 'TD' && node.className.includes('adc-weekend'))) throw new Error('Wochenendspalten oder -zeilen werden in der Kalendermatrix nicht markiert.');
monthTable.render([{ uid: 'person-a', displayName: 'Person A', roles: ['ad-Buero'], areas: ['ad-Bereich-West'] }], {
    period: 'month', month: new Date(2026, 6, 1), vertical: true, selected: new Set(),
    data: { entries: [], absences: [] },
    visibleRange: () => monthDate.monthRange(new Date(2026, 6, 1)),
});
const verticalMonthNodes = flattenNodes(monthContainer);
if (!verticalMonthNodes.some(node => node.tagName === 'TH' && node.scope === 'row' && node.className.includes('adc-person-heading') && node.textContent === 'Person A')) throw new Error('Personenspalte der Monatsansicht ist nicht als fixierter Personenbezug gekennzeichnet.');
if (!verticalMonthNodes.some(node => node.tagName === 'TH' && node.scope === 'col' && node.className.includes('adc-weekend') && node.textContent.includes('Wochenende'))) throw new Error('Wochenendspalten werden in der vertikalen Monatsausrichtung nicht barrierefrei gekennzeichnet.');
if (!monthTable.dayLabel(new Date(2026, 4, 1), { weekday: 'long', day: '2-digit', month: '2-digit' }).includes('Tag der Arbeit')
    || !monthTable.dayClasses(new Date(2026, 4, 1), null).includes('adc-holiday')) {
    throw new Error('Gemeinsame Feiertage erhalten in der Kalendermatrix keine sichtbare und textliche Kennzeichnung.');
}
for (const contract of ["params.set('people'", "params.set('roles'", "params.set('areas'", "params.set('period', 'month')", 'this.data.defaultFilters ||', 'this.data.currentUserProfile?.roles', 'if (this.selected.size) return this.selected.has(employee.uid)', 'showLeadershipStaff: this.showLeadershipStaff', 'period: this.period']) {
    if (!stateSource.includes(contract)) throw new Error(`Kalenderzustandsvertrag fehlt: ${contract}`);
}
if (weekTable.includes("header.append(this.node('th', 'Gesamt'))")) throw new Error('Entfernte Gesamtspalte wird noch gerendert.');
if (source.includes('state.data.summaries')) throw new Error('Entfernter Gesamt-Payload wird noch verwendet.');
for (const contract of ['extends BaseRepository', 'range(start, end)', 'savePreferences(filters)', 'saveShiftDefaults(shiftDefaults)', 'saveCalendarSync(enabled)', 'meetingGaps(start, employeeUids, durationMinutes)', 'blockMeeting(start, end, employeeUids, title)', 'updateMeeting(meetingUid, start, end, title)', 'removeMeeting(meetingUid)', "method: id == null ? 'POST' : 'PUT'", "seriesScope = 'occurrence'", '{ childMode, seriesScope }']) {
    if (!repository.includes(contract)) throw new Error(`Repository-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class Organization extends BaseModel', 'roleLabel(groupId)', 'areaLabel(groupId)', 'staffRoleGroups()', 'roleOrder(groupId)', 'areaOrder(groupId)', 'toArray()']) {
    if (!organizationModel.includes(contract)) throw new Error(`Organisationsmodell-Vertrag fehlt: ${contract}`);
}
const organizationContext = { window: { LocalBase: { models: { Model: class {} } } }, Number, String, JSON };
runInNewContext(organizationModel, organizationContext);
const sortableOrganization = new organizationContext.window.AdCalendar.models.Organization({
    roles: { office: { groupId: 'ad-Buero', label: 'Büro', sortOrder: 20 } },
    areas: { northeast: { groupId: 'ad-Bereich-Nordost', label: 'Nordost', sortOrder: 30 } },
});
if (sortableOrganization.roleOrder('ad-Buero') !== 20 || sortableOrganization.areaOrder('ad-Bereich-Nordost') !== 30) {
    throw new Error('Das Kalender-Organisationsmodell übernimmt die Backend-Reihenfolge nicht.');
}
for (const contract of ['window.LocalBase.models.Model', 'extends BaseModel', 'toArray()', 'this.defaultDate', 'this.defaultModified', 'this.defaultDeleted', 'this.meetingUid', 'this.seriesUid', 'this.seriesTimezone', 'this.canManageMeeting']) {
    if (!model.includes(contract)) throw new Error(`Modell-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class CalendarCell', 'adc-cell-actions', 'adc-entry__children', 'grid-template-rows:', 'grid-row:', 'entry.parentEntryId === shift.id', 'entry.canManageMeeting !== false', 'adc-entry__blocked-marker', 'adc-entry__series-marker', 'aria-hidden="true">🔒', "data-action=\"add-entry\"", 'data-tooltip="Dienst anlegen"', 'icon-calendar-dark']) {
    if (!calendarCell.includes(contract)) throw new Error(`Kalenderzellen-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class MeetingFinder', 'this.selected = new Set(selected)', 'employeeUids.length < 2', 'this.repository.meetingGaps', 'renderResults(gaps, canBlockAll)', 'In der nächsten Woche suchen', 'abwählen', 'this.repository.blockMeeting', 'Number(this.duration.value)']) {
    if (!meetingFinder.includes(contract)) throw new Error(`Meeting-Finder-Vertrag fehlt: ${contract}`);
}
for (const contract of ['class ShiftDefaults', 'Array.from({ length: 7 }', 'data-field="enabled"', 'this.onSave(this.collect())']) {
    if (!shiftDefaults.includes(contract)) throw new Error(`Dienstzeiten-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class ShiftCalendarSync', 'this.onSave(this.input.checked)', 'status.calendarName', 'Kalender ist aktiv']) {
    if (!shiftCalendarSync.includes(contract)) throw new Error(`Dienstkalender-Komponentenvertrag fehlt: ${contract}`);
}
for (const contract of ['class ExternalCalendars', "provider === 'google'", 'window.location.assign(response.authorizationUrl)', 'this.dialog.showModal()', 'this.repository.connectCalDav', 'this.repository.disconnectExternalCalendar', 'window.confirm(', 'this.password.value = \'\'', 'https://mail.adberlin.org', 'Der Kopano-Betreiber muss CalDAV']) {
    if (!externalCalendars.includes(contract)) throw new Error(`Externe-Kalender-Komponentenvertrag fehlt: ${contract}`);
}
const externalElements = {};
const externalElement = id => externalElements[id] ||= { id, value: '', textContent: '', hidden: false, disabled: false, listeners: {}, addEventListener(type, listener) { this.listeners[type] = listener; }, focus() { this.focused = true; } };
const providerButtons = ['kopano', 'google', 'apple', 'manual'].map(provider => ({ ...externalElement(`connect-${provider}`), dataset: { externalConnect: provider } }));
const disconnectButtons = ['kopano', 'google', 'apple', 'manual'].map(provider => ({ ...externalElement(`disconnect-${provider}`), dataset: { externalDisconnect: provider } }));
const externalDialog = externalElement('adc-external-calendar-dialog'); externalDialog.showModal = () => { externalDialog.open = true; }; externalDialog.close = () => { externalDialog.open = false; };
const externalForm = externalElement('adc-external-calendar-form'); externalForm.reportValidity = () => true; const externalSubmit = { disabled: false }; externalForm.querySelector = () => externalSubmit;
const externalRepository = {
    externalCalendars: async () => ({ externalCalendars: { kopano: { connected: false, available: true, calendarName: 'AD Dienste' } } }),
    connectCalDav: async (...args) => { externalRepository.connected = args; return { externalCalendars: { kopano: { connected: true, available: true, calendarName: 'AD Dienste' } } }; },
    disconnectExternalCalendar: async provider => ({ externalCalendars: { [provider]: { connected: false, available: true, calendarName: 'AD Dienste' } } }),
    startGoogleCalendarConnection: async () => ({ authorizationUrl: 'https://accounts.google.test/oauth' }),
};
let assignedAuthorizationUrl = '';
const externalContext = {
    window: { confirm: () => true, location: { assign: value => { assignedAuthorizationUrl = value; } } },
    document: {
        getElementById: id => id === 'adc-external-calendar-dialog' ? externalDialog : id === 'adc-external-calendar-form' ? externalForm : externalElement(id),
        querySelectorAll: selector => selector === '[data-external-connect]' ? providerButtons : disconnectButtons,
        querySelector: selector => {
            const match = selector.match(/data-external-(connect|disconnect)="([^"]+)"/);
            return match ? (match[1] === 'connect' ? providerButtons : disconnectButtons).find(button => Object.values(button.dataset).includes(match[2])) : null;
        },
    },
    Object, Promise,
};
runInNewContext(externalCalendars, externalContext);
const externalComponent = new externalContext.window.AdCalendar.components.ExternalCalendars({ repository: externalRepository, onMessage() {} });
await externalComponent.load();
await externalComponent.connect('kopano');
if (!externalDialog.open || externalElement('adc-external-server-url').value !== 'https://mail.adberlin.org' || !externalElement('adc-external-server-url').focused) throw new Error('Kopano-Dialog verwendet nicht die änderbare Vorgabe oder setzt keinen Fokus.');
externalElement('adc-external-username').value = 'person-a'; externalElement('adc-external-password').value = 'secret';
await externalComponent.submit({ preventDefault() {} });
if (externalRepository.connected?.[0] !== 'kopano' || externalElement('adc-external-password').value !== '' || !externalElement('adc-external-kopano-status').textContent.includes('Verbunden')) throw new Error('CalDAV-Verbindung aktualisiert Status nicht oder behält das Passwort im DOM.');
await externalComponent.connect('google');
if (assignedAuthorizationUrl !== 'https://accounts.google.test/oauth') throw new Error('Google-Verbindung startet keinen Top-Level-OAuth-Redirect.');
const syncInput = { checked: false };
const syncStatus = { textContent: '' };
const syncForm = { listener: null, addEventListener(type, listener) { this.listener = listener; }, reportValidity: () => true };
const syncContext = { window: {}, document: { getElementById: id => ({
    'adc-calendar-sync-form': syncForm,
    'adc-calendar-sync-enabled': syncInput,
    'adc-calendar-sync-status': syncStatus,
}[id]) } };
runInNewContext(shiftCalendarSync, syncContext);
let savedSync = null;
const syncComponent = new syncContext.window.AdCalendar.components.ShiftCalendarSync({ onSave: async enabled => { savedSync = enabled; } });
syncComponent.set({ enabled: true, calendarName: 'AD Dienste' });
await syncForm.listener({ preventDefault() {} });
if (!syncInput.checked || !syncStatus.textContent.includes('AD Dienste') || savedSync !== true) throw new Error('Persönliche Kalenderaktivierung ist nicht tastaturbedienbar oder zeigt ihren Zustand nicht an.');
for (const contract of ['class EntryDialog', 'this.dialog.showModal()', 'this.updateType()', 'this.updateRecurrence()', 'recurrenceFrequency:', 'recurrenceWeekdays:', 'recurrenceTimezone: this.timezone()', "typeof configured === 'string'", 'this.nextFreeShift', 'setCustomValidity(message)', 'Boolean(entry?.meetingUid)', "entry.type === 'shift'", 'start < new Date(entry.end)', 'end > new Date(entry.start)']) {
    if (!entryDialog.includes(contract)) throw new Error(`Eintragsdialog-Vertrag fehlt: ${contract}`);
}

const componentContext = {
    window: { LocalBase: { ui: { esc: value => String(value ?? '') } } },
    Date,
};
runInNewContext(calendarCell, componentContext);
const cell = new componentContext.window.AdCalendar.components.CalendarCell();
const cellHtml = cell.render([
    { id: 1, type: 'shift', start: '2026-07-06T08:00:00Z', end: '2026-07-06T16:00:00Z', title: '', parentEntryId: null },
    { id: 2, type: 'appointment', start: '2026-07-06T10:00:00Z', end: '2026-07-06T11:00:00Z', title: 'Teamtermin', parentEntryId: 1 },
], { canManage: true });
if (!cellHtml.includes('adc-entry__children') || cellHtml.indexOf('Teamtermin') < cellHtml.indexOf('adc-entry--shift')) {
    throw new Error('Termin wurde nicht sichtbar innerhalb des Dienstes gerendert.');
}
if ((cellHtml.match(/Teamtermin/g) || []).length !== 1) throw new Error('Enthaltener Termin wurde mehrfach gerendert.');
const timedCellHtml = cell.render(timelineEntries.map((entry, index) => ({ ...entry, id: index + 10, type: index === 0 ? 'shift' : 'appointment', parentEntryId: index === 0 ? null : 10, title: index === 0 ? '' : 'Zeitachsentermin' })), { canManage: true }, [], calendarLayout, timelineDay, calendarTimeline);
if (!timedCellHtml.includes('grid-template-rows:') || !timedCellHtml.includes('grid-row:2 / 5')) throw new Error('Kalenderzelle verwendet das gemeinsame Zeitraster nicht.');
const blockedHtml = cell.render([
    { id: 3, type: 'appointment', start: '2026-07-06T18:00:00Z', end: '2026-07-06T19:00:00Z', title: 'Blockiert', parentEntryId: null },
], { canManage: true });
if (!blockedHtml.includes('adc-entry--blocked') || !blockedHtml.includes('adc-entry__blocked-marker') || !blockedHtml.includes('Sperrtermin')) throw new Error('Sperrtermin wird nicht kräftig und textlich als Sperre gekennzeichnet.');
const seriesHtml = cell.render([
    { id: 4, type: 'appointment', start: '2026-07-06T10:00:00Z', end: '2026-07-06T11:00:00Z', title: 'Serie', parentEntryId: null, seriesUid: 'series-demo' },
], { canManage: true });
if (!seriesHtml.includes('adc-entry__series-marker') || !seriesHtml.includes('Serientermin')) throw new Error('Serientermin wird nicht zusätzlich zur visuellen Markierung textlich gekennzeichnet.');

const tabContext = { window: {} };
runInNewContext(tabNavigation, tabContext);
const fakeButton = () => ({ listeners: {}, attributes: {}, addEventListener(type, listener) { this.listeners[type] = listener; }, setAttribute(name, value) { this.attributes[name] = value; }, click() { this.listeners.click(); } });
const calendarButton = fakeButton(); const settingsButton = fakeButton();
const calendarPanel = { hidden: false }; const settingsPanel = { hidden: true }; const tabChanges = [];
new tabContext.window.AdCalendar.components.TabNavigation({ calendarButton, settingsButton, calendarPanel, settingsPanel, onChange: tab => tabChanges.push(tab) });
settingsButton.click();
if (!calendarPanel.hidden || settingsPanel.hidden || settingsButton.attributes['aria-selected'] !== 'true' || tabChanges[0] !== 'settings') {
    throw new Error('Klick auf den Einstellungs-Tab schaltet die Panels nicht um.');
}

const stateContext = { window: {}, Date, Set, URLSearchParams, Number };
runInNewContext(dateSource, stateContext);
runInNewContext(stateSource, stateContext);
const historyCalls = [];
const CalendarState = stateContext.window.AdCalendar.modules.CalendarState;
const CalendarDate = stateContext.window.AdCalendar.modules.CalendarDate;
const julyRange = CalendarDate.monthRange(new Date(2026, 6, 1));
if (CalendarDate.isoDay(julyRange.start) !== '2026-06-29' || CalendarDate.isoDay(julyRange.end) !== '2026-08-03' || julyRange.weeks.length !== 5) {
    throw new Error('Der sichtbare Monatsbereich umfasst nicht alle angefangenen Kalenderwochen.');
}
const monthHistoryCalls = [];
const monthState = new CalendarState(new Set(), { search: '?period=month&month=2026-07', pathname: '/apps/adcalendar/' }, { replaceState: (...args) => monthHistoryCalls.push(args) }).restore();
if (monthState.period !== 'month' || CalendarDate.monthValue(monthState.month) !== '2026-07') throw new Error('Monatsansicht wird nicht aus der URL wiederhergestellt.');
monthState.persist();
if (!monthHistoryCalls.at(-1)[2].includes('period=month') || !monthHistoryCalls.at(-1)[2].includes('month=2026-07')) throw new Error('Monatsansicht wird nicht in der URL persistiert.');
const filterState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState: (...args) => historyCalls.push(args) }).restore();
filterState.data = {
    defaultFilters: null,
    currentUserProfile: { roles: ['ad-Buero'], areas: ['ad-Bereich-Sued'] },
    employees: [
        { uid: 'bo', roles: ['ad-Buero'], areas: ['ad-Bereich-Sued'] },
        { uid: 'pdl', roles: ['ad-PDL'], areas: [] },
    ],
};
filterState.applyInitialFilters();
if (filterState.showLeadershipStaff || filterState.availableEmployees().map(employee => employee.uid).join(',') !== 'bo') throw new Error('Eigengruppen-Standard wurde beim Refactoring verändert.');
filterState.showLeadershipStaff = true; filterState.persist();
if (filterState.availableEmployees().map(employee => employee.uid).join(',') !== 'bo,pdl' || !historyCalls.at(-1)[2].includes('staff=visible')) throw new Error('Der gemeinsame Stabs-/GF-Block wird nicht unabhängig zugeschaltet oder in der URL persistiert.');
filterState.showLeadershipStaff = false; filterState.persist();
if (filterState.availableEmployees().some(employee => employee.uid === 'pdl') || !historyCalls.at(-1)[2].includes('staff=hidden')) throw new Error('Stabsfilter oder URL-Persistenz wurde beim Refactoring verändert.');

const staffDefaultState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
staffDefaultState.data = {
    defaultFilters: null,
    currentUserProfile: { roles: ['ad-PDL'], areas: [] },
    employees: filterState.data.employees,
};
staffDefaultState.applyInitialFilters();
if (!staffDefaultState.showLeadershipStaff || !staffDefaultState.leadershipStaffOnly || staffDefaultState.availableEmployees().map(employee => employee.uid).join(',') !== 'pdl') throw new Error('Mitglieder des gemeinsamen Leitungs-/Stabsblocks erhalten nicht ihren passenden Erststandard.');
const staffUrlCalls = [];
staffDefaultState.history = { replaceState: (...args) => staffUrlCalls.push(args) };
staffDefaultState.persist();
if (!staffUrlCalls.at(-1)[2].includes('staffOnly=1')) throw new Error('Reiner Leitungs-/Stabsfilter wird nicht in der URL persistiert.');
const restoredStaffState = new CalendarState(new Set(['ad-PDL']), { search: '?staff=visible&staffOnly=1', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
restoredStaffState.data = staffDefaultState.data;
restoredStaffState.applyInitialFilters();
if (!restoredStaffState.leadershipStaffOnly || restoredStaffState.availableEmployees().map(employee => employee.uid).join(',') !== 'pdl') throw new Error('Reiner Leitungs-/Stabsfilter geht beim Seiten-Reload verloren.');

const officeEmployees = [
    { uid: 'bl-now', roles: ['ad-BL', 'ad-Buero'], areas: ['ad-Bereich-Nordost', 'ad-Bereich-West'] },
    { uid: 'bl-south', roles: ['ad-BL', 'ad-Buero'], areas: ['ad-Bereich-Sued'] },
    { uid: 'deputy-west', roles: ['ad-StvBL', 'ad-EB'], areas: ['ad-Bereich-West'] },
    { uid: 'office-west', roles: ['ad-Buero'], areas: ['ad-Bereich-West'] },
    { uid: 'eb-west', roles: ['ad-EB'], areas: ['ad-Bereich-West'] },
    { uid: 'pfk', roles: ['ad-PFK'], areas: [] },
    { uid: 'pdl', roles: ['ad-PDL'], areas: [] },
];
const westState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
westState.data = {
    defaultFilters: { people: [], roles: [], areas: ['ad-Bereich-West'], vertical: true, showLeadershipStaff: false },
    currentUserProfile: { roles: [], areas: [] },
    employees: officeEmployees,
};
westState.applyInitialFilters();
if (westState.availableEmployees().map(employee => employee.uid).join(',') !== 'bl-now,deputy-west,office-west,eb-west') {
    throw new Error('Ein reiner Buerobereichsfilter zeigt nicht BL, Stv. BL, BO und EB des gewaehlten Bueros.');
}

const officeRoleState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
officeRoleState.data = {
    defaultFilters: { people: [], roles: ['ad-Buero'], areas: [], vertical: true, showLeadershipStaff: false },
    currentUserProfile: { roles: [], areas: [] },
    employees: officeEmployees,
};
officeRoleState.applyInitialFilters();
if (officeRoleState.availableEmployees().map(employee => employee.uid).join(',') !== 'office-west') {
    throw new Error('Der reine Büromitarbeiter*innen-Filter zeigt Personen mit vorrangiger Büroleitungsrolle.');
}

const ebRoleState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
ebRoleState.data = {
    defaultFilters: { people: [], roles: ['ad-EB'], areas: [], vertical: true, showLeadershipStaff: false },
    currentUserProfile: { roles: [], areas: [] },
    employees: officeEmployees,
};
ebRoleState.applyInitialFilters();
if (ebRoleState.availableEmployees().map(employee => employee.uid).join(',') !== 'eb-west') {
    throw new Error('Der reine Einsatzbegleitungsfilter zeigt Personen mit vorrangiger Stellvertretungsrolle.');
}

const emptyFilterState = new CalendarState(new Set(['ad-PDL']), { search: '', pathname: '/apps/adcalendar/' }, { replaceState() {} }).restore();
emptyFilterState.data = {
    defaultFilters: null,
    currentUserProfile: { roles: [], areas: [] },
    employees: officeEmployees,
};
emptyFilterState.applyInitialFilters();
if (!emptyFilterState.isUnfiltered() || emptyFilterState.availableEmployees().map(employee => employee.uid).join(',') !== officeEmployees.map(employee => employee.uid).join(',')) {
    throw new Error('Ohne Personen-, Rollen- oder Bereichsauswahl muessen alle Personen mit sichtbarer Planerrolle erscheinen.');
}
if ('empty' in emptyFilterState.toPreference()) throw new Error('Der ueberholte leere Eigengruppenfilter darf nicht mehr gespeichert werden.');
emptyFilterState.period = 'month';
if (emptyFilterState.toPreference().period !== 'month') throw new Error('Der Ansichtszeitraum wird nicht im persönlichen Standard gespeichert.');

const dialogContext = { window: {}, document: {}, Date };
runInNewContext(entryDialog, dialogContext);
const dialog = Object.create(dialogContext.window.AdCalendar.components.EntryDialog.prototype);
let startValidity = '';
let endValidity = '';
dialog.entries = () => [{ id: 7, employeeUid: 'demo', type: 'shift', start: '2026-07-06T10:00:00Z', end: '2026-07-06T18:00:00Z' }];
dialog.shiftDefaults = () => ({ '1': { enabled: true, start: '07:00', end: '15:00' } });
dialog.fields = {
    type: { value: 'shift' }, employee: { value: 'demo' },
    start: { value: '2026-07-06T13:00', setCustomValidity: value => { startValidity = value; } },
    end: { value: '2026-07-06T14:00', setCustomValidity: value => { endValidity = value; } },
    'entry-id': { value: '' }, 'time-help': { textContent: '' },
};
if (dialog.validate() !== false || startValidity === '' || endValidity === '') throw new Error('Dienstüberschneidung wurde im Dialog nicht blockiert.');
const suggestion = dialog.nextFreeShift('demo', new Date('2026-07-06T12:00:00'));
if (suggestion.start < new Date('2026-07-06T18:00:00Z') && suggestion.end > new Date('2026-07-06T10:00:00Z')) {
    throw new Error('Vorgeschlagener Dienst überschneidet den vorhandenen Dienst.');
}
dialog.entries = () => [];
dialog.shiftDefaults = () => ({ '1': { enabled: true, start: '20:00', end: '06:00' } });
const overnightSuggestion = dialog.nextFreeShift('demo', new Date('2026-07-06T12:00:00'));
if (overnightSuggestion.end - overnightSuggestion.start !== 10 * 60 * 60 * 1000) throw new Error('Persönlicher Nachtdienststandard wurde nicht übernommen.');
let recurrenceValidity = 'alt';
dialog.weekdays = Array.from({ length: 7 }, (_, index) => ({ value: String(index + 1), checked: false }));
dialog.fields = {
    'recurrence-frequency': { value: 'weekly', setCustomValidity: value => { recurrenceValidity = value; } },
    'recurrence-fields': { hidden: false }, 'recurrence-options': { hidden: true },
    'recurrence-weekdays': { hidden: true }, 'recurrence-interval': { required: false },
    'recurrence-until': { required: false, min: '', value: '' }, start: { value: '2026-07-06T09:00' },
};
dialog.updateRecurrence();
if (dialog.fields['recurrence-options'].hidden || dialog.fields['recurrence-weekdays'].hidden || !dialog.fields['recurrence-interval'].required || !dialog.fields['recurrence-until'].required || !dialog.weekdays[0].checked || recurrenceValidity !== '') {
    throw new Error('Wöchentliche Eingabe aktiviert Pflichtfelder oder Startwochentag nicht tastaturunabhängig.');
}
dialogContext.window.OC = { getTimeZone: () => 'Europe/Berlin' };
if (dialog.timezone() !== 'Europe/Berlin') throw new Error('Konfigurierte Nextcloud-Zeitzone wird nicht für die Serie übernommen.');
console.log('Calendar workflow smoke: OK');
