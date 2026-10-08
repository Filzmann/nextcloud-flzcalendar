(function() {
    'use strict';

    /**
     * Zweck: Leitet für zusammengehörige Meetingeinträge die gemeinsame UI-Bearbeitbarkeit ab.
     * Zusammenspiel: main.js wendet den Resolver nach der Modell-Hydration an; EntryWorkflow berücksichtigt das Ergebnis bei Aktionen.
     * Vertrag: Ein Meeting ist in der UI nur gemeinsam bearbeitbar, wenn alle beteiligten Personen canManage besitzen. Die Server-Policy bleibt autoritativ.
     */
    class MeetingCapabilities {
        apply(entries, employees) {
            const employeesByUid = new Map(employees.map(employee => [employee.uid, employee]));
            const meetings = new Map();
            for (const entry of entries) {
                if (!entry.meetingUid) continue;
                if (!meetings.has(entry.meetingUid)) meetings.set(entry.meetingUid, []);
                meetings.get(entry.meetingUid).push(entry);
            }
            for (const meetingEntries of meetings.values()) {
                const canManage = meetingEntries.every(entry => employeesByUid.get(entry.employeeUid)?.canManage === true);
                for (const entry of meetingEntries) entry.canManageMeeting = canManage;
            }
            return entries;
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.modules = window.FlzCalendar.modules || {};
    window.FlzCalendar.modules.MeetingCapabilities = MeetingCapabilities;
})();
