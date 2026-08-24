# AD Kalender

Wochen- und monatsbasierte Dienst- und Terminplanung mit wiederkehrenden Terminen, Personensuche, Gruppenfiltern, Meetinglückensuche, Standarddienstzeiten, read-only Urlaubsmarkierungen und persönlichem Export eigener Dienste, Termine und Urlaube nach Nextcloud sowie dienst-only externe Kalender.

Die Monatsansicht stellt den vollständigen sichtbaren Monatszeitraum in einer einzigen durchgehenden Planungsmatrix dar und lässt sich wie die Wochenansicht zwischen „Tage als Zeilen“ und „Personen als Zeilen“ umschalten. Sie wiederholt keine Wochenblöcke: Die gemeinsame Tagesachse wächst je nach Ausrichtung um Zeilen oder Spalten. Vor und nach dem gewählten Monat erscheinen jeweils höchstens drei abgedunkelte Randtage; die erste und letzte sichtbare Woche dürfen dadurch unvollständig sein. Die zu den Personen gehörende erste Spalte beziehungsweise Kopfzeile bleibt beim Scrollen sichtbar. Samstag und Sonntag werden ausschließlich über ihren Wochentagsnamen gekennzeichnet. Leere Wochenend- und Feiertagstage erscheinen kompakt und erhalten bei „Tage als Spalten“ eine feste schmale Breite. Sie wechseln nur bei vorhandenen Diensten oder Terminen automatisch auf Normalgröße; ein Urlaubsmarker allein verhindert die kompakte Darstellung nicht. Die Kalendermatrix scrollt innerhalb des sichtbaren Tabellenbereichs in beide Richtungen; ihre horizontale Leiste bleibt unabhängig von der Inhaltshöhe am unteren Rand dieses Bereichs erreichbar. Gesetzliche Feiertage der organisationsweit konfigurierten Kalenderregion werden über den gemeinsamen LocalBase-Kalendervertrag geliefert, namentlich gekennzeichnet und bleiben ohne Auswirkung auf Dienste, Termine oder Rechte. Berlin bleibt Bestandsdefault. Zeitraum und Ausrichtung können zusammen mit den Filtern als persönlicher Standard gespeichert werden.

## Staging-Kompatibilität

- Nextcloud 34
- PHP 8.3 oder neuer innerhalb des von Nextcloud 34 unterstützten Bereichs
- Laufzeitbasis: `localbase`; `orgsuite` ist ab zwei AD-Fachprodukten optional aktiv
- App-ID und Installationsordner: `adcalendar`

## Installation

Für Staging und Auslieferung das Produktbundle `ad-product-adcalendar-<release>.tar.gz` und dessen enthaltenes `install.sh` verwenden. Es prüft und installiert LocalBase automatisch; ab dem zweiten AD-Fachprodukt aktiviert es OrgSuite.

AD Kalender funktioniert einzeln. Ohne AD Urlaub stehen manuelle Sperrtermine zur Verfügung; die read-only Urlaubsmarkierungen entfallen.

Der Befehl `adcalendar:demo:seed` ist ausschließlich für synthetische Testdaten gedacht und darf auf einem realitätsnahen Staging-System nicht ohne bewusste Entscheidung ausgeführt werden.

## Externe Kalender

Jede angemeldete Person verwaltet Kopano-, Google-, Apple- und manuelle CalDAV-Verbindungen im eigenen Tab `Einstellungen`. AD Calendar erzeugt beim Anbieter einen sichtbaren Kalender mit dem administrativ konfigurierten Namen und exportiert ausschließlich Dienste. Ohne gesetzte Konfiguration bleibt `AD Dienste` der Bestandsdefault. Anbieterinhalte werden nicht in AD Calendar eingeblendet oder zurückimportiert.

