(function() {
    'use strict';

    const BaseModel = window.LocalBase.models.Model;

    /**
     * Zweck: Hydriert den serverseitigen CalendarEntry-Vertrag für Komponenten und Workflows.
     * Spiegelung: PHP: OCA\FlzCalendar\Model\CalendarEntry::toArray().
     */
    class CalendarEntry extends BaseModel {
        constructor(data = {}) {
            super();
            this.id = data.id == null ? null : Number(data.id);
            this.employeeUid = String(data.employeeUid || '');
            this.start = String(data.start || '');
            this.end = String(data.end || '');
            this.type = String(data.type || '');
            this.title = String(data.title || '');
            this.parentEntryId = data.parentEntryId == null ? null : Number(data.parentEntryId);
            this.meetingUid = data.meetingUid == null ? null : String(data.meetingUid);
            this.seriesUid = data.seriesUid == null ? null : String(data.seriesUid);
            this.seriesTimezone = data.seriesTimezone == null ? null : String(data.seriesTimezone);
            this.canManageMeeting = data.canManageMeeting !== false;
            this.isBlocked = Boolean(data.isBlocked);
            this.defaultDate = data.defaultDate == null ? null : String(data.defaultDate);
            this.defaultModified = Boolean(data.defaultModified);
            this.defaultDeleted = Boolean(data.defaultDeleted);
        }

        toArray() {
            return {
                id: this.id,
                employeeUid: this.employeeUid,
                start: this.start,
                end: this.end,
                type: this.type,
                title: this.title,
                parentEntryId: this.parentEntryId,
                meetingUid: this.meetingUid,
                seriesUid: this.seriesUid,
                seriesTimezone: this.seriesTimezone,
                isBlocked: this.isBlocked,
                defaultDate: this.defaultDate,
                defaultModified: this.defaultModified,
                defaultDeleted: this.defaultDeleted,
            };
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.models = window.FlzCalendar.models || {};
    window.FlzCalendar.models.CalendarEntry = CalendarEntry;
})();
