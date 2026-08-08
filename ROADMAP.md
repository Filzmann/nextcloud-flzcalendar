# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Umgesetzte Aufgaben mit offener manueller Abnahme

### ADC-ADMIN-DEFAULTS – Kalenderdefaults administrierbar machen

Status: technisch umgesetzt am 31. Juli 2026; lokale Regressionen und die
reale Nextcloud-DAV-Schnittstelle sind grün, die fachliche Staging-Abnahme
folgt mit `docs/manual-acceptance.md`

- Kopano-/CalDAV-Vorgabe und sichtbaren Zielkalendernamen aus je einer
  validierten serverseitigen AppConfig-Quelle liefern und in
  PHP, JavaScript, Templates und Tests nur noch konsumieren.
- Bestehende Werte als Migrationsdefaults behalten. Technische DAV-IDs,
  reservierte URIs und Objektkennungen bleiben stabil.
- Bereits gespeicherte persönliche Verbindungen niemals mit einem neuen
  Default überschreiben. HTTPS-, SSRF-, Same-Origin-, Secret- und
  Adminschutz vollständig erhalten.
- Eine Änderung des sichtbaren Namens benennt auch vorhandene app-eigene
  Kalender idempotent um. Fremde Kalender und technische DAV-IDs,
  reservierte URIs sowie Objektkennungen bleiben unverändert.
- Beide Werte werden vollständig serverseitig validiert, bevor AppConfig
  verändert wird. Die URL durchläuft denselben HTTPS-, SSRF- und
  Same-Origin-Vertrag wie persönliche Verbindungen; der sichtbare Name ist
  nicht leer, begrenzt, frei von Steuerzeichen und wird in HTML sowie XML
  kontextgerecht escaped.
- Speichern ist ausschließlich für bestätigte Nextcloud-Admins mit normalem
  CSRF-Schutz erlaubt. Nichtadmins und anonyme Requests verändern nichts;
  Antworten, Status und Logs enthalten weiterhin keine Zugangsdaten,
  Providerkennungen oder internen Kalenderkennungen.
- Vorhandene interne Kalender werden anhand ihrer deterministischen URI,
  externe Kalender ausschließlich anhand der gespeicherten stabilen
  Kalender-URL beziehungsweise Provider-ID erkannt. Kann App-Eigentum nicht
  sicher belegt werden, wird nicht umbenannt.
- Die Umbenennung erfolgt beim nächsten ausgehenden Abgleich wiederholbar je
  Konto und Provider. Ein Providerfehler nimmt den gültigen globalen Default
  nicht zurück, verändert keine führenden AD-Daten, blockiert keine anderen
  Konten oder Provider und wird über den bestehenden datensparsamen
  Fehlervertrag erneut versuchbar.
- Tests: Fresh Install ohne gesetzte Schlüssel, Upgrade aus dem bisherigen
  fest codierten Stand, wiederholtes Speichern, Teilfehler ohne halbe
  Konfiguration, alte persönliche Verbindung ohne URL-Überschreibung,
  ungültige/leere/überlange Werte und Steuerzeichen, Admin-Allow,
  Nichtadmin-/Anonym-Deny ohne Zustandsänderung, aktiver CSRF-Schutz,
  Secret-/Kennungs-Redaktion, XML-/HTML-Escaping, stabile DAV-/Provider-ID,
  interne und externe Bestandsumbenennung, fremder Kalender sowie
  isolierter Providerfehler mit erfolgreichem Wiederholungsversuch.

## Freigegebene Umsetzungsaufgaben

### ADC-L10N – AD Kalender vollständig lokalisieren

Status: automatische Umsetzung am 31. Juli 2026 abgeschlossen; manuelle
Browserabnahme offen. Die manuelle Abnahme von ADC-ADMIN-DEFAULTS bleibt nach
bewusster Entscheidung ebenfalls offen.

- Feste `de-DE`-Formatierung, Wochentagslisten sowie sichtbare UI-, Admin-,
  Provider- und Fehlermeldungen auf aktive Nextcloud-Locale und
  Nextcloud-l10n umstellen.
- ISO-Daten, Zeitzonen, Serien-/Statuswerte, DAV-IDs und konfigurierte
  Kalendernamen unverändert lassen.
- Abkürzungen locale-fähig erzeugen; deutsche Ausgabe, eine weitere Locale,
  Fallback, Zeitumstellungen, Jahresgrenzen, Platzhalter und Escaping testen.
