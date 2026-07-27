# Changelog

## 0.12.0-rc.11

- Kopano-/CalDAV-Fehlerdiagnose mit verständlicher HTTP-405-Meldung im persönlichen Connector und einem rein lesenden administrativen Verbindungstest ergänzt.
- Umschaltbare Zeilen-/Spaltenausrichtung auch in der Monatsansicht sowie fixierte Personenachse beim Scrollen abgesichert.
- Samstage, dunklere Sonntage, gesetzliche Berliner Feiertage und getrennte Markierungen für Heiligabend und Silvester ergänzt, ohne Tagesspalten zu verbreitern.
- Den DAV-Konsistenzjob bei Updates bestehender Installationen idempotent registriert.

## 0.12.0-rc.9

- Google-OAuth-Konfiguration im Nextcloud-Adminabschnitt von AD Kalender ergänzt.
- Client-ID, nur schreibbares sensitives Secret, automatisch erzeugte Redirect-URI, Konfigurationsstatus und Entfernen-Funktion umgesetzt.
- Aufklappbare Schritt-für-Schritt-Anleitung für Google-Cloud-Projekt, API, Zielgruppe, Scope, Webclient und exakte Redirect-URI im Adminbereich ergänzt.
- Speicherung und Entfernung zusätzlich zur Nextcloud-Adminroute serverseitig auf aktive Administrator*innen begrenzt und CSRF-geschützt.
- Secret-, Allow-/Deny-, Controller- und ausführbare Admin-UI-Tests ergänzt.

## 0.12.0-rc.8

- Umschalter zwischen Wochen- und Monatsansicht ergänzt.
- Monatsansicht als vollständig bedienbare Folge der betroffenen Wochenblöcke mit abgedunkelten Randtagen umgesetzt.
- Personenspalte beim horizontalen Scrollen fixiert; der gewählte Zeitraum wird in URL und persönlichem Standard beibehalten.
- Monatsdaten auf einen serverseitig validierten Bereich von höchstens sechs Wochen begrenzt und mit API-, Zustands-, Navigations- und Layouttests abgesichert.

## 0.12.0-rc.7

- Persönliche Verbindungen zu Kopano, Google, Apple und generischem CalDAV im Einstellungs-Tab ergänzt.
- Zugangsdaten und OAuth-Tokens mit Nextcloud verschlüsselt und als sensible Benutzerwerte gespeichert.
- Sichtbare externe Zielkalender „AD Dienste“, einseitige Mehranbieter-Synchronisierung, Verbindungstest und sicheres Trennen umgesetzt.
- Kopano mit der änderbaren Vorgabe `https://mail.adberlin.org` sowie Apple-/CalDAV-Anleitungen im barrierefreien Dialog ergänzt.
- Google-Webserver-OAuth mit engem Kalender-Scope, einmaligem Statuswert, Offline-Refresh und sicherer Widerrufsstrecke vorbereitet.
- CalDAV-, OAuth-, Geheimnis-, SSRF-/Origin-, Controller-, UI- und Mehranbieter-Verträge getestet.

## 0.12.0-rc.6

- Begrenzte tägliche, wöchentliche und monatliche Terminserien ergänzt.
- Einzel- und Gesamtbearbeitung sowie Einzel- und Gesamtlöschung mit unverändert serverseitiger Rechteprüfung umgesetzt.
- Serienmigration, Sommerzeit-, Urlaubs-, Atomaritäts-, UI- und DDEV-Persistenztests ergänzt.

## 0.12.0-rc.1

- Eigenständige Navigation ohne OrgSuite ergänzt.
- Kalenderfähigkeiten über den optionalen LocalBase-Integrationsvertrag veröffentlicht.
- Ungültige harte App-Abhängigkeiten aus den Nextcloud-Metadaten entfernt.

## 0.11.14-rc.1

- Öffentliche Projekt-, Quellcode- und Fehlerkanäle ergänzt.
- Veröffentlichungsvorbereitung mit neutralisierten Assistenzteam-Beispielen.

## 0.11.13-rc.1

- Erster reproduzierbarer Staging-Releasekandidat für Nextcloud 34 und PHP ab 8.3.
- Dynamische Organisation, Hierarchie und bereichsgebundene Bearbeitungsrechte.
- Standarddienste mit gelöschten Einzelvorkommen sowie Urlaubsintegration.
- DDEV-Integration und authentifizierte HTTP-Rechtematrix.
