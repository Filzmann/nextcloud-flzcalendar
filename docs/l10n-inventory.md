# L10N-Inventar – Filzmann Kalender

Stand: 31. Juli 2026. Dieses Inventar ist die Arbeitsgrundlage für
`FLZC-L10N`. Es trennt sichtbare Texte von technischen oder bewusst stabilen
Werten. Das abschließende Rohtext- und Katalog-Gate ist für die unten
aufgeführten Oberflächen verbindlich.

Fortschritt: Locale-Adapter, `de`-/`en_GB`-Kataloge, öffentliche
Controllerfehler, Templates, JavaScript-Komponenten, Navigation, CLI sowie
die sichtbaren Nextcloud-DAV-/Google-Ereignistexte sind migriert. Die
automatischen L10N-Verträge sind abgeschlossen; die manuelle Browserabnahme
für beide Locales bleibt bewusst aufgeschoben.

## Unveränderte Maschinenverträge

- App-ID, Routen, DOM-IDs, CSS-Klassen, Gruppen-IDs und Providerwerte bleiben
  sprachunabhängig.
- ISO-Daten, IANA-Zeitzonen, Serien-, Urlaubs- und Synchronisationsstatus,
  DAV-URIs, Provider-IDs und Objektkennungen werden nicht übersetzt.
- Administrativ konfigurierte Kalender-, Rollen-, Bereichs- und Personennamen
  werden unverändert und kontextgerecht escaped ausgegeben.
- HTTP-Status und neue maschinenlesbare Fehlercodes bleiben über alle Locales
  identisch. Rechte- und Sicherheitsentscheidungen werten niemals eine
  übersetzte Meldung aus.
- Logtexte und interne Exceptions werden nicht allein für die Oberfläche zum
  öffentlichen Übersetzungsvertrag. Nutzerseitig sichtbare Fehler entstehen
  an der Controller-/UI-Grenze aus einem stabilen Code und `IL10N`/`t()`.

## Serverseitige sichtbare Texte

| Bereich | Dateien | Zu migrieren |
|---|---|---|
| Hauptoberfläche | `templates/index.php`, `templates/partials/settings.php`, `templates/partials/entry-dialog.php`, `templates/partials/meeting-dialog.php` | Überschriften, Labels, Hilfen, Status-Ausgangstexte, Buttons und ARIA-Beschriftungen |
| Administration | `templates/admin.php`, `lib/Settings/Admin.php`, `lib/Settings/AdminSection.php` | Abschnittsnamen, Status, Formulare, Anleitungen und dynamische Hinweise |
| Kalender-API | `lib/Controller/ApiController.php`, `lib/Controller/MeetingController.php` | öffentliche Fehlertexte und Meldungsplatzhalter; stabile Codes ergänzen |
| Persönliche/administrative Kalender | `lib/Controller/ExternalCalendarController.php`, `lib/Controller/ExternalCalendarAdminController.php`, `lib/Controller/GoogleOAuthAdminController.php`, `lib/Controller/CalendarDefaultsAdminController.php`, `lib/Controller/DemoAdminController.php` | Allow-/Deny-, Validierungs- und Providerfehler; stabile Codes ergänzen |
| Navigations-/Kommandoausgabe | `lib/Listener/StandaloneNavigationListener.php`, `lib/Command/SeedDemoCommand.php` | sichtbare Navigations- und CLI-Texte |
| Fachliche Validierung | `lib/Model/CalendarEntry.php`, `lib/Model/RecurrenceRule.php`, `lib/Service/CalendarService.php`, `lib/Service/CalendarSettingsService.php`, `lib/Service/CalendarPreferenceService.php`, `lib/Service/CalendarTargetConfig.php`, `lib/Service/ExternalCalendarService.php`, `lib/Service/MeetingAvailabilityService.php`, `lib/Service/MeetingService.php`, `lib/Service/RecurringAppointmentService.php`, `lib/Exception/MeetingSlotUnavailableException.php` | an Nutzer*innen weitergereichte Validierungsgründe in lokalisierbare Fehlercodes überführen |
| Providerdiagnosen | `lib/CalendarSync/CalDavClient.php`, `lib/CalendarSync/ExternalCalendarConnectionException.php`, `lib/CalendarSync/ExternalCalendarUrlValidator.php`, `lib/CalendarSync/GoogleCalendarClient.php`, `lib/CalendarSync/GoogleOAuthService.php`, `lib/CalendarSync/NextcloudDavShiftCalendarPublisher.php` | öffentliche Diagnosen an der Controllergrenze lokalisieren; interne sichere Klassifikation bewahren |

Migrationen, Repositoryfehler, Hintergrundjob-Logs, Testassertionen und
synthetische Demo-Fachdaten sind keine UI-Katalogquelle. Sie bleiben nur dann
deutsch, wenn der Text ein interner Diagnose- oder Fixturewert ist.

## Clientseitige sichtbare Texte

