# Changelog

## Unreleased

- Nextcloud 35.0.1 durch Fresh Install, Upgrade 34→35 sowie Provider-,
  Berechtigungs-, Runtime-, UI-/API- und Asset-Smokes nachgewiesen und den
  unterstützten Bereich auf die lückenlosen Hauptversionen 33 bis 35
  erweitert. Der gekapselte private DAV-Adapter ist zusätzlich mit dem
  nativen Nextcloud-35-Backend für Erstellen, Lesen, Aktualisieren, Löschen,
  Reparaturabgleich, Opt-out und Fremdobjektschutz geprüft; Nextcloud 36
  bleibt ungeprüft.
- Die zeitlich begrenzte fachliche Adminfreigabe auf Mitglieder der
  Nextcloud-Gruppe `Datenschutzbeauftragte` begrenzt, die Steuerung aus dem
  technischen Adminbereich in den rollenabhängigen Hauptbereich verschoben
  und Allow-, Deny-, Manipulations-, UI-, Audit- und Providerprojektionen
  automatisiert abgesichert.
- Einen app-eigenen Processing-Metadata-Katalog für Kalenderplanung,
  persönliche Standards, externe Verbindungen, Kalenderableitungen und
  temporäre Adminfreigaben über den V1-Vertrag des Datenschutz-Centers
  veröffentlicht.
- Die interne Nextcloud-DAV-Grenze beim Umbenennen des app-eigenen Kalenders
  auf den erwarteten `Sabre\DAV\PropPatch`-Vertrag einschließlich explizitem
  Commit und sichtbarem Fehlerpfad korrigiert und durch einen fokussierten
  Adaptertest sowie reale DAV-Läufe abgesichert.
- Den Berechtigungsprovider-Listener an Nextclouds `IEventListener`-Vertrag
  gebunden, damit die eigenständige Berechtigungsmatrix ihn zuverlässig
  entdeckt.
- Nextcloud 33 und 34 durch Fresh Install, Upgrade mit synthetischen
  Bestandsdaten, reale Integrationsläufe, Provider, HTTP/API und Assets
  nachgewiesen und als unterstützten Bereich deklariert.
- Öffentliche Nextcloud-Kalenderverträge als unvollständigen Ersatz für den
  gekapselten DAV-Publisher dokumentiert; der private Adapter bleibt an einen
  eigenen Source- und Runtime-Kompatibilitätsnachweis gebunden.
- Dokumentations- und Steuerungsstruktur vereinheitlicht; die Roadmap auf
  verbleibende Abnahmen, Freigaben und Erweiterungen reduziert.
- FlzPlaner-Schichten als responsive read-only Sperrzeiten `Assistenz`/`AS`
  eingeblendet und überlappende manuelle sowie regelmäßige Dienste über den
  versionierten LocalBase-Konfliktvertrag verhindert.

## 0.15.0-rc.1

- Subjectgebundene persönliche Datenauskunft für eigene Dienste und Termine ergänzt; bei gemeinsamen Terminen werden weitere Beteiligte nur abstrakt erwähnt.
- Menschenlesbare Tabellenfelder, deutsche Kurzdatumswerte und ehrliche Aufbewahrungshinweise ohne erfundene Löschfrist bereitgestellt.
- Für Viewports bis 700 Pixel eine semantische, einklappbare Tagesliste aus denselben gefilterten Kalenderdaten wie die Desktopmatrix ergänzt.
- Person, Organisationskontext, Datum, Feiertag, Eintragstyp und Urlaubsstatus mobil ohne horizontal verschobene Desktopmatrix sichtbar gemacht.
- Mobile Eintragsaktionen an den unveränderten `canManage`-Vertrag gebunden und mit mindestens 44 Pixel großen Touch-Zielen versehen.
- Die mobile Tagesliste auf einen einzigen vertikalen App-Scroller umgestellt und einen Floating-Button für die Rückkehr zum Anfang ergänzt.
- Nachtdienste je Tageszelle mit gekapptem Teilzeitraum und Fortsetzungskennzeichnung dargestellt.
- Leere Dialogfehlermeldungen ausgeblendet, statt eine bedeutungslose Fehlerfläche anzuzeigen.
- Mitarbeiter*innen-Auswahl im Eintragsdialog durch eine feste Anzeige ersetzt und nachträgliche API-Umzuordnungen serverseitig gesperrt.
- Kalendergruppen in der Übersicht auf die zentralen Betriebskürzel wie `BO-NO`, `EB-W`, `PFK`, `BO-Pflege` und `IT` umgestellt.

## 0.14.0-rc.2

- Kalenderzeiten beim Schreiben und Lesen explizit als UTC behandelt, damit reale Datenbankzugriffe die lokale Uhrzeit von Terminserien über Sommerzeitgrenzen hinweg stabil halten.
- Bereits gespeicherte Serienvorkommen anhand ihrer Serienzeitzone einmalig und DST-sicher nach UTC korrigiert; ungültige Bestandszeilen werden sichtbar gemeldet und isoliert übersprungen.
- Reale DDEV-Smokes für Terminserien, Urlaubsregeln und DAV-Dienstsynchronisierung in einem app-lokalen Integrationslauf gebündelt.

## 0.14.0-rc.1

- Präzisierte Urlaubslogik umgesetzt: geplanter und genehmigter Urlaub blockieren Dienste und Standardmaterialisierung, während Sperrtermine möglich und bearbeitbar bleiben.
- Urlaubsmarker auf kompakte, schreibgeschützte `U?`-/`U`-Hinweise reduziert und ihre Wirkung gegen die reale Filzmann-Urlaubsplanung-Integration geprüft.
- Fokus-, Kontrast-, Sticky-, Scrollpositions-, Feiertags- und Eintragshöhen-Verträge für die Kalenderoberfläche nachgeschärft; den redundanten Typwähler entfernt.
- Gemeinsamen Leitungs-/Stabsfilter von IT, Sekretariat sowie Finanzen/Lohn getrennt.
- Direkten serverseitigen Mehrfachrollen-Deny und den administrativen Speicherweg mit selbstbereinigenden DDEV-Smokes abgesichert.

## 0.12.0-rc.11

- Kopano-/CalDAV-Fehlerdiagnose mit verständlicher HTTP-405-Meldung im persönlichen Connector und einem rein lesenden administrativen Verbindungstest ergänzt.
- Umschaltbare Zeilen-/Spaltenausrichtung auch in der Monatsansicht sowie fixierte Personenachse beim Scrollen abgesichert.
- Samstage, dunklere Sonntage, gesetzliche Berliner Feiertage und getrennte Markierungen für Heiligabend und Silvester ergänzt, ohne Tagesspalten zu verbreitern.
- Den DAV-Konsistenzjob bei Updates bestehender Installationen idempotent registriert.

## 0.12.0-rc.9

- Google-OAuth-Konfiguration im Nextcloud-Adminabschnitt von Filzmann Kalender ergänzt.
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
- Sichtbare externe Zielkalender „Filzmann Dienste“, einseitige Mehranbieter-Synchronisierung, Verbindungstest und sicheres Trennen umgesetzt.
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
