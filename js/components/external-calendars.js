(function() {
    'use strict';
    const l10n = window.FlzCalendar.l10n;

    const providers = {
        kopano: {
            title: l10n.t('Connect Kopano'),
            serverUrl: '',
            instruction: l10n.t('Sign in with your Kopano username and password. The prefilled server address can be changed. Requirement: the Kopano provider must allow CalDAV over HTTPS; the app cannot enable this server access itself.'),
            usernameLabel: l10n.t('Kopano username'),
            passwordLabel: l10n.t('Kopano password'),
        },
        apple: {
            title: l10n.t('Connect Apple'),
            serverUrl: 'https://caldav.icloud.com',
            instruction: l10n.t('Use your Apple ID as the username and an app-specific password previously created in your Apple account.'),
            usernameLabel: 'Apple-ID',
            passwordLabel: l10n.t('App-specific password'),
        },
        manual: {
            title: l10n.t('Connect CalDAV manually'),
            serverUrl: '',
            instruction: '',
            usernameLabel: l10n.t('Username'),
            passwordLabel: l10n.t('Password or app password'),
        },
    };

    /** Persönliche Providerkarten und ein gemeinsamer, tastaturbedienbarer CalDAV-Einrichtungsdialog. */
    class ExternalCalendars {
        constructor(options) {
            this.repository = options.repository;
            this.onMessage = options.onMessage;
            this.dialog = document.getElementById('flz-calendar-external-calendar-dialog');
            this.form = document.getElementById('flz-calendar-external-calendar-form');
            this.provider = document.getElementById('flz-calendar-external-provider');
            this.heading = document.getElementById('flz-calendar-external-dialog-heading');
            this.instruction = document.getElementById('flz-calendar-external-instruction');
            this.serverUrl = document.getElementById('flz-calendar-external-server-url');
            this.username = document.getElementById('flz-calendar-external-username');
            this.password = document.getElementById('flz-calendar-external-password');
            this.usernameLabel = document.getElementById('flz-calendar-external-username-label');
            this.passwordLabel = document.getElementById('flz-calendar-external-password-label');
            this.returnFocus = null;
            this.calendarName = this.dialog.dataset?.calendarName || 'Filzmann Dienste';
            providers.kopano.serverUrl = this.dialog.dataset?.kopanoDefault || providers.kopano.serverUrl;
            providers.manual.instruction = l10n.t('Enter your provider’s HTTPS CalDAV address. Filzmann Calendar discovers the calendar path and creates the visible calendar “{calendar}”.', { calendar: this.calendarName });
            this.form.addEventListener('submit', event => this.submit(event));
            this.dialog.addEventListener('cancel', event => { event.preventDefault(); this.close(); });
            document.getElementById('flz-calendar-external-dialog-close').addEventListener('click', () => this.close());
            document.getElementById('flz-calendar-external-dialog-cancel').addEventListener('click', () => this.close());
            for (const button of document.querySelectorAll('[data-external-connect]')) button.addEventListener('click', () => this.connect(button.dataset.externalConnect));
            for (const button of document.querySelectorAll('[data-external-disconnect]')) button.addEventListener('click', () => this.disconnect(button.dataset.externalDisconnect));
        }

        async load() {
            try {
                const response = await this.repository.externalCalendars();
                this.set(response.externalCalendars || {});
            } catch (error) { this.onMessage(error, true); }
        }

        set(statuses) {
            for (const [provider, status] of Object.entries(statuses)) {
                if (status.calendarName) this.calendarName = status.calendarName;
                const text = document.getElementById(`flz-calendar-external-${provider}-status`);
                const connect = document.querySelector(`[data-external-connect="${provider}"]`);
                const disconnect = document.querySelector(`[data-external-disconnect="${provider}"]`);
                if (!text || !connect || !disconnect) continue;
                text.textContent = status.connected
                    ? l10n.t('Connected – target calendar “{calendar}”', { calendar: status.calendarName || 'Filzmann Dienste' })
                    : status.available === false ? l10n.t('Not configured by an administrator yet.') : l10n.t('Not connected.');
                connect.hidden = false;
                connect.disabled = status.available === false;
                connect.textContent = status.connected
                    ? (provider === 'google' ? l10n.t('Authorise again') : l10n.t('Change connection'))
                    : ({ kopano: l10n.t('Connect Kopano'), google: l10n.t('Connect with Google'), apple: l10n.t('Connect Apple'), manual: l10n.t('Connect manually') }[provider] || l10n.t('Connect'));
                disconnect.hidden = !status.connected;
            }
        }

        async connect(provider) {
            if (provider === 'google') {
                try {
                    const response = await this.repository.startGoogleCalendarConnection();
                    window.location.assign(response.authorizationUrl);
                } catch (error) { this.onMessage(error, true); }
                return;
            }
            const settings = providers[provider];
            if (!settings) return;
            this.returnFocus = document.activeElement;
            this.provider.value = provider;
            this.heading.textContent = settings.title;
            this.instruction.textContent = settings.instruction;
            this.serverUrl.value = settings.serverUrl;
            this.username.value = '';
            this.password.value = '';
            this.usernameLabel.textContent = settings.usernameLabel;
            this.passwordLabel.textContent = settings.passwordLabel;
            this.dialog.showModal();
            this.serverUrl.focus();
        }

        async submit(event) {
            event.preventDefault();
            if (!this.form.reportValidity()) return;
            const submit = this.form.querySelector('button[type="submit"]');
            submit.disabled = true;
            try {
                const response = await this.repository.connectCalDav(this.provider.value, this.serverUrl.value, this.username.value, this.password.value);
                this.set(response.externalCalendars || {});
                this.close();
                this.onMessage(l10n.t('The external calendar was connected.'));
            } catch (error) { this.onMessage(error, true); }
            finally { submit.disabled = false; }
        }

        async disconnect(provider) {
            if (!window.confirm(l10n.t('Disconnect and remove all shifts created by Filzmann Calendar from this provider?'))) return;
            try {
                const response = await this.repository.disconnectExternalCalendar(provider);
                this.set(response.externalCalendars || {});
                this.onMessage(l10n.t('The external calendar connection was disconnected.'));
            } catch (error) { this.onMessage(error, true); }
        }

        close() {
            this.clearSecret();
            this.dialog.close();
            const returnFocus = this.returnFocus;
            this.returnFocus = null;
            if (returnFocus?.isConnected !== false && typeof returnFocus?.focus === 'function') returnFocus.focus();
        }

        clearSecret() {
            this.password.value = '';
        }
    }

    window.FlzCalendar = window.FlzCalendar || {};
    window.FlzCalendar.components = window.FlzCalendar.components || {};
    window.FlzCalendar.components.ExternalCalendars = ExternalCalendars;
})();
