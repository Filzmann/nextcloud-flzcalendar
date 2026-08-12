(function() {
    'use strict';

    /** Zweck: Stellt die app-weit identischen Berechnungen für lokale Kalendertage und ISO-Wochen zentral bereit. */
    class CalendarDate {
        static isoDay(value) {
            const year = value.getFullYear();
            const month = String(value.getMonth() + 1).padStart(2, '0');
            const day = String(value.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        static startOfWeek(value) {
            const result = new Date(value);
            const weekday = result.getDay() || 7;
            result.setDate(result.getDate() - weekday + 1);
            result.setHours(0, 0, 0, 0);
            return result;
        }

        static startOfMonth(value) {
            const result = new Date(value);
            result.setDate(1);
            result.setHours(0, 0, 0, 0);
            return result;
        }

        static monthValue(value) {
            return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}`;
        }

        static monthRange(value) {
            const month = this.startOfMonth(value);
            const leadingDays = Math.min((month.getDay() || 7) - 1, 3);
            const start = new Date(month);
            start.setDate(start.getDate() - leadingDays);
            const afterMonth = new Date(month);
            afterMonth.setMonth(afterMonth.getMonth() + 1);
            const trailingDays = Math.min((8 - (afterMonth.getDay() || 7)) % 7, 3);
            const end = new Date(afterMonth);
            end.setDate(end.getDate() + trailingDays);
            const weeks = [];
            for (let week = new Date(start); week < end; week.setDate(week.getDate() + 7)) weeks.push(new Date(week));
            return { start, end, weeks };
        }

        static completeWeekRange(startValue, endValue) {
            const start = this.startOfWeek(startValue);
            const end = new Date(endValue);
            const weekday = end.getDay() || 7;
            if (weekday !== 1) end.setDate(end.getDate() + 8 - weekday);
            end.setHours(0, 0, 0, 0);
            return { start, end };
        }

        static isoWeekValue(date) {
            const value = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
            const day = value.getUTCDay() || 7;
            value.setUTCDate(value.getUTCDate() + 4 - day);
            const yearStart = new Date(Date.UTC(value.getUTCFullYear(), 0, 1));
            const week = Math.ceil((((value - yearStart) / 86400000) + 1) / 7);
            return `${value.getUTCFullYear()}-W${String(week).padStart(2, '0')}`;
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.modules = window.AdCalendar.modules || {};
    window.AdCalendar.modules.CalendarDate = CalendarDate;
})();
