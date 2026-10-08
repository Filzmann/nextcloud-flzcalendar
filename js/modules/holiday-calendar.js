(function () {
    'use strict';

    /** Datengetriebener read-only Lookup der vom gemeinsamen LocalBase-Vertrag gelieferten Feiertage. */
    class HolidayCalendar {
        constructor(calendars = []) {
            this.names = new Map();
            this.set(calendars);
        }

        set(calendars) {
            this.names.clear();
            for (const calendar of Array.isArray(calendars) ? calendars : []) {
                for (const holiday of Array.isArray(calendar?.publicHolidays) ? calendar.publicHolidays : []) {
                    const name = String(holiday?.name || '').trim();
                    const start = this.date(holiday?.startDate);
                    const end = this.date(holiday?.endDate);
                    if (!name || !start || !end || end < start) continue;
                    for (let day = start; day <= end; day = this.addDay(day)) this.names.set(day, name);
                }
            }
        }

        name(date) {
            return this.names.get(this.isoDay(date)) || '';
        }

        date(value) {
            if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return '';
            const parsed = new Date(`${value}T12:00:00Z`);
            return Number.isNaN(parsed.getTime()) || parsed.toISOString().slice(0, 10) !== value ? '' : value;
        }

        addDay(value) {
            const date = new Date(`${value}T12:00:00Z`);
            date.setUTCDate(date.getUTCDate() + 1);
            return date.toISOString().slice(0, 10);
        }

        isoDay(date) {
            const pad = value => String(value).padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.modules = window.FlzCalendar.modules || {};
    window.FlzCalendar.modules.HolidayCalendar = HolidayCalendar;
}());