- Serverseitig Nextclouds `IL10N` und clientseitig den nativen
  Nextcloud-l10n-Vertrag verwenden. Übersetzungen werden nicht als
  unkontrolliertes HTML eingesetzt; variable Werte laufen über typisierte
  Platzhalter und die bestehende kontextgerechte Ausgabe.
- Maschinenlesbare Status-, Fehler- und Rechteentscheidungen bleiben über
  sprachunabhängige Codes stabil. Weder Server noch Client leiten Rechte,
  Providerklassifikation oder Sicherheitsentscheidungen aus übersetzten
  Texten ab; Geheimnisse, URLs und technische Kennungen werden nicht als
  Übersetzungsparameter verwendet.
- Zuerst ein vollständiges Inventar der sichtbaren PHP-/Template-/JavaScript-
  Texte und Datumsformatierungen erstellen. Danach Server, Client und
  Formatierung in kleinen testgetriebenen Schritten migrieren; technische
  Werte, Rechteentscheidungen und Fehlerklassifikation bleiben unabhängig von
  der gewählten Sprache.
- Das vollständige Datei-, Maschinenvertrags- und Testinventar steht in
  [`docs/l10n-inventory.md`](docs/l10n-inventory.md) und wird bis zum
  abschließenden Rohtext-Gate schrittweise abgearbeitet.
- Umsetzungsstand 31. Juli 2026: Locale-Adapter und deutsche/englische
  Kataloge sind aktiv; öffentliche JSON-Fehler liefern stabile Codes und
  lokalisierte sichere Meldungen. Hauptoberfläche, persönliche Einstellungen,
  Adminbereich, Dialoge, JavaScript-Komponenten, Navigation, CLI sowie
  sichtbare Nextcloud-DAV-/Google-Ereignistexte verwenden englische
  L10N-Quellschlüssel. Server- und Clientkataloge sowie feste deutsche
  UI-Rohtexte werden automatisiert geprüft.
- Tests: Deutsch, Englisch als zweite Locale, unbekannte Locale/Fallback,
  Singular/Plural, fehlender Schlüssel, typisierte Platzhalter,
  HTML-/Script-Sonderzeichen, server- und clientseitig identische
  Schlüsselverwendung, DST/Jahresgrenze sowie Tastatur- und Layout-Smokes mit
  längeren übersetzten Beschriftungen.
- Der Rohtext- und Katalogcheck ist nach vollständiger Migration verbindlich.
  Offen bleibt die bewusst verschobene Sichtprüfung in realen deutschen und
  englischen Nextcloud-Sitzungen.

## Sicherheits- und Test-Gate für die nächsten Umsetzungen

- Vor Produktivcode werden Invariante, erlaubte und verbotene Ausgangszustände,
  Zielzustand, Nebenwirkungen, Wiederholungsverhalten, Teilfehler und Rückbau
  festgehalten. Beobachtbares Verhalten wird nach dem lokalen
  `test-driven-change`-Vertrag zuerst rot nachgewiesen.
- Neue Adminaktionen bleiben serverseitig deny by default, prüfen die
  Nextcloud-Adminrolle unabhängig von der UI und behalten den normalen
  CSRF-Schutz. Persönliche Aktionen bleiben auf das angemeldete Konto
  begrenzt; Hintergrundjobs erhalten keine zusätzlichen Fachrechte.
- AppConfig und UserConfig werden über native Nextcloud-Schnittstellen
  verwendet. Secrets bleiben sensitiv, verschlüsselt und nur schreibbar;
  URLs, technische IDs, Konten und Fehlerdetails werden nur ausgegeben, wenn
  der jeweilige öffentliche Vertrag sie ausdrücklich benötigt.
- Relevante PHP- und JavaScript-Suiten, Sicherheits-/Controller-Smokes,
  `git diff --check` und die passende reale DDEV-Integration müssen grün sein.
  Provider- und Browserwirkung, die lokal nicht realistisch isolierbar ist,
  wird zusätzlich mit `docs/manual-acceptance.md` dokumentiert.
- Für neuen oder wesentlich geänderten ausführbaren Code werden mindestens
  85 Prozent Line-Coverage je PHP/JavaScript-Teil angestrebt;
  Sicherheitsinvarianten sind unabhängig vom Prozentwert vollständig zu
  testen. Ausgelassene Integrationen und Restrisiken werden ausdrücklich
  berichtet.

