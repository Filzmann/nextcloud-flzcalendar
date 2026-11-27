(function() {
    'use strict';
    const l10n = window.FlzCalendar.l10n;

    /**
     * Zweck: Koordiniert Anlegen, Bearbeiten und Löschen von Kalender- und gemeinsamen Meetingeinträgen.
     * Zusammenspiel: CalendarCell liefert Aktionen, EntryDialog erfasst Daten, CalendarRepository persistiert und main.js lädt anschließend neu.
     * Vertrag: canManage steuert nur die Bedienoberfläche. Repository-Endpunkte und Server-Policies bleiben die maßgebliche Berechtigungsgrenze.
     */
    class EntryWorkflow {
        constructor(options) {
            this.repository = options.repository;
            this.state = options.state;
            this.dialog = options.dialog;
            this.show = options.show;
            this.reload = options.reload;
            options.body.addEventListener('click', event => this.handleClick(event));
        }

        async save(data) {
            try {
                const existing = this.state.data.entries.find(entry => entry.id === Number(data.id));
                let message = l10n.t('Eintrag gespeichert.');
                if (existing?.meetingUid) {
                    if (!existing.canManageMeeting) throw new Error(l10n.t('Das gemeinsame Meeting darf nur bearbeitet werden, wenn alle beteiligten Kalender bearbeitet werden dürfen.'));
                    await this.repository.updateMeeting(existing.meetingUid, data.start, data.end, data.title);
                    message = l10n.t('Meeting für alle Beteiligten gespeichert.');
                } else {
                    const seriesScope = existing?.seriesUid ? await this.seriesChoice('edit') : 'occurrence';
                    if (seriesScope === null) return;
                    const result = await this.repository.save({
                        employeeUid: data.employeeUid,
                        type: data.type,
                        start: data.start,
                        end: data.end,
                        title: data.title,
                        recurrenceFrequency: data.recurrenceFrequency,
                        recurrenceInterval: data.recurrenceInterval,
                        recurrenceUntil: data.recurrenceUntil,
                        recurrenceWeekdays: data.recurrenceWeekdays,
                        recurrenceTimezone: data.recurrenceTimezone,
                    }, data.id, seriesScope);
                    if (result?.seriesCount > 1) message = l10n.n('{count} Serientermin gespeichert.', '{count} Serientermine gespeichert.', result.seriesCount, { count: result.seriesCount });
                    else if (existing?.seriesUid) message = l10n.t('Serientermin gespeichert.');
                }
                this.dialog.close();
                this.show(message);
                await this.reload();
            } catch (error) {
                this.show(error, true);
            }
        }

        async remove(entry) {
            if (entry.meetingUid) {
                await this.removeMeeting(entry);
                return;
            }
            let mode = '';
            const seriesScope = entry.seriesUid ? await this.seriesChoice('delete') : 'occurrence';
            if (seriesScope === null) return;
            if (entry.type === 'shift') {
                try {
                    await this.repository.remove(entry.id);
                    this.show(l10n.t('Dienst gelöscht.'));
                    await this.reload();
                    return;
                } catch (error) {
                    if (error.status !== 409 || !error.data?.confirmationRequired) {
                        this.show(error, true);
                        return;
                    }
                    mode = await this.deletionChoice(error.data.children.length);
                }
                if (mode === null) return;
            } else if (!entry.seriesUid && !window.confirm(l10n.t('Termin wirklich löschen?'))) {
                return;
            }
            try {
                await this.repository.remove(entry.id, mode, seriesScope);
                this.show(seriesScope === 'series' ? l10n.t('Serientermin gelöscht.') : mode === 'detach' ? l10n.t('Dienst gelöscht; Termine sind jetzt Sperrtermine.') : l10n.t('Eintrag gelöscht.'));
                await this.reload();
            } catch (error) {
                this.show(error, true);
            }
        }

        async removeMeeting(entry) {
            if (!entry.canManageMeeting) {
                this.show(l10n.t('Das gemeinsame Meeting darf nur gelöscht werden, wenn alle beteiligten Kalender bearbeitet werden dürfen.'), true);
                return;
            }
            if (!window.confirm(l10n.t('Meeting wirklich für alle Beteiligten löschen?'))) return;
            try {
                await this.repository.removeMeeting(entry.meetingUid);
                this.show(l10n.t('Meeting für alle Beteiligten gelöscht.'));
                await this.reload();
            } catch (error) {
                this.show(error, true);
            }
        }

        handleClick(event) {
            const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
            if (!button) return;
            const cell = button.closest('[data-employee-uid][data-day]');
            if (!cell) return;
            const employee = this.state.data.employees.find(item => item.uid === cell.dataset.employeeUid);
            if (!employee?.canManage) return;
            if (button.dataset.action === 'add-entry') {
                this.dialog.open({ employee, day: new Date(`${cell.dataset.day}T12:00:00`), type: button.dataset.entryType });
                return;
            }
            const entry = this.state.data.entries.find(item => item.id === Number(button.dataset.entryId));
            if (!entry) return;
            if (button.dataset.action === 'edit-entry') this.dialog.open({ employee, day: new Date(entry.start), type: entry.type, entry });
            if (button.dataset.action === 'delete-entry') void this.remove(entry);
        }

        deletionChoice(count) {
            return new Promise(resolve => {
                const returnFocus = document.activeElement;
                const dialog = document.createElement('dialog');
                dialog.className = 'flz-calendar-dialog flz-calendar-delete-dialog';
                dialog.setAttribute('aria-labelledby', 'flz-calendar-delete-title');
                let settled = false;
                const finish = value => {
                    if (settled) return;
                    settled = true;
                    dialog.close();
                    dialog.remove();
                    if (returnFocus?.isConnected !== false && typeof returnFocus?.focus === 'function') returnFocus.focus();
                    resolve(value);
                };
                const title = this.node('h2', l10n.t('Dienst mit Terminen löschen'));
                title.id = 'flz-calendar-delete-title';
                dialog.append(title, this.node('p', l10n.n('Der Dienst enthält {count} Termin. Was soll damit geschehen?', 'Der Dienst enthält {count} Termine. Was soll damit geschehen?', count, { count })));
                [['delete', l10n.t('Dienst und Termine löschen')], ['detach', l10n.t('Nur Dienst löschen; Termine als Sperrtermine behalten')], [null, l10n.t('Abbrechen')]]
                    .forEach(([value, label]) => {
                        const button = this.node('button', label);
                        button.type = 'button';
                        button.addEventListener('click', () => finish(value));
                        dialog.append(button);
                    });
                dialog.addEventListener('cancel', event => { event.preventDefault(); finish(null); });
                document.body.append(dialog);
                dialog.showModal();
            });
        }

        seriesChoice(action) {
            return new Promise(resolve => {
                const returnFocus = document.activeElement;
                const dialog = document.createElement('dialog');
                dialog.className = 'flz-calendar-dialog flz-calendar-delete-dialog';
                dialog.setAttribute('aria-labelledby', 'flz-calendar-series-scope-title');
                let settled = false;
                const finish = value => {
                    if (settled) return;
                    settled = true;
                    dialog.close();
                    dialog.remove();
                    if (returnFocus?.isConnected !== false && typeof returnFocus?.focus === 'function') returnFocus.focus();
                    resolve(value);
                };
                const title = this.node('h2', action === 'edit' ? l10n.t('Serientermin bearbeiten') : l10n.t('Serientermin löschen'));
                title.id = 'flz-calendar-series-scope-title';
                dialog.append(title, this.node('p', action === 'edit' ? l10n.t('Soll nur dieses Vorkommen oder die gesamte Serie bearbeitet werden?') : l10n.t('Soll nur dieses Vorkommen oder die gesamte Serie gelöscht werden?')));
                [['occurrence', l10n.t('Nur dieses Vorkommen')], ['series', l10n.t('Gesamte Serie')], [null, l10n.t('Abbrechen')]]
                    .forEach(([value, label]) => {
                        const button = this.node('button', label);
                        button.type = 'button';
                        button.addEventListener('click', () => finish(value));
                        dialog.append(button);
                    });
                dialog.addEventListener('cancel', event => { event.preventDefault(); finish(null); });
                document.body.append(dialog);
                dialog.showModal();
            });
        }

        node(tag, value) {
            const result = document.createElement(tag);
            result.textContent = value;
            return result;
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.modules = window.FlzCalendar.modules || {};
    window.FlzCalendar.modules.EntryWorkflow = EntryWorkflow;
})();
