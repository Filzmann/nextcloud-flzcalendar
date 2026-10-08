import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const localizationUrl = new URL('../../js/modules/localization.js', import.meta.url);
const source = readFileSync(localizationUrl, 'utf8');

function load(locale, translations = true) {
    const calls = [];
    const window = {};
    if (translations) {
        window.t = (app, key, parameters = {}) => {
            calls.push(['t', app, key, parameters]);
            return `${locale}:${key.replace('{date}', parameters.date ?? '{date}')}`;
        };
        window.n = (app, singular, plural, count, parameters = {}) => {
            calls.push(['n', app, singular, plural, count, parameters]);
            return count === 1 ? singular : plural.replace('{count}', String(count));
        };
    }
    const context = {
        window,
        document: { documentElement: {
            dataset: locale === undefined ? {} : { locale },
            lang: locale === undefined ? '' : locale.split(/[-_]/)[0],
        } },
        Intl,
        Date,
    };
    runInNewContext(source, context, { filename: fileURLToPath(localizationUrl) });
    return { l10n: window.FlzCalendar.l10n, calls };
}

const german = load('de-DE');
if (german.l10n.locale !== 'de-DE') throw new Error('Aktive Nextcloud-Locale wird nicht übernommen.');
if (german.l10n.date(new Date('2026-12-31T12:00:00Z'), { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }) !== '31.12.2026') {
    throw new Error('Deutsches Datumsformat folgt nicht der aktiven Locale.');
}
if (german.l10n.time(new Date('2026-03-29T01:30:00Z'), { timeZone: 'Europe/Berlin' }) !== '03:30') {
    throw new Error('Localezeit berücksichtigt Zeitzone oder Zeitumstellung nicht.');
}
if (german.l10n.lower('ÄPFEL') !== 'äpfel') throw new Error('Localeabhängige Suchnormalisierung fehlt.');

const nextcloudGerman = load('de_DE', false).l10n;
if (nextcloudGerman.locale !== 'de-DE'
    || nextcloudGerman.date(new Date('2026-12-31T12:00:00Z'), { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }) !== '31.12.2026') {
    throw new Error('Nextcloud-Locale de_DE wird nicht als deutsches Datumsformat normalisiert.');
}
const translated = german.l10n.t('Calendar week from {date}', { date: '<31.07.>' });
if (translated !== 'de-DE:Calendar week from <31.07.>' || german.calls[0][1] !== 'flzcalendar' || german.calls[0][3].date !== '<31.07.>') {
    throw new Error('Übersetzung delegiert App-ID oder typisierte Platzhalter nicht unverändert an Nextcloud.');
}
if (german.l10n.n('{count} person', '{count} people', 2, { count: 2 }) !== '2 people' || german.calls[1][4] !== 2) {
    throw new Error('Pluraldelegation folgt nicht dem nativen Nextcloud-Vertrag.');
}

const english = load('en-GB');
if (english.l10n.date(new Date('2026-12-31T12:00:00Z'), { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }) !== '31/12/2026') {
    throw new Error('Englisches Datumsformat wird nicht aus der aktiven Locale erzeugt.');
}

const fallback = load('invalid_locale', false).l10n;
if (fallback.locale !== 'de-DE'
    || fallback.date(new Date('2026-12-31T12:00:00Z'), { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }) !== '31.12.2026'
    || fallback.t('Hello {name}', { name: '<Team>' }) !== 'Hello <Team>'
    || fallback.n('One', '{count} items', 3, { count: 3 }) !== '3 items') {
    throw new Error('Ungültige Locale besitzt keinen sicheren deutschen Datumsfallback.');
}

const missingLocale = load(undefined, false).l10n;
if (missingLocale.locale !== 'de-DE') throw new Error('Ohne Locale ist Deutsch nicht der Standard.');

for (const locale of ['de', 'en_GB']) {
    const catalog = JSON.parse(readFileSync(new URL(`../../l10n/${locale}.json`, import.meta.url), 'utf8'));
    if (typeof catalog.translations !== 'object' || !String(catalog.pluralForm).includes('nplurals=2')) {
        throw new Error(`Nextcloud-Katalog für ${locale} ist unvollständig.`);
    }
}
const germanCatalog = JSON.parse(readFileSync(new URL('../../l10n/de.json', import.meta.url), 'utf8')).translations;
if (germanCatalog.Monday !== 'Montag'
    || germanCatalog['Calendar week from {date}'] !== 'Kalenderwoche ab {date}'
    || germanCatalog['_{count} matching gap_::_{count} matching gaps_'][1] !== '{count} passende Lücken') {
    throw new Error('Deutscher Katalog enthält Wochentage, Platzhalter oder Plural nicht korrekt.');
}
const englishCatalog = JSON.parse(readFileSync(new URL('../../l10n/en_GB.json', import.meta.url), 'utf8')).translations;
if (englishCatalog.Monday !== 'Monday' || englishCatalog['Calendar week from {date}'] !== 'Calendar week from {date}') {
    throw new Error('Englischer Zweitkatalog ist nicht vollständig prüfbar.');
}

