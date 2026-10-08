(function () {
    'use strict';
    const l10n = window.FlzCalendar.l10n;
    const apiClient = new window.LocalBase.api.ApiClient({
        appId: 'flzcalendar',
        errorMessage: (data, status) => data?.error || data?.message || `HTTP ${status}`,
    });
    const repository = new window.FlzCalendar.repositories.CalendarRepository(apiClient);
    const notice = new window.LocalBase.ui.Notice('flz-calendar-notice', { baseClass: 'flz-calendar-notice', typeClassPrefix: 'flz-calendar-notice--' });
    const EntryModel = window.FlzCalendar.models.CalendarEntry;
    const OrganizationModel = window.FlzCalendar.models.Organization;
    const CalendarDate = window.FlzCalendar.modules.CalendarDate;
    const leadershipStaffRoles = new Set();
    let loadSequence = 0;
    let organization = new OrganizationModel({});
    const elements = Object.fromEntries(['calendar-tables','filter-status'].map(id => [id, document.getElementById(`flz-calendar-${id}`)]));
    const state = new window.FlzCalendar.modules.CalendarState(leadershipStaffRoles).restore();
    const meetingCapabilities = new window.FlzCalendar.modules.MeetingCapabilities();
    const tabs = new window.FlzCalendar.components.TabNavigation({
        calendarButton: document.getElementById('flz-calendar-tab-calendar'),
        settingsButton: document.getElementById('flz-calendar-tab-settings'),
        calendarPanel: document.getElementById('flz-calendar-calendar-view'),
        settingsPanel: document.getElementById('flz-calendar-settings-view'),
        onChange: tab => { state.activeTab = tab; state.persist(); },
    });
    tabs.show(state.activeTab, false);
    const calendarCell = new window.FlzCalendar.components.CalendarCell();
    const weekTable = new window.FlzCalendar.components.WeekTable({
        container: elements['calendar-tables'], calendarCell,
        organization: () => organization,
    });
    let entryWorkflow;
    const entryDialog = new window.FlzCalendar.components.EntryDialog({
        entries: () => state.data?.entries || [],
        shiftDefaults: () => state.data?.shiftDefaults || {},
        onSubmit: data => entryWorkflow.save(data),
    });
    const meetingFinder = new window.FlzCalendar.components.MeetingFinder({
        repository,
        onError: error => show(error, true),
        onBlocked: async () => { await load(); show(l10n.t('The meeting was blocked for all selected people.')); },
    });
    const shiftDefaults = new window.FlzCalendar.components.ShiftDefaults({ onSave: saveShiftDefaults });
    const shiftCalendarSync = new window.FlzCalendar.components.ShiftCalendarSync({ onSave: saveCalendarSync });
    const externalCalendars = new window.FlzCalendar.components.ExternalCalendars({ repository, onMessage: show });
    const calendarFilters = new window.FlzCalendar.components.CalendarFilters({
        state,
        organization: () => organization,
        leadershipStaffRoles,
        onChange: renderTable,
    });
    const weekNavigation = new window.FlzCalendar.components.WeekNavigation({
        state,
        onWeekChange: load,
        onViewChange: renderTable,
        onPeriodChange: load,
    });
    entryWorkflow = new window.FlzCalendar.modules.EntryWorkflow({
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
        const meetingButton = document.getElementById('flz-calendar-open-meeting-finder');
        meetingButton.disabled = state.period === 'month';
        meetingButton.title = state.period === 'month' ? l10n.t('Meeting gap search is available in the weekly view.') : '';
    }

    async function saveShiftDefaults(defaults) {
        try {
            const response = await repository.saveShiftDefaults(defaults);
            state.data.shiftDefaults = response.shiftDefaults;
            shiftDefaults.set(response.shiftDefaults);
            await load();
            show(l10n.t('Personal default shift times saved.'));
        } catch (error) { show(error, true); }
    }

    async function saveCalendarSync(enabled) {
        try {
            const response = await repository.saveCalendarSync(enabled);
            state.data.calendarSync = response.calendarSync;
            shiftCalendarSync.set(response.calendarSync);
            show(enabled
                ? l10n.t('The private calendar “{calendar}” is enabled.', { calendar: response.calendarSync.calendarName || 'Filzmann Dienste' })
                : l10n.t('Private shift calendar synchronisation is disabled.'));
        } catch (error) { show(error, true); }
    }

    function applyOrganization(data) {
        organization = OrganizationModel.get(data);
        leadershipStaffRoles.clear();
        for (const group of organization.leadershipStaffRoleGroups()) leadershipStaffRoles.add(group);
    }

    function renderTable() {
        const employees = state.availableEmployees();
        elements['filter-status'].textContent = state.selected.size
            ? l10n.n('{count} selected', '{count} selected', employees.length, { count: employees.length })
            : state.isUnfiltered() ? l10n.t('All people') : l10n.n('{count} filtered', '{count} filtered', employees.length, { count: employees.length });
        weekTable.render(employees, state);
    }

    async function load() {
        const sequence = ++loadSequence;
        const visibleRange = state.visibleRange();
        const requestRange = state.period === 'month'
            ? CalendarDate.completeWeekRange(visibleRange.start, visibleRange.end)
            : visibleRange;
        const range = { start: CalendarDate.isoDay(requestRange.start), end: CalendarDate.isoDay(requestRange.end) };
        weekNavigation.render();
        if (state.data) renderTable();
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

    document.getElementById('flz-calendar-open-meeting-finder').addEventListener('click', () => meetingFinder.open(CalendarDate.isoDay(state.monday), state.data.employees, [...state.selected]));
    document.getElementById('flz-calendar-back-to-top').addEventListener('click', () => {
        document.getElementById('flzcalendar-app').scrollTo({ top: 0, behavior: 'smooth' });
    });
    document.getElementById('flz-calendar-save-default').addEventListener('click', async () => {
        try {
            await repository.savePreferences(state.toPreference());
            show(l10n.t('The current filters and view were made your personal default.'));
        } catch (error) { show(error, true); }
    });
    externalCalendars.load();
    const connectionResult = new URLSearchParams(window.location.search).get('calendarConnection');
    if (connectionResult === 'google-connected') show(l10n.t('Google Calendar was connected.'));
    else if (connectionResult === 'google-error') show(l10n.t('The Google connection could not be completed.'), true);
    load();
}());
