(function() {
    'use strict';

    /** Zweck: Bindet die Kalender-/Einstellungs-Tabs unabhängig von der weiteren App-Initialisierung. */
    class TabNavigation {
        constructor(options) {
            this.calendarButton = options.calendarButton;
            this.settingsButton = options.settingsButton;
            this.calendarPanel = options.calendarPanel;
            this.settingsPanel = options.settingsPanel;
            this.onChange = options.onChange;
            this.calendarButton.addEventListener('click', () => this.show('calendar'));
            this.settingsButton.addEventListener('click', () => this.show('settings'));
        }

        show(tab, notify = true) {
            const active = tab === 'settings' ? 'settings' : 'calendar';
            const calendar = active === 'calendar';
            this.calendarPanel.hidden = !calendar;
            this.settingsPanel.hidden = calendar;
            this.calendarButton.setAttribute('aria-selected', String(calendar));
            this.settingsButton.setAttribute('aria-selected', String(!calendar));
            if (notify) this.onChange(active);
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.components = window.FlzCalendar.components || {};
    window.FlzCalendar.components.TabNavigation = TabNavigation;
})();
