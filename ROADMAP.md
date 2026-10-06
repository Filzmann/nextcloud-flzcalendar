# Roadmap – AD Kalender

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Offene Abnahmen

### ADC-STAGING-ACCEPTANCE – bestehenden Kernumfang fachlich abnehmen

- Wochen- und Monatsplanung, Meeting-Lückensuche, persönliche Standards,
  Rollen-/Bereichsfilter und Urlaubsmarkierungen auf Staging prüfen.
- Globale Rollen, Backend-Reihenfolge und Hierarchie sichtbar abnehmen.
- Internen DAV-Abgleich, administrativen Zielkalendernamen,
  Bestandsumbenennung und datensparsame Jobstatusanzeige prüfen.
- Die DPO-gesteuerte Adminfreigabe mit getrennten Konten für DPO, nativen
  Admin ohne DPO-Rolle und gewöhnliche Nutzung in DDEV oder Staging prüfen;
  Ablauf, Widerruf, Rollenverlust, CSRF und Tastaturbedienung einschließen.
- Ergebnisse ausschließlich in `docs/manual-acceptance.md` dokumentieren.

### ADC-STAGING-FOLLOWUP – Abweichungen der laufenden Abnahme schließen

- Den geöffneten Filter als überlagerndes Panel ausführen, damit er die
  Kalendermatrix auch bei kleinen Viewports nicht verdrängt; Fokus,
  Escape-Verhalten und erreichbare Scrollleisten mitprüfen.
- Den gemeinsamen Leitungs-/Stabs-Schalter ohne vorausgesetzte zusätzliche
  Bereichsauswahl bedienbar machen.
- Bereichsfilter für bereichsgebundene Rollen weiterhin als Schnittmenge
  anwenden, globale Rollen ohne natürliche Bereichszuordnung jedoch nicht
  allein wegen einer Bereichsauswahl ausblenden.
- Neutrale Testkonten für jeden konfigurierten Bereich und die relevanten
  Hierarchiepfade bereitstellen, damit Rollen-, Bereichs- und Deny-Fälle
  vollständig abgenommen werden können.
- Die systemweite UI-Nacharbeit der temporären Adminfreigabe folgt
  `DP-11` im Parent-Zukunftsplan; die app-lokalen Rechte- und Auditgrenzen
  bleiben unverändert.

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
