# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Freigegebene Umsetzungsaufgaben

### ADC-ADMIN-DEFAULTS – Kalenderdefaults administrierbar machen

Status: bereit nach Entscheidung zum Verhalten vorhandener Kalender

- Kopano-/CalDAV-Vorgabe und sichtbaren Zielkalendernamen aus je einer
  validierten serverseitigen AppConfig-Quelle liefern und in
  PHP, JavaScript, Templates und Tests nur noch konsumieren.
- Bestehende Werte als Migrationsdefaults behalten. Technische DAV-IDs,
  reservierte URIs und Objektkennungen bleiben stabil.
- Bereits gespeicherte persönliche Verbindungen niemals mit einem neuen
  Default überschreiben. HTTPS-, SSRF-, Same-Origin-, Secret- und
  Adminschutz vollständig erhalten.
- Vor Implementierung entscheiden, ob eine Änderung des sichtbaren Namens
  vorhandene app-eigene Kalender idempotent umbenennt oder nur neue Kalender
  betrifft.
- Fresh Install, Upgrade, alte persönliche Verbindung, ungültige URL,
  Nichtadmin-Deny, Secret-Ausgabe, stabile DAV-ID und gewählte
  Umbenennungssemantik testen.

### ADC-L10N – AD Kalender vollständig lokalisieren

Status: bereit nach Auswahl einer Pilot-App und ihres l10n-Vertrags

- Feste `de-DE`-Formatierung, Wochentagslisten sowie sichtbare UI-, Admin-,
  Provider- und Fehlermeldungen auf aktive Nextcloud-Locale und
  Nextcloud-l10n umstellen.
- ISO-Daten, Zeitzonen, Serien-/Statuswerte, DAV-IDs und konfigurierte
  Kalendernamen unverändert lassen.
- Abkürzungen locale-fähig erzeugen; deutsche Ausgabe, eine weitere Locale,
  Fallback, Zeitumstellungen, Jahresgrenzen, Platzhalter und Escaping testen.
- Erst nach vollständiger Migration einen Rohtext-Check für AD Kalender
  verbindlich schalten.

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
