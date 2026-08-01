(function() {
    'use strict';

    const appId = 'adcalendar';
    const root = document.documentElement;
    const requestedLocale = root.dataset?.locale || root.lang || 'en';
    let locale = requestedLocale;
    try { new Intl.DateTimeFormat(locale); }
    catch (error) { locale = 'en'; }

    const interpolate = (message, parameters) => {
        let result = message;
        for (const [name, value] of Object.entries(parameters || {})) {
            result = result.split(`{${name}}`).join(String(value));
        }
        return result;
    };

    const l10n = {
        locale,
        t(message, parameters = {}) {
            return typeof window.t === 'function'
                ? window.t(appId, message, parameters)
                : interpolate(message, parameters);
        },
        n(singular, plural, count, parameters = {}) {
            const values = { ...parameters, count };
            return typeof window.n === 'function'
                ? window.n(appId, singular, plural, count, values)
                : interpolate(count === 1 ? singular : plural, values);
        },
        date(value, options = {}) {
            return new Intl.DateTimeFormat(locale, options).format(value);
        },
        time(value, options = {}) {
            return new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit', ...options }).format(value);
        },
        lower(value) {
            return String(value).toLocaleLowerCase(locale);
        },
    };

    window.AdCalendar = window.AdCalendar || {};
    window.AdCalendar.l10n = l10n;
})();
