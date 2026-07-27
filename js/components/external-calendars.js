(function() {
    'use strict';

    const providers = {
        kopano: {
            title: 'Kopano verbinden',
            serverUrl: 'https://mail.adberlin.org',
            instruction: 'Melde dich mit deinem Kopano-Benutzernamen und Passwort an. Die vorbelegte Serveradresse kann geändert werden. Voraussetzung: Der Kopano-Betreiber muss CalDAV per HTTPS erlauben; die App kann diesen Serverzugang nicht selbst freischalten.',
            usernameLabel: 'Kopano-Benutzername',
            passwordLabel: 'Kopano-Passwort',
        },
        apple: {
            title: 'Apple verbinden',
            serverUrl: 'https://caldav.icloud.com',
            instruction: 'Verwende deine Apple-ID als Benutzername und ein zuvor im Apple-Account erzeugtes app-spezifisches Passwort.',
            usernameLabel: 'Apple-ID',
            passwordLabel: 'App-spezifisches Passwort',
        },
        manual: {
            title: 'CalDAV manuell verbinden',
            serverUrl: '',
            instruction: 'Trage die HTTPS-CalDAV-Adresse deines Anbieters ein. AD Calendar ermittelt den Kalenderpfad und legt den sichtbaren Kalender „AD Dienste“ an.',
            usernameLabel: 'Benutzername',
            passwordLabel: 'Passwort oder App-Passwort',
        },
    };

    /** Persönliche Providerkarten und ein gemeinsamer, tastaturbedienbarer CalDAV-Einrichtungsdialog. */
    class ExternalCalendars {
        constructor(options) {
            this.repository = options.repository;
            this.onMessage = options.onMessage;
            this.dialog = document.getElementById('adc-external-calendar-dialog');
            this.form = document.getElementById('adc-external-calendar-form');
            this.provider = document.getElementById('adc-external-provider');
            this.heading = document.getElementById('adc-external-dialog-heading');
            this.instruction = document.getElementById('adc-external-instruction');
            this.serverUrl = document.getElementById('adc-external-server-url');
            this.username = document.getElementById('adc-external-username');
            this.password = document.getElementById('adc-external-password');
            this.usernameLabel = document.getElementById('adc-external-username-label');
            this.passwordLabel = document.getElementById('adc-external-password-label');
            this.form.addEventListener('submit', event => this.submit(event));
            this.dialog.addEventListener('cancel', () => this.clearSecret());
            document.getElementById('adc-external-dialog-close').addEventListener('click', () => this.close());
            document.getElementById('adc-external-dialog-cancel').addEventListener('click', () => this.close());
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
                const text = document.getElementById(`adc-external-${provider}-status`);
                const connect = document.querySelector(`[data-external-connect="${provider}"]`);
                const disconnect = document.querySelector(`[data-external-disconnect="${provider}"]`);
                if (!text || !connect || !disconnect) continue;
                text.textContent = status.connected ? `Verbunden – Zielkalender „${status.calendarName || 'AD Dienste'}“` : status.available === false ? 'Noch nicht durch die Administration konfiguriert.' : 'Nicht verbunden.';
                connect.hidden = false;
                connect.disabled = status.available === false;
                connect.textContent = status.connected ? (provider === 'google' ? 'Neu autorisieren' : 'Verbindung ändern') : ({ kopano: 'Kopano verbinden', google: 'Mit Google verbinden', apple: 'Apple verbinden', manual: 'Manuell verbinden' }[provider] || 'Verbinden');
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
                this.onMessage('Externer Kalender wurde verbunden.');
            } catch (error) { this.onMessage(error, true); }
            finally { submit.disabled = false; }
        }

        async disconnect(provider) {
            if (!window.confirm('Verbindung trennen und alle von AD Calendar erzeugten Dienste bei diesem Anbieter entfernen?')) return;
            try {
                const response = await this.repository.disconnectExternalCalendar(provider);
                this.set(response.externalCalendars || {});
                this.onMessage('Externe Kalenderverbindung wurde getrennt.');
            } catch (error) { this.onMessage(error, true); }
        }

        close() {
            this.clearSecret();
            this.dialog.close();
        }

        clearSecret() {
            this.password.value = '';
        }
    }

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.components = window.AdCalendar.components || {};
    window.AdCalendar.components.ExternalCalendars = ExternalCalendars;
})();