| Bereich | Dateien | Zu migrieren |
|---|---|---|
| App-Orchestrierung | `js/main.js`, `js/modules/entry-workflow.js` | Lade-, Erfolgs-, Fehler- und Bestätigungsdialoge |
| Navigation und Matrix | `js/components/week-navigation.js`, `js/components/week-table.js`, `js/components/calendar-cell.js` | Zeitraum, Achsen, Eintragstypen, Aktionen, Summen und ARIA-Texte |
| Filter | `js/components/calendar-filters.js` | Filterstatus, Auswahlaktionen und localeabhängige Suche |
| Einträge und Serien | `js/components/entry-dialog.js` | Dialogtitel, Labels, Validierung, Serien- und Löschoptionen |
| Meetings | `js/components/meeting-finder.js` | Suchstatus, Zeitfenster, Teilnehmer*innen, Buttons und Fehler |
| Persönliche Einstellungen | `js/components/shift-defaults.js`, `js/components/shift-calendar-sync.js`, `js/components/external-calendars.js` | Wochentage, Synchronisations- und Providertexte |
| Administration | `js/admin.js` | Speichern, Entfernen, Kopieren, Verbindungstest und Demo-Pack |
| Fallbacktexte aus Modellen | `js/models/calendar-entry.js`, `js/models/organization.js`, `js/modules/calendar-state.js`, `js/modules/meeting-capabilities.js`, `js/repositories/calendar-repository.js` | nur tatsächlich sichtbare Fallbacks und öffentliche Fehler |

`js/modules/calendar-date.js`, `js/modules/calendar-timeline.js` und
`js/modules/holiday-calendar.js` verarbeiten überwiegend technische Daten;
sichtbare Fallbacks werden trotzdem vom abschließenden Rohtext-Gate erfasst.

## Abschlussnachweis

- `L10nCatalogContractTest` erfasst serverseitige Schlüssel aus Templates,
  öffentlicher Fehlererzeugung, Navigation, CLI, Einstellungen und
  Kalenderprovidern und verlangt sie in `de` und `en_GB`.
- `localization-smoke.mjs` erfasst alle clientseitig verwendeten `t()`-/`n()`-
  Schlüssel, prüft beide JSON-/JavaScript-Kataloge und verhindert feste
  deutsche UI-Texte in den sichtbaren Hauptkomponenten.
- Locale, englische Zweitlocale, ungültige Locale/Fallback, Singular/Plural,
  typisierte Platzhalter, DST, Jahresgrenze und fehlende Kataloge sind
  automatisiert geprüft. Ein zusätzlicher Sicherheits-Smoke beweist das
  Escaping manipulierter Übersetzungstexte in HTML-erzeugenden Kalenderzellen.
- Lange Beschriftungen dürfen in Navigation und Zeitraumumschalter umbrechen;
  die zugehörige CSS-Invariante ist im Layout-Smoke verankert.
- Interne Logs und Exceptions, Migrationstexte, technische IDs, konfigurierte
  Namen und synthetische Fixtures bleiben absichtlich außerhalb der
  UI-Kataloge. Der bestehende LocalBase-Konfliktlabel-Vertrag wird in diesem
  app-lokalen Schritt nicht verändert.

## Localeabhängige Formatierung

Die aktive Locale wird nativ aus `document.documentElement.dataset.locale`
gelesen; `lang` und schließlich `en` sind Fallbacks. Folgende feste
`de-DE`-Verwendungen werden zentral ersetzt:

- Uhrzeiten: `calendar-cell.js`, `entry-dialog.js`, `meeting-finder.js`
- Tage/Zeiträume: `meeting-finder.js`, `week-navigation.js`, `week-table.js`
- Suchnormalisierung: `calendar-filters.js`, `meeting-finder.js`
- feste Wochentagsliste: `shift-defaults.js`

Testgrenzen: Deutsch, Englisch, unbekannte/ungültige Locale, fehlender
Übersetzungsschlüssel, Singular/Plural, typisierte Platzhalter,
HTML-/Script-Sonderzeichen, DST, Jahresgrenze und längere englische Labels.

## Kataloge und Einbindung

- Englische Quelltexte sind die stabilen Nextcloud-L10N-Schlüssel.
- `l10n/de.js` und `l10n/de.json` liefern Deutsch; `en_GB` dient als zweite
  explizit geprüfte Locale, der englische Quelltext bleibt der Fallback.
- PHP verwendet ausschließlich `OCP\IL10N`; Templates verwenden `$l->t()`.
- JavaScript verwendet Nextclouds globales `t()`/`n()` über einen kleinen
  app-lokalen Adapter für Übersetzung und `Intl`-Formatierung.
- `translation('flzcalendar')` lädt den Clientkatalog auf Haupt- und
  Adminoberfläche. Übersetzte Werte werden nur über `textContent`, normale
  DOM-Attribute oder die bestehenden escaped Templateausgaben eingesetzt.
