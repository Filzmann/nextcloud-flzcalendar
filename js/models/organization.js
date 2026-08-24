(function() {
    'use strict';

    const BaseModel = window.LocalBase.models.Model;

    /**
     * Zweck: Stellt den serverseitig konfigurierten AD-Organisationsvertrag für Filter, Gruppierung und Einstellungen bereit.
     * Spiegelung: PHP: OCA\LocalBase\Organization\AdOrganizationDefinition.
     */
    class Organization extends BaseModel {
        constructor(data = {}) {
            super();
            this.version = Number(data.version || 1);
            this.teamGroupPrefix = String(data.teamGroupPrefix || '');
            this.teamLabelPrefix = String(data.teamLabelPrefix || 'Assistenzteam');
            this.teamCodeMaxLength = Number(data.teamCodeMaxLength || 16);
            this.staffBlockLabel = String(data.staffBlockLabel || 'Leitungen und Stabsstellen');
            this.roles = data.roles && typeof data.roles === 'object' ? data.roles : {};
            this.areas = data.areas && typeof data.areas === 'object' ? data.areas : {};
            this.hierarchy = data.hierarchy && typeof data.hierarchy === 'object' ? data.hierarchy : {};
            this.organizationTeams = Array.isArray(data.organizationTeams) ? data.organizationTeams : [];
        }

        roleLabel(groupId) {
            return Object.values(this.roles).find(role => role.groupId === groupId)?.label || groupId;
        }

        areaLabel(groupId) {
            return Object.values(this.areas).find(area => area.groupId === groupId)?.label || groupId;
        }

        roleShortLabel(groupId) {
            const role = Object.values(this.roles).find(item => item.groupId === groupId);
            return role?.shortLabel || role?.label || groupId;
        }

        areaShortLabel(groupId) {
            const area = Object.values(this.areas).find(item => item.groupId === groupId);
            return area?.shortLabel || area?.label || groupId;
        }

        staffRoleGroups() {
            return Object.values(this.roles).filter(role => role.staffBlock).sort((a, b) => Number(a.sortOrder) - Number(b.sortOrder)).map(role => role.groupId);
        }

        leadershipStaffRoleGroups() {
            const separateRoleKeys = new Set(['finance_lead', 'finance', 'payroll', 'it', 'secretariat']);
            return Object.entries(this.roles)
                .filter(([key, role]) => role.staffBlock && !separateRoleKeys.has(key))
                .sort(([, left], [, right]) => Number(left.sortOrder) - Number(right.sortOrder))
                .map(([, role]) => role.groupId);
        }

        roleOrder(groupId) {
            const role = Object.values(this.roles).find(item => item.groupId === groupId);
            return role ? Number(role.sortOrder) : Number.MAX_SAFE_INTEGER;
        }

        areaOrder(groupId) {
            const area = Object.values(this.areas).find(item => item.groupId === groupId);
            return area ? Number(area.sortOrder) : Number.MAX_SAFE_INTEGER;
        }

        toArray() {
            return JSON.parse(JSON.stringify({
                version: this.version,
                teamGroupPrefix: this.teamGroupPrefix,
                teamLabelPrefix: this.teamLabelPrefix,
                teamCodeMaxLength: this.teamCodeMaxLength,
                staffBlockLabel: this.staffBlockLabel,
                roles: this.roles,
                areas: this.areas,
                hierarchy: this.hierarchy,
                organizationTeams: this.organizationTeams,
            }));
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.models = window.AdCalendar.models || {};
    window.AdCalendar.models.Organization = Organization;
})();
