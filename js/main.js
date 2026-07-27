(function () {
    'use strict';
    const apiClient = new window.LocalBase.api.ApiClient({
        appId: 'adcalendar',
        errorMessage: (data, status) => data?.error || data?.message || `HTTP ${status}`,
    });
    const repository = new window.AdCalendar.repositories.CalendarRepository(apiClient);
    const notice = new window.LocalBase.ui.Notice('adc-notice', { baseClass: 'adc-notice', typeClassPrefix: 'adc-notice--' });
    const EntryModel = window.AdCalendar.models.CalendarEntry;
    const OrganizationModel = window.AdCalendar.models.Organization;
    const CalendarDate = window.AdCalendar.modules.CalendarDate;
    const leadershipStaffRoles = new Set();
    let loadSequence = 0;
    let organization = new OrganizationModel({});
    const elements = Object.fromEntries(['calendar-tables','filter-status'].map(id => [id, document.getElementById(`adc-${id}`)]));
    const state = new window.AdCalendar.modules.CalendarState(leadershipStaffRoles).restore();
    const meetingCapabilities = new window.AdCalendar.modules.MeetingCapabilities();
    const tabs = new window.AdCalendar.components.TabNavigation({
        calendarButton: document.getElementById('adc-tab-calendar'),
        settingsButton: document.getElementById('adc-tab-settings'),
        calendarPanel: document.getElementById('adc-calendar-view'),
        settingsPanel: document.getElementById('adc-settings-view'),
        onChange: tab => { state.activeTab = tab; state.persist(); },
    });
    tabs.show(state.activeTab, false);
    const calendarCell = new window.AdCalendar.components.CalendarCell();
    const weekTable = new window.AdCalendar.components.WeekTable({
        container: elements['calendar-tables'], calendarCell,
        organization: () => organization,
    });
    let entryWorkflow;
    const entryDialog = new window.AdCalendar.components.EntryDialog({
        entries: () => state.data?.entries || [],
        shiftDefaults: () => state.data?.shiftDefaults || {},
        onSubmit: data => entryWorkflow.save(data),
    });
    const meetingFinder = new window.AdCalendar.components.MeetingFinder({
        repository,
        onError: error => show(error, true),
        onBlocked: async () => { await load(); show('Meeting wurde für alle ausgewählten Personen blockiert.'); },
    });
    const shiftDefaults = new window.AdCalendar.components.ShiftDefaults({ onSave: saveShiftDefaults });
    const shiftCalendarSync = new window.AdCalendar.components.ShiftCalendarSync({ onSave: saveCalendarSync });
    const externalCalendars = new window.AdCalendar.components.ExternalCalendars({ repository, onMessage: show });
    const calendarFilters = new window.AdCalendar.components.CalendarFilters({
        state,
        organization: () => organization,
        leadershipStaffRoles,
        onChange: renderTable,
    });
    const weekNavigation = new window.AdCalendar.components.WeekNavigation({
        state,
        onWeekChange: load,
        onViewChange: renderTable,
        onPeriodChange: load,
    });
    entryWorkflow = new window.AdCalendar.modules.EntryWorkflow({
        repository,
        state,
        dialog: entryDialog,
        body: elements['calendar-tables'],
        show,
        reload: load,
    });

    function show(message, error) { if (error) notice.error(message); else if (message) notice.success(message); else notice.clear(); }

    function renderFilters() {
        calendarFilters.render();
        entryDialog.setEmployees(state.data.employees.filter(employee => employee.canManage));
        shiftDefaults.set(state.data.shiftDefaults || {});
        shiftCalendarSync.set(state.data.calendarSync || {});
        const meetingButton = document.getElementById('adc-open-meeting-finder');
        meetingButton.disabled = state.period === 'month';
        meetingButton.title = state.period === 'month' ? 'Die Meetinglückensuche ist in der Wochenansicht verfügbar.' : '';
    }

    async function saveShiftDefaults(defaults) {
        try {
            const response = await repository.saveShiftDefaults(defaults);
            state.data.shiftDefaults = response.shiftDefaults;
            shiftDefaults.set(response.shiftDefaults);
            await load();
            show('Persönliche Standard-Dienstzeiten gespeichert.');
        } catch (error) { show(error, true); }
    }

    async function saveCalendarSync(enabled) {
        try {
            const response = await repository.saveCalendarSync(enabled);
            state.data.calendarSync = response.calendarSync;
            shiftCalendarSync.set(response.calendarSync);
            show(enabled ? 'Der private Kalender „AD Dienste“ ist aktiviert.' : 'Die private Dienstkalender-Synchronisation ist deaktiviert.');
        } catch (error) { show(error, true); }
    }

    function applyOrganization(data) {
        organization = OrganizationModel.get(data);
        leadershipStaffRoles.clear();
        for (const group of organization.staffRoleGroups()) leadershipStaffRoles.add(group);
    }

    function renderTable() {
        const employees = state.availableEmployees();
        elements['filter-status'].textContent = state.selected.size ? `${employees.length} ausgewählt` : state.isUnfiltered() ? 'Alle Personen' : `${employees.length} gefiltert`;
        weekTable.render(employees, state);
    }

    async function load() {
        const sequence = ++loadSequence;
        const visibleRange = state.visibleRange();
        const range = { start: CalendarDate.isoDay(visibleRange.start), end: CalendarDate.isoDay(visibleRange.end) };
        weekNavigation.render();
        try {
            const requestedPeriod = state.period;
            const data = state.period === 'month'
                ? await repository.range(range.start, range.end)
                : await repository.week(range.start);
            if (sequence !== loadSequence) return;
            data.entries = EntryModel.get_all(data.entries);
            meetingCapabilities.apply(data.entries, data.employees);
            state.data = data;
            weekTable.setHolidays(data.holidayCalendars || []);
            applyOrganization(data.organization);
            state.applyInitialFilters();
            if (state.period !== requestedPeriod) {
                state.persist();
                await load();
                return;
            }
            weekNavigation.render();
            renderFilters();
            renderTable();
            tabs.show(state.activeTab, false);
            show('');
        } catch (error) {
            if (sequence === loadSequence) show(error, true);
        }
    }

    document.getElementById('adc-open-meeting-finder').addEventListener('click', () => meetingFinder.open(CalendarDate.isoDay(state.monday), state.data.employees, [...state.selected]));
    document.getElementById('adc-save-default').addEventListener('click', async () => {
        try {
            await repository.savePreferences(state.toPreference());
            show('Aktuelle Filter und Ansicht wurden zum persönlichen Standard gemacht.');
        } catch (error) { show(error, true); }
    });
    externalCalendars.load();
    const connectionResult = new URLSearchParams(window.location.search).get('calendarConnection');
    if (connectionResult === 'google-connected') show('Google-Kalender wurde verbunden.');
    else if (connectionResult === 'google-error') show('Die Google-Verbindung konnte nicht abgeschlossen werden.', true);
    load();
}());