## Aktueller Fokus

- Den persönlichen Nextcloud-Kalender vom bisherigen Dienstabgleich auf eigene Dienste, Termine und Urlaube erweitern. Vor der Umsetzung ist der bounded Discovery-/Zeitraumvertrag für Urlaube über die öffentliche LocalBase-Grenze festzulegen; direkte Zugriffe auf AD-Urlaub-Daten bleiben ausgeschlossen.
- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Die deutsche und englische Kalender-/Adminoberfläche einschließlich langer
  Beschriftungen, Pluralen und veröffentlichter DAV-/Google-Ereignistexte
  anhand des Abnahmeformulars prüfen.
- Wochen- und Monatsplanung, Meeting-Lückensuche, persönliche Standards und optionale Urlaubsmarkierungen auf einem realitätsnahen Staging fachlich abnehmen.
- Rollen-, Bereichs- und Personenfilter einschließlich bereichsübergreifender Leitungen in der sichtbaren Oberfläche prüfen.
- Die ergänzten globalen Gruppen Stv. PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang mit ihrer Backend-Reihenfolge und Hierarchie im Kalender abnehmen.
- Den einseitigen Abgleich persönlicher Dienste in den privaten Nextcloud-Kalender mit dem konfigurierten Zielnamen fachlich abnehmen.
- Persönliche Kopano- und manuelle CalDAV-Verbindungen mit realen Testkonten auf Staging fachlich abnehmen.
- Administrative Kopano-/CalDAV-Vorgabe und Zielkalendername einschließlich interner und externer Bestandsumbenennung mit Abschnitt G des Abnahmeformulars prüfen.
- Die aggregierte Adminanzeige des letzten Hintergrundabgleichs für Erfolg und
  Teilfehler prüfen; sie darf keine Konten, Kalender, Provider, URLs oder
  Fehlerdetails offenlegen.
- Die fachliche Abnahme der Google- und Apple-Verbindungen ist auf Mitte bis Ende August 2026 verschoben.

## Geplante Erweiterungen

- Die aggregierte Anzeige des letzten Hintergrundlaufs ist umgesetzt. Eine
  Historie, Benachrichtigung oder längere Aufbewahrung wird erst bei konkretem
  Betriebsbedarf erweitert und muss weiterhin admin-only, mengenbegrenzt und
  frei von Konten, Kalendern, Providern, URLs, Zugangsdaten und Fehlerdetails
  bleiben. Vor einer Erweiterung sind Aufbewahrungsdauer, Quittierung,
  Wiederholungsstrategie und Löschweg ausdrücklich festzulegen und mit
  Erfolg, Teilfehler, Wiederholung sowie Nichtadmin-/Anonym-Deny zu testen.
- Weitere Auswertungszeiträume über Woche und Monat hinaus werden erst nach
  einem konkreten Fachbedarf festgelegt. Jeder neue Bereich ist serverseitig
  begrenzt, validiert Zeitzone und Datumsgrenzen, erweitert keine Leserechte
  und schützt API sowie Oberfläche vor unbegrenzt großen Abfragen. Tests
  umfassen Mindest-/Höchstgrenze, ungültige und umgekehrte Bereiche,
  DST/Jahreswechsel, Berechtigungsscope und eine realistische Lastgrenze.

## Vor bidirektionalen Synchronisationsstufen zu klären

- Konfliktauflösung, Löschungen und Zuständigkeit bei einem späteren Rückimport.
- Einwilligung, Datenschutz, Monitoring und Wiederholungsstrategie für einen späteren Rückimport.
- Serverseitige Rechteprüfung; eine Synchronisation erweitert niemals Planungs- oder Leserechte.
- Vor einer Freigabe sind ein vollständiges Zustandsmodell für lokale und
  externe Änderungen, deterministische Konfliktentscheidungen, Löschmarker,
  Idempotenz, Replay-/Retry-Verhalten und Nebenläufigkeit festzulegen.
- Tests müssen manipulierte und fremde Providerobjekte, veraltete ETags,
  doppelte und verspätete Ereignisse, Teilfehler, Wiederanlauf, Entzug der
  Einwilligung, Löschkonflikte, Rechteänderungen während des Abgleichs sowie
  datensparsame Logs und Adminanzeigen abdecken. Ein Rückimport bleibt bis zu
  dieser ausdrücklichen Fach- und Sicherheitsfreigabe außerhalb des Scopes.
