# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Aktueller Fokus

- Wochen- und Monatsplanung, Meeting-Lückensuche, persönliche Standards und optionale Urlaubsmarkierungen auf einem realitätsnahen Staging fachlich abnehmen.
- Rollen-, Bereichs- und Personenfilter einschließlich bereichsübergreifender Leitungen in der sichtbaren Oberfläche prüfen.
- Die ergänzten globalen Gruppen Stv. PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang mit ihrer Backend-Reihenfolge und Hierarchie im Kalender abnehmen.
- Den einseitigen Abgleich persönlicher Dienste in den privaten Nextcloud-Kalender „AD Dienste“ fachlich abnehmen.
- Persönliche Kopano- und manuelle CalDAV-Verbindungen mit realen Testkonten auf Staging fachlich abnehmen.
- Die fachliche Abnahme der Google- und Apple-Verbindungen ist auf Mitte bis Ende August 2026 verschoben.

## Geplante Erweiterungen

- Für den Produktivbetrieb ist noch festzulegen, wie fehlgeschlagene Hintergrundläufe überwacht und administrativ sichtbar gemacht werden.
- Weitere Auswertungszeiträume über Woche und Monat hinaus werden nach einem konkreten Fachbedarf festgelegt.

## Vor bidirektionalen Synchronisationsstufen zu klären

- Konfliktauflösung, Löschungen und Zuständigkeit bei einem späteren Rückimport.
- Einwilligung, Datenschutz, Monitoring und Wiederholungsstrategie für einen späteren Rückimport.
- Serverseitige Rechteprüfung; eine Synchronisation erweitert niemals Planungs- oder Leserechte.
