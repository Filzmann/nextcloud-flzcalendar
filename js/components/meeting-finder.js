(function() {
    'use strict';
    const CalendarDate = window.FlzCalendar.modules.CalendarDate;
    const l10n = window.FlzCalendar.l10n;

    /**
     * Zweck: Kapselt Personenauswahl, wochenweise Lückensuche und gemeinsame Terminblockierung.
     * Vertrag: Eine Blockierung wird nur angeboten, wenn der Server alle ausgewählten Kalender zur Bearbeitung freigibt.
     */
    class MeetingFinder {
        constructor(options) {
            this.repository = options.repository;
            this.onError = options.onError;
            this.onBlocked = options.onBlocked || (() => {});
            this.dialog = document.getElementById('flz-calendar-meeting-dialog');
            this.form = document.getElementById('flz-calendar-meeting-form');
            this.search = document.getElementById('flz-calendar-meeting-search');
            this.people = document.getElementById('flz-calendar-meeting-people');
            this.duration = document.getElementById('flz-calendar-meeting-duration');
            this.title = document.getElementById('flz-calendar-meeting-title');
            this.results = document.getElementById('flz-calendar-meeting-results');
            this.week = document.getElementById('flz-calendar-meeting-week');
            this.employees = [];
            this.selected = new Set();
            this.start = '';
            this.returnFocus = null;
            document.getElementById('flz-calendar-meeting-close').addEventListener('click', () => this.close());
            document.getElementById('flz-calendar-meeting-cancel').addEventListener('click', () => this.close());
            this.dialog.addEventListener('cancel', event => { event.preventDefault(); this.close(); });
            this.search.addEventListener('input', () => this.renderPeople());
            this.form.addEventListener('submit', event => this.submit(event));
        }

        open(start, employees, selected = []) {
            this.returnFocus = document.activeElement;
            this.start = start;
            this.employees = employees;
            this.search.value = '';
            this.results.replaceChildren();
            this.selected = new Set(selected);
            this.renderPeople();
            this.renderWeek();
            this.dialog.showModal();
            this.search.focus();
        }

        close() {
            this.dialog.close();
            const returnFocus = this.returnFocus;
            this.returnFocus = null;
            if (returnFocus?.isConnected !== false && typeof returnFocus?.focus === 'function') returnFocus.focus();
        }

        renderWeek() {
            this.week.textContent = l10n.t('Calendar week from {date}', { date: l10n.date(new Date(`${this.start}T12:00:00`)) });
        }

        renderPeople() {
            const query = l10n.lower(this.search.value.trim());
            this.people.replaceChildren(...this.employees.filter(employee => !query || l10n.lower(employee.displayName).includes(query)).map(employee => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox'; input.value = employee.uid; input.checked = this.selected.has(employee.uid);
                input.addEventListener('change', () => {
                    input.checked ? this.selected.add(employee.uid) : this.selected.delete(employee.uid);
                    this.results.replaceChildren();
                });
                label.append(input, document.createTextNode(` ${employee.displayName}`));
                return label;
            }));
        }

        async submit(event) {
            event.preventDefault();
            await this.searchWeek();
        }

        async searchWeek() {
            const employeeUids = [...this.selected];
            const durationMinutes = Number(this.duration.value);
            if (employeeUids.length < 2) {
                this.results.textContent = l10n.t('Please select at least two people.');
                return;
            }
            if (!Number.isInteger(durationMinutes) || durationMinutes < 15 || durationMinutes > 480) {
                this.results.textContent = l10n.t('Please select a duration between 15 and 480 minutes.');
                return;
            }
            try {
                const response = await this.repository.meetingGaps(this.start, employeeUids, durationMinutes);
                this.renderResults(response.gaps || [], Boolean(response.canBlockAll));
            } catch (error) {
                this.onError(error);
            }
        }

        renderResults(gaps, canBlockAll) {
            if (!gaps.length) {
                this.renderNoResults();
                return;
            }
            const heading = document.createElement('h3'); heading.textContent = l10n.n('{count} matching gap', '{count} matching gaps', gaps.length, { count: gaps.length });
            const list = document.createElement('ul');
            list.className = 'flz-calendar-meeting-gap-list';
            for (const gap of gaps) {
                const start = new Date(gap.start);
                const end = new Date(start.getTime() + Number(this.duration.value) * 60000);
                const item = document.createElement('li');
                const label = document.createElement('span');
                label.textContent = `${l10n.date(start, { weekday: 'short', day: '2-digit', month: '2-digit' })}, ${this.time(start)}–${this.time(end)}`;
                item.append(label);
                if (canBlockAll) {
                    const block = document.createElement('button');
                    block.type = 'button'; block.textContent = l10n.t('Block for everyone');
                    block.addEventListener('click', () => this.block(start, end, block));
                    item.append(block);
                }
                list.append(item);
            }
            const nodes = [heading, list];
            if (!canBlockAll) {
                const note = document.createElement('p');
                note.textContent = l10n.t('Direct blocking is only possible if you may edit every selected calendar.');
                nodes.push(note);
            }
            this.results.replaceChildren(...nodes);
        }

        renderNoResults() {
            const message = document.createElement('p');
            message.textContent = l10n.t('No matching common gap was found in this calendar week.');
            const actions = document.createElement('div');
            actions.className = 'flz-calendar-meeting-result-actions';
            const nextWeek = document.createElement('button');
            nextWeek.type = 'button'; nextWeek.textContent = l10n.t('Search in the next week');
            nextWeek.addEventListener('click', async () => {
                const date = new Date(`${this.start}T12:00:00`); date.setDate(date.getDate() + 7);
                this.start = CalendarDate.isoDay(date); this.renderWeek(); await this.searchWeek();
            });
            actions.append(nextWeek);
            for (const uid of this.selected) {
                const employee = this.employees.find(item => item.uid === uid);
                if (!employee) continue;
                const remove = document.createElement('button');
                remove.type = 'button'; remove.textContent = l10n.t('Deselect {employee}', { employee: employee.displayName });
                remove.addEventListener('click', async () => {
                    this.selected.delete(uid); this.renderPeople();
                    if (this.selected.size >= 2) await this.searchWeek();
                    else this.results.textContent = l10n.t('Please select at least two people.');
                });
                actions.append(remove);
            }
            this.results.replaceChildren(message, actions);
        }

        async block(start, end, button) {
            const title = this.title.value.trim();
            if (!title) {
                this.title.setCustomValidity(l10n.t('Please enter a title for the block.'));
                this.title.reportValidity();
                return;
            }
            this.title.setCustomValidity('');
            button.disabled = true;
            try {
                await this.repository.blockMeeting(start.toISOString(), end.toISOString(), [...this.selected], title);
                this.results.textContent = l10n.t('The appointment was blocked for all selected people.');
                await this.onBlocked();
            } catch (error) {
                button.disabled = false;
                this.onError(error);
            }
        }

        time(date) { return l10n.time(date); }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.components = window.FlzCalendar.components || {};
    window.FlzCalendar.components.MeetingFinder = MeetingFinder;
})();
