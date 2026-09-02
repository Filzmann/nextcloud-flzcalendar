# Roadmap – AD Kalender

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nextcloud-Kompatibilitätsgate

### ADC-NC-COMPAT – DAV-Grenze für OpenDesk/NC 33 und künftige Majors sichern

`info.xml` bleibt bei 34/34, bis der Kalender-Rename test-first auf den von
NC 33 und 34 erwarteten `Sabre\DAV\PropPatch`-Vertrag korrigiert ist.
Danach müssen Fresh Install/Upgrade, DI, Migrationen, Background-Job,
interner DAV-Abgleich, Providerfehler, Standalone-/LocalBase-Kombination,
Assets und sichtbare Oberfläche auf NC 33 grün sein. Jede höhere Major wird
lückenlos mit `verify-nextcloud-future-compatibility` belegt; der private
DAV-Port benötigt dabei einen eigenen Source- und Runtime-Nachweis.

## Offene Abnahmen

### ADC-STAGING-ACCEPTANCE – bestehenden Kernumfang fachlich abnehmen

- Wochen- und Monatsplanung, Meeting-Lückensuche, persönliche Standards,
  Rollen-/Bereichsfilter und Urlaubsmarkierungen auf Staging prüfen.
- Globale Rollen, Backend-Reihenfolge und Hierarchie sichtbar abnehmen.
- Internen DAV-Abgleich, administrativen Zielkalendernamen,
  Bestandsumbenennung und datensparsame Jobstatusanzeige prüfen.
- Ergebnisse ausschließlich in `docs/manual-acceptance.md` dokumentieren.

### ADC-L10N-ACCEPTANCE – deutsche und englische Oberfläche prüfen

- Deutsche und englische Kalender-/Adminoberfläche mit langen
  Beschriftungen, Pluralen und veröffentlichten DAV-/Google-Ereignistexten
  anhand des Abnahmeformulars prüfen.
- Technische IDs, Statuscodes, Rechteentscheidungen und Secrets bleiben von
  Übersetzungen unabhängig.

### ADC-MOBILE-ACCEPTANCE – reale Smartphone-Browserabnahme

- Kleine Viewports, beide Matrixausrichtungen, Filter, Menübedienung,
  eigene und berechtigte fremde Einträge, Zoom, Sticky-Kontext und
  Scrollverhalten in realen Browsern prüfen.
- Tastatur, sichtbaren Fokus und ausreichend große Touch-Ziele abnehmen.

## Zurückgestellte Erweiterungen

### Externe Kalenderprovider freigeben

- Scope ausdrücklich wieder öffnen und ausschließlich neutrale Testkonten
  verwenden.
- Kopano/manuelles CalDAV, Google und Apple hinsichtlich Export,
  Aktualisierung, Löschung, Trennung, Fehlerisolation, Fremdkalenderschutz,
  Secret-Schutz und Rückimportverbot prüfen.

### Betriebsstatus erweitern

Eine Jobhistorie, Benachrichtigung oder längere Aufbewahrung wird erst bei
konkretem Betriebsbedarf ergänzt. Vorher sind Aufbewahrungsdauer, Quittierung,
Retry und Löschweg festzulegen sowie Admin-Allow und Nichtadmin-/Anonym-Deny
zu testen.

### Weitere Auswertungszeiträume

Zusätzliche Zeiträume werden erst nach konkretem Fachbedarf festgelegt. Neue
Abfragen müssen begrenzt sein, Datums- und Zeitzonengrenzen validieren,
Leserechte erhalten und realistische Lastgrenzen nachweisen.

## Vor einem Rückimport zu entscheiden

- Zuständigkeit, Konfliktauflösung, Löschmarker und Einwilligung.
- Vollständiges Zustandsmodell, Idempotenz, Replay/Retry und Nebenläufigkeit.
- Rechteänderungen, manipulierte oder fremde Providerobjekte, veraltete ETags,
  Teilfehler, Wiederanlauf und datensparsame Diagnose.