- Nextcloud-Admins verwalten die Kopano-/CalDAV-Vorgabe und den sichtbaren Zielkalendernamen unter `Administrationseinstellungen` → `AD Kalender` → `Kopano und CalDAV`. Ohne gesetzte Konfiguration bleibt `https://mail.adberlin.org/` die Vorgabe.
- Die Kopano-Adresse bleibt im persönlichen Verbindungsdialog änderbar. Bereits gespeicherte persönliche Serveradressen werden durch spätere Änderungen der administrativen Vorgabe nicht überschrieben.
- Vorhandene sicher erkannte app-eigene Kalender werden beim nächsten ausgehenden Abgleich auf den neuen sichtbaren Namen umbenannt. Technische Kalender-, Provider-, Objekt- und Ereigniskennungen bleiben unverändert; fremde Kalender werden nicht umbenannt.
- Der Kopano-Betreiber muss einen HTTPS-CalDAV-Endpunkt bereitstellen. HTTP 405 wird im Connector ausdrücklich als nicht freigegebener CalDAV-Zugriff erklärt; die notwendige Serverfreigabe kann nicht durch AD Kalender erfolgen.
- Nextcloud-Admins können Adresse und Zugang im AD-Kalender-Adminabschnitt mit einer ausschließlich lesenden CalDAV-Anfrage prüfen. Das Passwort wird weder gespeichert noch zurückgegeben; der Test legt keinen Kalender an.
- Apple und manuelles CalDAV verwenden ein Anbieter- beziehungsweise app-spezifisches Passwort.
- CalDAV-Ziele müssen HTTPS verwenden. Nextclouds HTTP-Client erzwingt zusätzlich seine serverseitige SSRF-Sperre.
- Persönliche Passwörter und Google-Tokens liegen verschlüsselt und als sensible Nextcloud-Benutzerkonfiguration vor.

Google benötigt einmalig einen systemweiten Web-OAuth-Client. Nextcloud-Admins hinterlegen ihn unter `Administrationseinstellungen` → `AD Kalender` → `Google Calendar OAuth`. Die Oberfläche zeigt die installationsspezifische Redirect-URI, speichert das Secret als `lazy` und `sensitive` und gibt es nach dem Speichern nicht wieder aus.

In Google Cloud sind die Google Calendar API, ein OAuth-Zustimmungsbildschirm und ein OAuth-Client vom Typ `Webanwendung` erforderlich. Als autorisierte Redirect-URI wird exakt der Wert aus dem AD-Kalender-Adminabschnitt übernommen. Technisch verwendet die App die Schlüssel `google_oauth_client_id` und `google_oauth_client_secret` sowie den eng begrenzten Scope für app-erzeugte Kalender.

Die allgemeine Form der Weiterleitungsroute lautet:

    <NEXTCLOUD-BASIS>/index.php/apps/adcalendar/oauth/google/callback

Bei installationsweit aktivem Pretty-URL-Rewriting kann `index.php` entfallen. Maßgeblich ist ausschließlich der in der Adminoberfläche angezeigte Wert.

## Datenschutz-Auskunft

Ist `filzmann_data_protection` kompatibel aktiv, registriert AD Kalender lazy
einen Standalone-V1-Provider für Nextcloud-Konten. Er weist subjectgebunden
eigene Dienste und Termine, bewusst gespeicherte Filterstandards,
Standard-Dienstzeiten, den explizit gespeicherten Zustand des privaten
Nextcloud-Kalenderabgleichs sowie vorhandene externe Kalenderanbieter aus.

Bei gemeinsamen Terminen werden weitere Beteiligte nur abstrakt genannt.
Ausgewählte Personen im persönlichen Filter werden ausschließlich gezählt.
Serveradressen, Kontonamen, Kalender- und Providerkennungen, Passwörter,
Tokens, OAuth-State und fremde Kalendereinträge werden weder entschlüsselt
noch ausgegeben. Der private Nextcloud-DAV-Kalender und externe Zielkalender
sind abgeleitete Kopien der führenden AD-Kalenderdaten; ihre Objekte werden
nicht zusätzlich als zweite Datenquelle ausgelesen. AD Kalender speichert
keine Daten in Files oder Team Folders.

## Roadmap

Geplante Erweiterungen und offene Produktentscheidungen stehen in der [Roadmap](ROADMAP.md).

Für die fachliche Prüfung auf Staging steht ein ausfüllbares [manuelles Abnahmeformular](docs/manual-acceptance.md) bereit. Zugangsdaten, Tokens und personenbezogene Echtdaten werden darin nicht dokumentiert.

Installations-, Betriebs- und Abnahmeunterlagen stehen im öffentlichen [AD-Suite-Projekt](https://github.com/Filzmann/ad-suite).
