(function() {
    'use strict';
    const l10n = window.FlzCalendar.l10n;

    /** Zweck: Bedient den standardmäßig aktiven persönlichen Abgleich und dessen Opt-out für „Filzmann Dienste“. */
    class ShiftCalendarSync {
        constructor(options) {
            this.form = document.getElementById('flz-calendar-calendar-sync-form');
            this.input = document.getElementById('flz-calendar-calendar-sync-enabled');
            this.status = document.getElementById('flz-calendar-calendar-sync-status');
            this.onSave = options.onSave;
            this.form.addEventListener('submit', event => this.submit(event));
        }

        set(status) {
            this.input.checked = Boolean(status.enabled);
            const name = status.calendarName || 'Filzmann Dienste';
            this.status.textContent = status.enabled
                ? l10n.t('Calendar is active: {calendar}.', { calendar: name })
                : l10n.t('Calendar is not active: {calendar}.', { calendar: name });
        }

        async submit(event) {
            event.preventDefault();
            if (this.form.reportValidity()) await this.onSave(this.input.checked);
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.components = window.FlzCalendar.components || {};
    window.FlzCalendar.components.ShiftCalendarSync = ShiftCalendarSync;
})();
