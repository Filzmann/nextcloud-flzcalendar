(function() {
    'use strict';
    const l10n = window.AdCalendar.l10n;

    /** Zweck: Bedient den standardmäßig aktiven persönlichen Abgleich und dessen Opt-out für „AD Dienste“. */
    class ShiftCalendarSync {
        constructor(options) {
            this.form = document.getElementById('adc-calendar-sync-form');
            this.input = document.getElementById('adc-calendar-sync-enabled');
            this.status = document.getElementById('adc-calendar-sync-status');
            this.onSave = options.onSave;
            this.form.addEventListener('submit', event => this.submit(event));
        }

        set(status) {
            this.input.checked = Boolean(status.enabled);
            const name = status.calendarName || 'AD Dienste';
            this.status.textContent = status.enabled
                ? l10n.t('Calendar is active: {calendar}.', { calendar: name })
                : l10n.t('Calendar is not active: {calendar}.', { calendar: name });
        }

        async submit(event) {
            event.preventDefault();
            if (this.form.reportValidity()) await this.onSave(this.input.checked);
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.ShiftCalendarSync = ShiftCalendarSync;
})();
