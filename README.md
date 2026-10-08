# Filzmann Kalender

Wochen- und monatsbasierte Dienst- und Terminplanung mit wiederkehrenden Terminen, Personensuche, Gruppenfiltern, Meetinglückensuche, Standarddienstzeiten, read-only Urlaubsmarkierungen und persönlichem Export eigener Dienste, Termine und Urlaube nach Nextcloud sowie dienst-only externe Kalender.

Die Monatsansicht stellt den vollständigen sichtbaren Monatszeitraum in einer einzigen durchgehenden Planungsmatrix dar und lässt sich wie die Wochenansicht zwischen „Tage als Zeilen“ und „Personen als Zeilen“ umschalten. Sie wiederholt keine Wochenblöcke: Die gemeinsame Tagesachse wächst je nach Ausrichtung um Zeilen oder Spalten. Vor und nach dem gewählten Monat erscheinen jeweils höchstens drei abgedunkelte Randtage; die erste und letzte sichtbare Woche dürfen dadurch unvollständig sein. Die zu den Personen gehörende erste Spalte beziehungsweise Kopfzeile bleibt beim Scrollen sichtbar. Samstag und Sonntag werden ausschließlich über ihren Wochentagsnamen gekennzeichnet. Leere Wochenend- und Feiertagstage erscheinen kompakt und erhalten bei „Tage als Spalten“ eine feste schmale Breite. Sie wechseln nur bei vorhandenen Diensten oder Terminen automatisch auf Normalgröße; ein Urlaubsmarker allein verhindert die kompakte Darstellung nicht. Die Kalendermatrix scrollt innerhalb des sichtbaren Tabellenbereichs in beide Richtungen; ihre horizontale Leiste bleibt unabhängig von der Inhaltshöhe am unteren Rand dieses Bereichs erreichbar. Gesetzliche Feiertage der organisationsweit konfigurierten Kalenderregion werden über den gemeinsamen LocalBase-Kalendervertrag geliefert, namentlich gekennzeichnet und bleiben ohne Auswirkung auf Dienste, Termine oder Rechte. Berlin bleibt Bestandsdefault. Zeitraum und Ausrichtung können zusammen mit den Filtern als persönlicher Standard gespeichert werden.

## Staging-Kompatibilität

- Nextcloud 33 und 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 33/34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei FLZ-Fachprodukten optional aktiv
- App-ID und Installationsordner: `flzcalendar`

## Installation

