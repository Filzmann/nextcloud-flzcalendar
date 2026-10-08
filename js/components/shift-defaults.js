(function() {
    'use strict';
    const l10n = window.FlzCalendar.l10n;

    /** Zweck: Rendert und sammelt die persönlichen Standard-Dienstzeiten je Wochentag. */
    class ShiftDefaults {
        constructor(options) {
            this.container = document.getElementById('flz-calendar-shift-defaults');
            this.form = document.getElementById('flz-calendar-shift-defaults-form');
            this.onSave = options.onSave;
            this.weekdays = ['',
                l10n.t('Monday'), l10n.t('Tuesday'), l10n.t('Wednesday'), l10n.t('Thursday'),
                l10n.t('Friday'), l10n.t('Saturday'), l10n.t('Sunday'),
            ];
            this.form.addEventListener('submit', event => this.submit(event));
        }

        set(defaults) {
            this.container.replaceChildren(...Array.from({ length: 7 }, (_, index) => this.row(index + 1, defaults[String(index + 1)] || {})));
        }

        row(weekday, value) {
            const row = document.createElement('div'); row.className = 'flz-calendar-shift-default-row'; row.dataset.weekday = String(weekday);
            const enabledLabel = document.createElement('label'); enabledLabel.className = 'flz-calendar-shift-default-day';
            const enabled = document.createElement('input'); enabled.type = 'checkbox'; enabled.checked = value.enabled !== false; enabled.dataset.field = 'enabled';
            enabledLabel.append(enabled, document.createTextNode(` ${this.weekdays[weekday]}`));
            const start = this.timeField('Beginn', 'start', value.start || '08:00');
            const end = this.timeField('Ende', 'end', value.end || '16:00');
            const update = () => { start.input.disabled = !enabled.checked; end.input.disabled = !enabled.checked; };
            enabled.addEventListener('change', update); update();
            row.append(enabledLabel, start.label, end.label);
            return row;
        }

        timeField(text, field, value) {
            const label = document.createElement('label'); label.append(document.createTextNode(`${text} `));
            const input = document.createElement('input'); input.type = 'time'; input.required = true; input.value = value; input.dataset.field = field;
            label.append(input); return { label, input };
        }

        collect() {
            return Object.fromEntries([...this.container.querySelectorAll('[data-weekday]')].map(row => [row.dataset.weekday, {
                enabled: row.querySelector('[data-field="enabled"]').checked,
                start: row.querySelector('[data-field="start"]').value,
                end: row.querySelector('[data-field="end"]').value,
            }]));
        }

        async submit(event) { event.preventDefault(); if (this.form.reportValidity()) await this.onSave(this.collect()); }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.components = window.FlzCalendar.components || {};
    window.FlzCalendar.components.ShiftDefaults = ShiftDefaults;
})();
