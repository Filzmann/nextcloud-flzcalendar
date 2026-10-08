(function() {
    'use strict';
    const l10n = window.FlzCalendar.l10n;

    /**
     * Zweck: Rendert Rollen-, Bereichs- und Personenfilter und bindet deren Interaktionen.
     * Zusammenspiel: CalendarState hält den Filterzustand; main.js reagiert über onChange mit einer neuen Tabellenansicht.
     * Vertrag: Änderungen werden vor onChange im Zustand und in der URL persistiert. Der Reset entfernt nur explizit ausgewählte Personen.
     */
    class CalendarFilters {
        constructor(options) {
            this.state = options.state;
            this.organization = options.organization;
            this.leadershipStaffRoles = options.leadershipStaffRoles;
            this.onChange = options.onChange;
            this.roles = document.getElementById('flz-calendar-role-filters');
            this.areas = document.getElementById('flz-calendar-area-filters');
            this.search = document.getElementById('flz-calendar-person-search');
            this.searchResults = document.getElementById('flz-calendar-search-results');
            this.selectedPeople = document.getElementById('flz-calendar-selected-people');
            this.reset = document.getElementById('flz-calendar-reset-selection');
            this.search.addEventListener('input', event => this.renderSearch(event.target.value));
            this.reset.addEventListener('click', () => this.resetSelection());
        }

        render() {
            const employees = this.state.data?.employees || [];
            const organization = this.organization();
            const roles = [...new Set(employees.flatMap(employee => employee.roles))]
                .filter(role => !this.leadershipStaffRoles.has(role))
                .sort((a, b) => organization.roleOrder(a) - organization.roleOrder(b)
                    || organization.roleLabel(a).localeCompare(organization.roleLabel(b), l10n.locale));
            const areas = [...new Set(employees.flatMap(employee => employee.areas))]
                .sort((a, b) => organization.areaOrder(a) - organization.areaOrder(b)
                    || organization.areaLabel(a).localeCompare(organization.areaLabel(b), l10n.locale));
            this.renderCheckboxes(this.roles, roles, this.state.roles, value => organization.roleLabel(value));
            this.renderLeadershipStaffCheckbox();
            this.renderCheckboxes(this.areas, areas, this.state.areas, value => organization.areaLabel(value));
            this.renderSelected();
        }

        renderLeadershipStaffCheckbox() {
            const label = document.createElement('label');
            const input = document.createElement('input');
            const unfiltered = this.state.isUnfiltered();
            input.type = 'checkbox';
            input.checked = unfiltered || this.state.showLeadershipStaff;
            input.disabled = unfiltered;
            input.addEventListener('change', () => {
                this.state.showLeadershipStaff = input.checked;
                if (!input.checked) this.state.leadershipStaffOnly = false;
                if (!input.checked) for (const role of this.leadershipStaffRoles) this.state.roles.delete(role);
                this.changed(true);
            });
            label.append(input, document.createTextNode(` ${l10n.t('Show {label}', { label: this.organization().staffBlockLabel })}`));
            this.roles.append(label);
        }

        renderCheckboxes(container, values, selected, labelFor) {
            container.replaceChildren(...values.map(value => {
                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = selected.has(value);
                input.addEventListener('change', () => {
                    this.state.leadershipStaffOnly = false;
                    input.checked ? selected.add(value) : selected.delete(value);
                    this.changed(true);
                });
                label.append(input, document.createTextNode(` ${labelFor(value)}`));
                return label;
            }));
        }

        renderSelected() {
            const people = (this.state.data?.employees || []).filter(employee => this.state.selected.has(employee.uid));
            this.reset.hidden = people.length === 0;
            if (!people.length) {
                this.selectedPeople.replaceChildren(this.node('li', l10n.t('No explicit selection – group filters apply.')));
                return;
            }
            this.selectedPeople.replaceChildren(...people.map(employee => {
                const item = this.node('li');
                const button = this.node('button', l10n.t('Remove {employee}', { employee: employee.displayName }));
                button.type = 'button';
                button.addEventListener('click', () => {
                    this.state.selected.delete(employee.uid);
                    this.changed(true);
                });
                item.append(button);
                return item;
            }));
        }

        renderSearch(value) {
            const query = l10n.lower(value.trim());
            const matches = query ? (this.state.data?.employees || [])
                .filter(employee => l10n.lower(employee.displayName).includes(query) && !this.state.selected.has(employee.uid))
                .slice(0, 12) : [];
            this.searchResults.replaceChildren(...matches.map(employee => {
                const item = this.node('li');
                const button = this.node('button', l10n.t('Select {employee}', { employee: employee.displayName }));
                button.type = 'button';
                button.addEventListener('click', () => {
                    this.state.leadershipStaffOnly = false;
                    this.state.selected.add(employee.uid);
                    this.clearSearch();
                    this.changed(true);
                });
                item.append(button);
                return item;
            }));
        }

        resetSelection() {
            this.state.selected.clear();
            this.clearSearch();
            this.changed(true);
        }

        changed(renderSelection = false) {
            this.state.persist();
            if (renderSelection) this.render();
            this.onChange();
        }

        clearSearch() {
            this.search.value = '';
            this.searchResults.replaceChildren();
        }

        node(tag, value) {
            const result = document.createElement(tag);
            if (value !== undefined) result.textContent = value;
            return result;
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.components = window.FlzCalendar.components || {};
    window.FlzCalendar.components.CalendarFilters = CalendarFilters;
})();
