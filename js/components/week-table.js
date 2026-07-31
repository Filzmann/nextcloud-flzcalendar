(function() {
    'use strict';
    const CalendarDate = window.AdCalendar.modules.CalendarDate;

    /**
     * Zweck: Rendert die Wochen- oder Monatsmatrix in beiden Ausrichtungen inklusive Fachgruppen- und Hierarchiesortierung.
     * Zusammenspiel: main.js filtert Personen; CalendarCell rendert den Inhalt eines Mitarbeiter-Tags.
     */
    class WeekTable {
        constructor(options) {
            this.container = options.container;
            this.calendarCell = options.calendarCell;
            this.organization = options.organization;
            this.timeline = new window.AdCalendar.modules.CalendarTimeline();
            this.holidays = options.holidays || new window.AdCalendar.modules.HolidayCalendar();
        }

        setHolidays(calendars) { this.holidays.set(calendars); }

        render(employees, state) {
            const orderedEmployees = this.orderedEmployees(employees);
            const range = state.visibleRange();
            const days = this.daysInRange(range.start, range.end);
            this.container.replaceChildren(this.periodMatrix(orderedEmployees, state, days));
        }

        periodMatrix(employees, state, days) {
            const block = document.createElement('section');
            block.className = 'adc-period-matrix';
            const wrap = document.createElement('div');
            wrap.className = 'adc-table-wrap';
            const table = document.createElement('table');
            table.className = 'adc-calendar';
            const caption = this.node('caption', 'Geplante Dienste und Termine je Mitarbeiter*in');
            const head = document.createElement('thead');
            const body = document.createElement('tbody');
            table.append(caption, head, body);
            wrap.append(table);
            block.append(wrap);
            const activeMonth = state.period === 'month' ? state.month : null;
            const compactDays = new Set(days
                .filter(day => this.isCompactDay(day, state, employees))
                .map(day => CalendarDate.isoDay(day)));
            if (state.vertical) this.vertical(employees, state, days, head, body, activeMonth, compactDays);
            else this.horizontal(employees, state, days, head, body, activeMonth, compactDays);
            return block;
        }

        vertical(employees, state, days, head, body, activeMonth = null, compactDays = new Set()) {
            const header = document.createElement('tr'); header.append(this.node('th', 'Mitarbeiter*in'));
            for (const day of days) {
                const compact = compactDays.has(CalendarDate.isoDay(day));
                const dayHeading = this.node('th', this.dayLabel(day, { weekday: 'short', day: '2-digit', month: '2-digit' }), this.dayClasses(day, activeMonth, compact));
                dayHeading.scope = 'col'; header.append(dayHeading);
            }
            head.replaceChildren(header);

            const rows = [];
            let previousCluster = null;
            for (const employee of employees) {
                const cluster = this.clusterLabel(employee);
                if (!state.selected.size && cluster !== previousCluster) {
                    const groupRow = document.createElement('tr');
                    const groupCell = this.node('th', cluster, 'adc-group-heading');
                    groupCell.colSpan = days.length + 1; groupRow.append(groupCell); rows.push(groupRow); previousCluster = cluster;
                }
                const row = document.createElement('tr');
                const name = this.node('th', employee.displayName, this.classes('adc-person-heading', state.selected.has(employee.uid) ? 'adc-selected' : ''));
                name.scope = 'row'; row.append(name);
                const employeeEntries = state.data.entries.filter(entry => entry.employeeUid === employee.uid);
                const layout = this.timeline.layout(employeeEntries, days);
                for (const day of days) {
                    row.append(this.cellFor(employee, day, state.data.entries, state.data.absences || [], layout, activeMonth, compactDays.has(CalendarDate.isoDay(day))));
                }
                rows.push(row);
            }
            body.replaceChildren(...rows);
        }

        horizontal(employees, state, days, head, body, activeMonth = null, compactDays = new Set()) {
            const header = document.createElement('tr'); header.append(this.node('th', 'Tag'));
            for (const employee of employees) {
                const name = this.node('th', employee.displayName, this.classes('adc-person-heading', state.selected.has(employee.uid) ? 'adc-selected' : ''));
                name.scope = 'col'; header.append(name);
            }
            head.replaceChildren(header);
            const rows = days.map(day => {
                const row = document.createElement('tr');
                const compact = compactDays.has(CalendarDate.isoDay(day));
                const label = this.node('th', this.dayLabel(day, { weekday: 'long', day: '2-digit', month: '2-digit' }), this.dayClasses(day, activeMonth, compact));
                label.scope = 'row'; row.append(label);
                const dayEnd = new Date(day); dayEnd.setDate(dayEnd.getDate() + 1);
                const visibleUids = new Set(employees.map(employee => employee.uid));
                const dayEntries = state.data.entries.filter(entry => visibleUids.has(entry.employeeUid) && new Date(entry.start) < dayEnd && new Date(entry.end) > day);
                const layout = this.timeline.layout(dayEntries, [day]);
                for (const employee of employees) row.append(this.cellFor(employee, day, state.data.entries, state.data.absences || [], layout, activeMonth, compact));
                return row;
            });
            body.replaceChildren(...rows);
        }

        cellFor(employee, day, allEntries, allAbsences, layout, activeMonth = null, compact = false) {
            const cell = document.createElement('td');
            const dayEnd = new Date(day); dayEnd.setDate(dayEnd.getDate() + 1);
            const entries = allEntries.filter(entry => entry.employeeUid === employee.uid && new Date(entry.start) < dayEnd && new Date(entry.end) > day);
            const absences = allAbsences.filter(absence => absence.employeeUid === employee.uid && new Date(absence.start) < dayEnd && new Date(absence.end) > day);
            cell.dataset.employeeUid = employee.uid;
            cell.dataset.day = CalendarDate.isoDay(day);
            if (compact) cell.classList.add('adc-compact-day');
            if (this.outsideMonth(day, activeMonth)) cell.classList.add('adc-outside-month');
            if (this.isWeekend(day)) cell.classList.add('adc-weekend');
            const holiday = this.holidays.name(day);
            if (holiday) {
                cell.classList.add('adc-holiday');
                cell.dataset.holiday = holiday;
            }
            cell.innerHTML = this.calendarCell.render(entries, employee, absences, layout, day, this.timeline);
            return cell;
        }

        dayLabel(day, options) {
            const label = day.toLocaleDateString('de-DE', options);
            return [label, this.holidays.name(day)].filter(Boolean).join(' · ');
        }

        dayClasses(day, activeMonth, compact = false) {
            return this.classes(
                compact ? 'adc-compact-day' : '',
                this.outsideMonth(day, activeMonth) ? 'adc-outside-month' : '',
                this.isWeekend(day) ? 'adc-weekend' : '',
                this.holidays.name(day) ? 'adc-holiday' : '',
            );
        }

        isWeekend(day) {
            return day.getDay() === 0 || day.getDay() === 6;
        }

        outsideMonth(day, activeMonth) {
            return activeMonth !== null && (day.getFullYear() !== activeMonth.getFullYear() || day.getMonth() !== activeMonth.getMonth());
        }

        isCompactDay(day, state, employees) {
            if (!this.isWeekend(day) && !this.holidays.name(day)) return false;
            const dayEnd = new Date(day);
            dayEnd.setDate(dayEnd.getDate() + 1);
            const visibleUids = new Set(employees.map(employee => employee.uid));
            const overlaps = value => visibleUids.has(value.employeeUid)
                && new Date(value.start) < dayEnd
                && new Date(value.end) > day;
            return !state.data.entries.some(overlaps);
        }

        clusterLabel(employee) {
            const organization = this.organization();
            const staffRoles = new Set(organization.staffRoleGroups());
            if (employee.roles.some(role => staffRoles.has(role))) return organization.staffBlockLabel;
            const roleNames = employee.roles.slice()
                .sort((a, b) => organization.roleOrder(a) - organization.roleOrder(b))
                .map(value => organization.roleLabel(value));
            const roles = roleNames.length > 1 ? `${roleNames[0]} (${roleNames.slice(1).join(', ')})` : roleNames[0] || 'Ohne Fachrolle';
            const areas = employee.areas.slice()
                .sort((a, b) => organization.areaOrder(a) - organization.areaOrder(b))
                .map(value => organization.areaLabel(value)).join(' / ');
            return areas ? `${roles} · ${areas}` : roles;
        }

        orderedEmployees(employees) {
            return employees.slice().sort((a, b) => this.employeeOrder(a, b));
        }

        employeeOrder(a, b) {
            const roleComparison = this.groupRoleRank(a) - this.groupRoleRank(b);
            if (roleComparison !== 0) return roleComparison;
            const areaComparison = this.groupAreaRank(a) - this.groupAreaRank(b);
            if (areaComparison !== 0) return areaComparison;
            const clusterComparison = this.clusterLabel(a).localeCompare(this.clusterLabel(b), 'de');
            if (clusterComparison !== 0) return clusterComparison;
            const hierarchyComparison = this.staffRank(a) - this.staffRank(b);
            return hierarchyComparison || a.displayName.localeCompare(b.displayName, 'de');
        }

        groupRoleRank(employee) {
            const organization = this.organization();
            const staffRoles = organization.staffRoleGroups();
            const staffRoleSet = new Set(staffRoles);
            if (employee.roles.some(role => staffRoleSet.has(role))) {
                return Math.min(...staffRoles.map(role => organization.roleOrder(role)));
            }
            return this.staffRank(employee);
        }

        groupAreaRank(employee) {
            const organization = this.organization();
            const staffRoles = new Set(organization.staffRoleGroups());
            if (employee.roles.some(role => staffRoles.has(role))) return Number.MIN_SAFE_INTEGER;
            const ranks = employee.areas.map(area => organization.areaOrder(area));
            return ranks.length ? Math.min(...ranks) : Number.MAX_SAFE_INTEGER;
        }

        staffRank(employee) {
            const ranks = employee.roles.map(role => this.organization().roleOrder(role));
            return ranks.length ? Math.min(...ranks) : Number.MAX_SAFE_INTEGER;
        }

        daysInRange(start, end) {
            const days = [];
            for (let day = new Date(start); day < end; day.setDate(day.getDate() + 1)) days.push(new Date(day));
            return days;
        }
        classes(...values) { return values.filter(Boolean).join(' '); }
        node(tag, value, className) { const result = document.createElement(tag); result.textContent = value; if (className) result.className = className; return result; }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.WeekTable = WeekTable;
})();
