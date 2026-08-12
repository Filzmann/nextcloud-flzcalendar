(function() {
    'use strict';

    const { esc } = window.LocalBase.ui;
    const l10n = window.AdCalendar.l10n;

    /**
     * Zweck: Rendert einen kompakten Mitarbeiter-Tag und ordnet Termine sichtbar ihrem Dienst zu.
     * Zusammenspiel: main.js liefert Tagesdaten und bindet die delegierten data-action-Ereignisse.
     */
    class CalendarCell {
        render(entries, employee, absences = [], layout = null, day = null, timeline = null) {
            const shifts = entries.filter(entry => entry.type === 'shift');
            const standalone = entries.filter(entry => entry.type === 'appointment' && entry.parentEntryId === null);
            const hasAbsence = absences.length > 0;
            const actions = this.actions(employee.canManage, employee.canManage && !hasAbsence);
            const markers = this.absenceMarkers(absences);
            const gridStyle = layout ? ` style="grid-template-rows:${layout.rows}"` : '';
            const shiftManageable = employee.canManage && !hasAbsence;
            const shiftEntries = shifts
                .map(shift => this.shift(
                    shift,
                    entries,
                    shiftManageable,
                    this.rowStyle(layout, shift, day, timeline),
                    day,
                ))
                .join('');
            const blockedEntries = standalone
                .map(entry => this.entry(
                    entry,
                    'blocked',
                    employee.canManage,
                    this.rowStyle(layout, entry, day, timeline),
                ))
                .join('');

            return `${markers}${actions}<div class="adc-cell-entries"${gridStyle}>${shiftEntries}${blockedEntries}</div>`;
        }

        actions(canManage, canCreateShift) {
            const addShift = l10n.t('Create shift');
            const addAppointment = l10n.t('Create appointment');
            const buttons = canManage ? `
                ${canCreateShift ? `<button type="button" class="adc-quick-add adc-icon-button icon-add" data-action="add-entry" data-entry-type="shift" data-tooltip="${esc(addShift)}" aria-label="${esc(addShift)}" title="${esc(addShift)}"></button>` : ''}
                <button type="button" class="adc-quick-add adc-icon-button icon-calendar-dark" data-action="add-entry" data-entry-type="appointment" data-tooltip="${esc(addAppointment)}" aria-label="${esc(addAppointment)}" title="${esc(addAppointment)}"></button>` : '';

            return `<div class="adc-cell-actions" aria-label="${esc(l10n.t('Create entry'))}">${buttons}</div>`;
        }

        absenceMarkers(absences) {
            return absences.map(absence => {
                const description = absence.blocks
                    ? l10n.t('Approved absence – shifts are blocked, appointments remain possible')
                    : l10n.t('Planned absence – shifts are blocked, appointments remain possible');

                return `<span class="adc-absence adc-absence--${esc(absence.status)}" title="${esc(description)}" aria-label="${esc(description)}">${esc(absence.marker)}</span>`;
            }).join('');
        }

        shift(shift, entries, canManage, style = '', day = null) {
            const children = entries.filter(entry => entry.type === 'appointment' && entry.parentEntryId === shift.id);
            const childEntries = children.length
                ? `<div class="adc-entry__children" aria-label="${esc(l10n.t('Appointments within the shift'))}">${children.map(entry => this.entry(
                    entry,
                    'appointment',
                    canManage && (!entry.meetingUid || entry.canManageMeeting !== false),
                )).join('')}</div>`
                : '';

            return `<article class="adc-entry adc-entry--shift" data-entry-id="${esc(shift.id)}"${style}>
                ${this.header(shift, l10n.t('Shift'), canManage, false, day)}
                ${childEntries}
            </article>`;
        }

        entry(entry, kind, canManage, style = '') {
            const label = kind === 'blocked' ? l10n.t('Blocked time') : l10n.t('Appointment');
            const manageable = canManage && (!entry.meetingUid || entry.canManageMeeting !== false);
            return `<article class="adc-entry adc-entry--${kind}" data-entry-id="${esc(entry.id)}"${style}>${this.header(entry, label, manageable, kind === 'blocked')}</article>`;
        }

        rowStyle(layout, entry, day, timeline) {
            if (!layout || !day || !timeline) return '';
            return ` style="grid-row:${timeline.gridRow(layout, entry, day)}"`;
        }

        header(entry, label, canManage, blocked = false, day = null) {
            const title = entry.title ? `<span class="adc-entry__title">${esc(entry.title)}</span>` : '';
            const blockedMarker = blocked ? '<span class="adc-entry__blocked-marker" aria-hidden="true">🔒</span>' : '';
            const recurring = esc(l10n.t('Recurring appointment'));
            const seriesMarker = entry.seriesUid ? `<span class="adc-entry__series-marker" title="${recurring}"><span aria-hidden="true">↻</span><span class="hidden-visually">${recurring}</span></span>` : '';
            const editLabel = l10n.t('Edit {type}', { type: label });
            const deleteLabel = l10n.t('Delete {type}', { type: label });
            const controls = canManage
                ? `<span class="adc-entry__actions">
                    <button type="button" class="adc-icon-button icon-rename" data-action="edit-entry" data-entry-id="${esc(entry.id)}" aria-label="${esc(editLabel)}" title="${esc(l10n.t('Edit'))}"></button>
                    <button type="button" class="adc-icon-button icon-delete" data-action="delete-entry" data-entry-id="${esc(entry.id)}" aria-label="${esc(deleteLabel)}" title="${esc(l10n.t('Delete'))}"></button>
                </span>`
                : '';

            const period = this.dayPeriod(entry, day);
            return `<header class="adc-entry__header"><span>${blockedMarker}${seriesMarker}<strong>${esc(label)}</strong> ${esc(period.start)}–${esc(period.end)}${period.continuation}</span>${controls}</header>${title}`;
        }

        dayPeriod(entry, day) {
            if (!day) return { start: this.time(entry.start), end: this.time(entry.end), continuation: '' };
            const dayStart = new Date(day);
            dayStart.setHours(0, 0, 0, 0);
            const dayEnd = new Date(dayStart);
            dayEnd.setDate(dayEnd.getDate() + 1);
            const startsBefore = new Date(entry.start) < dayStart;
            const endsAfter = new Date(entry.end) >= dayEnd;
            const markers = [];
            if (startsBefore) markers.push(this.continuation('←', l10n.t('Continued from previous day')));
            if (endsAfter) markers.push(this.continuation('→', l10n.t('Continues on next day')));
            return {
                start: startsBefore ? '00:00' : this.time(entry.start),
                end: endsAfter ? '24:00' : this.time(entry.end),
                continuation: markers.join(''),
            };
        }

        continuation(marker, label) {
            return ` <span class="adc-entry__continuation" title="${esc(label)}"><span aria-hidden="true">${marker}</span><span class="hidden-visually">${esc(label)}</span></span>`;
        }

        time(value) {
            return l10n.time(new Date(value));
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.CalendarCell = CalendarCell;
})();