const jsRoot = fileURLToPath(new URL('../../js', import.meta.url));
const clientKeys = new Set();
for (const relativePath of readdirSync(jsRoot, { recursive: true })) {
    if (!String(relativePath).endsWith('.js')) continue;
    const clientSource = readFileSync(new URL(`../../js/${relativePath}`, import.meta.url), 'utf8');
    for (const match of clientSource.matchAll(/l10n\.t\('([^']+)'/g)) clientKeys.add(match[1]);
    for (const match of clientSource.matchAll(/l10n\.n\('([^']+)',\s*'([^']+)'/g)) clientKeys.add(`_${match[1]}_::_${match[2]}_`);
}
for (const locale of ['de', 'en_GB']) {
    const json = JSON.parse(readFileSync(new URL(`../../l10n/${locale}.json`, import.meta.url), 'utf8')).translations;
    let registered = null;
    const catalogSource = readFileSync(new URL(`../../l10n/${locale}.js`, import.meta.url), 'utf8');
    const declaredKeys = [...catalogSource.matchAll(/^\s*"([^"]+)"\s*:/gm)].map(match => match[1]);
    if (new Set(declaredKeys).size !== declaredKeys.length) throw new Error(`JavaScript-Katalog ${locale} enthält doppelte Schlüssel.`);
    runInNewContext(catalogSource, {
        OC: { L10N: { register(app, translations) { if (app === 'flzcalendar') registered = translations; } } },
    });
    for (const key of clientKeys) {
        if (!(key in json) || !(key in registered)) throw new Error(`Client-L10N-Schlüssel fehlt im ${locale}-Katalog: ${key}`);
    }
}

for (const relativePath of [
    '../../js/components/calendar-cell.js',
    '../../js/components/calendar-filters.js',
    '../../js/components/entry-dialog.js',
    '../../js/components/meeting-finder.js',
    '../../js/components/week-navigation.js',
    '../../js/components/week-table.js',
]) {
    const component = readFileSync(new URL(relativePath, import.meta.url), 'utf8');
    if (component.includes("'de-DE'") || component.includes('"de-DE"')) {
        throw new Error(`Komponente erzwingt weiterhin eine deutsche Locale: ${relativePath}`);
    }
}

const shiftDefaultsSource = readFileSync(new URL('../../js/components/shift-defaults.js', import.meta.url), 'utf8');
if (shiftDefaultsSource.includes("'Montag'") || !shiftDefaultsSource.includes("l10n.t('Monday')")) {
    throw new Error('Standarddienstzeiten verwenden weiterhin eine feste deutsche Wochentagsliste.');
}

const visibleSourceContracts = new Map([
    ['../../js/components/calendar-cell.js', ['Dienst anlegen', 'Genehmigter Urlaub', 'Sperrtermin']],
    ['../../js/components/calendar-filters.js', ['Keine explizite Auswahl', 'auswählen']],
    ['../../js/components/entry-dialog.js', ['Bearbeiten', 'Überschneidung mit Dienst']],
    ['../../js/components/external-calendars.js', ['Kopano verbinden', 'Nicht verbunden']],
    ['../../js/components/shift-calendar-sync.js', ['Kalender ist aktiv']],
    ['../../js/components/week-table.js', ['Geplante Dienste und Termine']],
    ['../../js/modules/entry-workflow.js', ['Meeting für alle Beteiligten gespeichert', 'Termin wirklich löschen']],
    ['../../js/main.js', ['Persönliche Standard-Dienstzeiten gespeichert', 'Alle Personen']],
]);
for (const [relativePath, forbiddenTexts] of visibleSourceContracts) {
    const component = readFileSync(new URL(relativePath, import.meta.url), 'utf8');
    if (!component.includes('l10n.t(')) throw new Error(`Sichtbare Komponente verwendet den L10N-Adapter nicht: ${relativePath}`);
    for (const text of forbiddenTexts) {
        if (component.includes(`'${text}`) || component.includes(`\`${text}`)) {
            throw new Error(`Komponente enthält noch festen deutschen UI-Text: ${relativePath}: ${text}`);
        }
    }
}

console.log('Localization smoke: OK');
