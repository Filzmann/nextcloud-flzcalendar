(function() {
    'use strict';

    const BaseRepository = window.LocalBase.repositories.Repository;

    /** Zweck: Kapselt alle Filzmann-Kalender-API-Pfade hinter dem gemeinsamen LocalBase-Client. */
    class CalendarRepository extends BaseRepository {
        week(start) {
            return this.request(`/api/week?start=${this.encode(start)}`);
        }

        range(start, end) {
            return this.request(`/api/range?start=${this.encode(start)}&end=${this.encode(end)}`);
        }

        save(entry, id = null, seriesScope = 'occurrence') {
            return this.request(id == null ? '/api/entries' : `/api/entries/${this.encode(id)}`, {
                method: id == null ? 'POST' : 'PUT',
                body: JSON.stringify(id == null ? entry : { ...entry, seriesScope }),
            });
        }

        remove(id, childMode = '', seriesScope = 'occurrence') {
            return this.request(`/api/entries/${this.encode(id)}`, {
                method: 'DELETE',
                body: JSON.stringify({ childMode, seriesScope }),
            });
        }

        savePreferences(filters) {
            return this.request('/api/preferences', { method: 'PUT', body: JSON.stringify({ filters }) });
        }

        saveShiftDefaults(shiftDefaults) {
            return this.request('/api/preferences/shifts', { method: 'PUT', body: JSON.stringify({ shiftDefaults }) });
        }

        saveCalendarSync(enabled) {
            return this.request('/api/preferences/calendar-sync', { method: 'PUT', body: JSON.stringify({ enabled }) });
        }

        externalCalendars() {
            return this.request('/api/external-calendars');
        }

        connectCalDav(provider, serverUrl, username, password) {
            return this.request('/api/external-calendars/caldav', {
                method: 'POST', body: JSON.stringify({ provider, serverUrl, username, password }),
            });
        }

        disconnectExternalCalendar(provider) {
            return this.request(`/api/external-calendars/${this.encode(provider)}`, { method: 'DELETE' });
        }

        startGoogleCalendarConnection() {
            return this.request('/api/external-calendars/google/start', { method: 'POST' });
        }

        meetingGaps(start, employeeUids, durationMinutes) {
            return this.request('/api/meeting-gaps', {
                method: 'POST',
                body: JSON.stringify({ start, employeeUids, durationMinutes }),
            });
        }

        blockMeeting(start, end, employeeUids, title) {
            return this.request('/api/meetings', {
                method: 'POST',
                body: JSON.stringify({ start, end, employeeUids, title }),
            });
        }

        updateMeeting(meetingUid, start, end, title) {
            return this.request(`/api/meetings/${this.encode(meetingUid)}`, {
                method: 'PUT',
                body: JSON.stringify({ start, end, title }),
            });
        }

        removeMeeting(meetingUid) {
            return this.request(`/api/meetings/${this.encode(meetingUid)}`, { method: 'DELETE' });
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.repositories = window.FlzCalendar.repositories || {};
    window.FlzCalendar.repositories.CalendarRepository = CalendarRepository;
})();
