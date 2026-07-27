(function() {
    'use strict';
    const CalendarDate = window.AdCalendar.modules.CalendarDate;

    /**
     * Zweck: Steuert Woche, Monat und Orientierung der Kalendermatrix als zusammengehörige Navigation.
     * Zusammenspiel: CalendarState persistiert die gewählte Ansicht; main.js lädt nach einem Zeitraumwechsel neue Daten.
     * Vertrag: Jede Navigationsaktion aktualisiert zuerst Zustand, URL und Beschriftung und ruft danach den passenden Callback auf.
     */
    class WeekNavigation {
        constructor(options) {
            this.state = options.state;
            this.onWeekChange = options.onWeekChange;
            this.onViewChange = options.onViewChange;
            this.onPeriodChange = options.onPeriodChange || options.onWeekChange;
            this.label = document.getElementById('adc-week-label');
            this.weekNumber = document.getElementById('adc-week-number');
            this.monthNumber = document.getElementById('adc-month-number');
            this.weekPicker = document.getElementById('adc-week-picker');
            this.monthPicker = document.getElementById('adc-month-picker');
            this.previous = document.getElementById('adc-previous-period');
            this.next = document.getElementById('adc-next-period');
            this.weekButton = document.getElementById('adc-period-week');
            this.monthButton = document.getElementById('adc-period-month');
            this.toggleView = document.getElementById('adc-toggle-view');
            this.heading = document.getElementById('adc-overview-heading');
            this.previous.addEventListener('click', () => this.state.period === 'month' ? this.moveMonth(-1) : this.move(-7));
            this.next.addEventListener('click', () => this.state.period === 'month' ? this.moveMonth(1) : this.move(7));
            this.weekNumber.addEventListener('change', event => this.select(event.target.value));
            this.monthNumber.addEventListener('change', event => this.selectMonth(event.target.value));
            this.weekButton.addEventListener('click', () => this.setPeriod('week'));
            this.monthButton.addEventListener('click', () => this.setPeriod('month'));
            this.toggleView.addEventListener('click', () => this.toggle());
        }

        render() {
            const isMonth = this.state.period === 'month';
            const sunday = new Date(this.state.monday);
            sunday.setDate(sunday.getDate() + 6);
            this.label.textContent = isMonth
                ? this.state.month.toLocaleDateString('de-DE', { month: 'long', year: 'numeric' })
                : `${this.state.monday.toLocaleDateString('de-DE')} – ${sunday.toLocaleDateString('de-DE')}`;
            this.weekNumber.value = CalendarDate.isoWeekValue(this.state.monday);
            this.monthNumber.value = CalendarDate.monthValue(this.state.month);
            this.weekPicker.hidden = isMonth;
            this.monthPicker.hidden = !isMonth;
            this.toggleView.hidden = false;
            this.weekButton.setAttribute('aria-pressed', String(!isMonth));
            this.monthButton.setAttribute('aria-pressed', String(isMonth));
            this.previous.textContent = isMonth ? 'Vorheriger Monat' : 'Vorherige Woche';
            this.next.textContent = isMonth ? 'Nächster Monat' : 'Nächste Woche';
            this.heading.textContent = isMonth ? 'Monatsplan' : 'Wochenplan';
            this.toggleView.textContent = this.state.vertical ? 'Tage als Zeilen' : 'Personen als Zeilen';
            this.toggleView.setAttribute('aria-pressed', String(!this.state.vertical));
        }

        move(days) {
            this.state.monday.setDate(this.state.monday.getDate() + days);
            this.changed(this.onWeekChange);
        }

        moveMonth(months) {
            this.state.month.setDate(1);
            this.state.month.setMonth(this.state.month.getMonth() + months);
            this.changed(this.onWeekChange);
        }

        select(value) {
            if (!value) return;
            const [year, week] = value.split('-W').map(Number);
            if (!year || !week) return;
            const januaryFourth = new Date(year, 0, 4);
            this.state.monday = CalendarDate.startOfWeek(januaryFourth);
            this.state.monday.setDate(this.state.monday.getDate() + (week - 1) * 7);
            this.changed(this.onWeekChange);
        }

        selectMonth(value) {
            if (!/^\d{4}-\d{2}$/.test(value || '')) return;
            this.state.month = CalendarDate.startOfMonth(new Date(`${value}-01T12:00:00`));
            this.changed(this.onWeekChange);
        }

        setPeriod(period) {
            if (!['week', 'month'].includes(period) || this.state.period === period) return;
            if (period === 'month') this.state.month = CalendarDate.startOfMonth(this.state.monday);
            else this.state.monday = CalendarDate.startOfWeek(this.state.month);
            this.state.period = period;
            this.changed(this.onPeriodChange || this.onWeekChange);
        }

        toggle() {
            this.state.vertical = !this.state.vertical;
            this.changed(this.onViewChange);
        }

        changed(callback) {
            this.state.persist();
            this.render();
            callback();
        }

    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.WeekNavigation = WeekNavigation;
})();