Für Staging und Auslieferung das Produktbundle `flz-product-flzcalendar-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten FLZ-Fachprodukt aktiviert es OrgSuite.

Filzmann Kalender funktioniert einzeln. Ohne Filzmann Urlaubsplanung stehen manuelle Sperrtermine zur Verfügung; die read-only Urlaubsmarkierungen entfallen.

Ist FlzPlaner aktiv, erscheinen belegte Assistenzschichten über den
versionierten LocalBase-Konfliktvertrag als nicht bearbeitbare Sperrzeiten.
Breite Zellen zeigen `Assistenz`, schmale Zellen `AS`. Überlappende manuelle
und regelmäßige Kalenderdienste werden verhindert; ohne FlzPlaner bleibt der
Kalender unverändert eigenständig nutzbar.

Der Befehl `flzcalendar:demo:seed` ist ausschließlich für synthetische Testdaten gedacht und darf auf einem realitätsnahen Staging-System nicht ohne bewusste Entscheidung ausgeführt werden.

## Externe Kalender

Jede angemeldete Person verwaltet Kopano-, Google-, Apple- und manuelle CalDAV-Verbindungen im eigenen Tab `Einstellungen`. Filzmann Calendar erzeugt beim Anbieter einen sichtbaren Kalender mit dem administrativ konfigurierten Namen und exportiert ausschließlich Dienste. Ohne gesetzte Konfiguration bleibt `Filzmann Dienste` der Bestandsdefault. Anbieterinhalte werden nicht in Filzmann Calendar eingeblendet oder zurückimportiert.

- Nextcloud-Admins verwalten die Kopano-/CalDAV-Vorgabe und den sichtbaren Zielkalendernamen unter `Administrationseinstellungen` → `Filzmann Kalender` → `Kopano und CalDAV`. Ohne gesetzte Konfiguration bleibt die Serveradresse leer und muss bewusst eingetragen werden.
- Die Kopano-Adresse bleibt im persönlichen Verbindungsdialog änderbar. Bereits gespeicherte persönliche Serveradressen werden durch spätere Änderungen der administrativen Vorgabe nicht überschrieben.
- Vorhandene sicher erkannte app-eigene Kalender werden beim nächsten ausgehenden Abgleich auf den neuen sichtbaren Namen umbenannt. Technische Kalender-, Provider-, Objekt- und Ereigniskennungen bleiben unverändert; fremde Kalender werden nicht umbenannt.
- Der Kopano-Betreiber muss einen HTTPS-CalDAV-Endpunkt bereitstellen. HTTP 405 wird im Connector ausdrücklich als nicht freigegebener CalDAV-Zugriff erklärt; die notwendige Serverfreigabe kann nicht durch Filzmann Kalender erfolgen.
- Nextcloud-Admins können Adresse und Zugang im Filzmann-Kalender-Adminabschnitt mit einer ausschließlich lesenden CalDAV-Anfrage prüfen. Das Passwort wird weder gespeichert noch zurückgegeben; der Test legt keinen Kalender an.
- Apple und manuelles CalDAV verwenden ein Anbieter- beziehungsweise app-spezifisches Passwort.
- CalDAV-Ziele müssen HTTPS verwenden. Nextclouds HTTP-Client erzwingt zusätzlich seine serverseitige SSRF-Sperre.
- Persönliche Passwörter und Google-Tokens liegen verschlüsselt und als sensible Nextcloud-Benutzerkonfiguration vor.

Google benötigt einmalig einen systemweiten Web-OAuth-Client. Nextcloud-Admins hinterlegen ihn unter `Administrationseinstellungen` → `Filzmann Kalender` → `Google Calendar OAuth`. Die Oberfläche zeigt die installationsspezifische Redirect-URI, speichert das Secret als `lazy` und `sensitive` und gibt es nach dem Speichern nicht wieder aus.

In Google Cloud sind die Google Calendar API, ein OAuth-Zustimmungsbildschirm und ein OAuth-Client vom Typ `Webanwendung` erforderlich. Als autorisierte Redirect-URI wird exakt der Wert aus dem Filzmann-Kalender-Adminabschnitt übernommen. Technisch verwendet die App die Schlüssel `google_oauth_client_id` und `google_oauth_client_secret` sowie den eng begrenzten Scope für app-erzeugte Kalender.

Die allgemeine Form der Weiterleitungsroute lautet:

    <NEXTCLOUD-BASIS>/index.php/apps/flzcalendar/oauth/google/callback

Bei installationsweit aktivem Pretty-URL-Rewriting kann `index.php` entfallen. Maßgeblich ist ausschließlich der in der Adminoberfläche angezeigte Wert.

## Datenschutz-Auskunft

Ist `flz_data_protection` kompatibel aktiv, registriert Filzmann Kalender lazy
einen Standalone-V1-Provider für Nextcloud-Konten. Er weist subjectgebunden
eigene Dienste und Termine, bewusst gespeicherte Filterstandards,
Standard-Dienstzeiten, den explizit gespeicherten Zustand des privaten
Nextcloud-Kalenderabgleichs sowie vorhandene externe Kalenderanbieter aus.

Bei gemeinsamen Terminen werden weitere Beteiligte nur abstrakt genannt.
Ausgewählte Personen im persönlichen Filter werden ausschließlich gezählt.
Serveradressen, Kontonamen, Kalender- und Providerkennungen, Passwörter,
Tokens, OAuth-State und fremde Kalendereinträge werden weder entschlüsselt
noch ausgegeben. Der private Nextcloud-DAV-Kalender und externe Zielkalender
sind abgeleitete Kopien der führenden Filzmann-Kalenderdaten; ihre Objekte werden
nicht zusätzlich als zweite Datenquelle ausgelesen. Filzmann Kalender speichert
keine Daten in Files oder Team Folders.

Der app-eigene Processing-Katalog beschreibt Kalenderplanung, persönliche
Standards, externe Verbindungen, abgeleitete Kalenderveröffentlichungen und
temporäre Adminfreigaben über den öffentlichen V1-Vertrag des
Datenschutz-Centers. Er enthält ausschließlich Policy-Metadaten und keine
entschlüsselten Zugangsdaten oder personenbezogenen Laufzeitdatensätze.
Offene Rechtsgrundlagen, Retention-, Backup-, Restore- und
Drittlandentscheidungen bleiben als `PRIVACY-DECISION-REQUIRED` sichtbar.

## Zeitlich begrenzter Admin-Vollzugriff

Ein Nextcloud-Administrationskonto erhält nicht automatisch Zugriff auf alle Mitarbeiterkalender. Ausschließlich Mitglieder der Nextcloud-Gruppe `Datenschutzbeauftragte` verwalten die app-lokale Freigabe im Hauptbereich von Filzmann Kalender für ein aktives Administrationskonto; der Datenschutzrolle muss selbst kein nativer Adminstatus zugewiesen sein. Die Freigabe gilt für 1, 4, 8 oder höchstens 24 Stunden und kann vorzeitig widerrufen werden. Native Administration allein genügt weder für die Freigabesteuerung noch für den fachlichen Zugriff. Beginn, geplantes Ende, Freigabe und Widerruf werden app-lokal protokolliert und in Datenschutz- sowie Berechtigungsprovider einbezogen. Technische OAuth-, CalDAV- und Kalenderdefault-Konfiguration bleibt davon getrennt.

## Roadmap

Geplante Erweiterungen und offene Produktentscheidungen stehen in der [Roadmap](ROADMAP.md).

Für die fachliche Prüfung auf Staging steht ein ausfüllbares [manuelles Abnahmeformular](docs/manual-acceptance.md) bereit. Zugangsdaten, Tokens und personenbezogene Echtdaten werden darin nicht dokumentiert.

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [Filzmann Nextcloud Plugins-Projekt](https://github.com/Filzmann/flz-full-suite).

## Dokumentation

- [Architektur](docs/architecture.md)
- [Lokalisierungsinventar](docs/l10n-inventory.md)
- [Manuelle Abnahme](docs/manual-acceptance.md)
- [Roadmap](ROADMAP.md)
- [Changelog](CHANGELOG.md)
- [Arbeitsregeln](AGENTS.md)
