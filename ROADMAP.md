# Roadmap – AD Kalender

Diese Datei bündelt geplante Erweiterungen und offene Produktentscheidungen. Verbindliche Fach-, Sicherheits- und Architekturregeln stehen in `AGENTS.md`.

## Nextcloud-Kompatibilitätsgate

### ADC-NC-COMPAT – DAV-Grenze für OpenDesk/NC 33 und künftige Majors sichern

Status: `info.xml` bleibt bei 34/34. NC 33.0.7 enthält die verwendete private
`CalDavBackend`-Klasse, aber der Kalender-Rename übergibt derzeit ein Array,
obwohl NC 33 und 34 ein `Sabre\DAV\PropPatch` erwarten. Diese Abweichung wird
app-lokal test-first korrigiert. Danach müssen Fresh Install/Upgrade, DI,
Migrationen, Background-Job, interner DAV-Abgleich, Providerfehler,
Standalone-/LocalBase-Kombination, Assets und sichtbare Oberfläche auf NC 33
grün sein. Die obere Grenze wird je Major lückenlos über
`verify-nextcloud-future-compatibility` bestimmt; der private DAV-Port erhält
bei jeder Major einen eigenen Source- und Runtime-Nachweis.

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

## Dokumentierter L10n-Ist-Stand und nicht freigegebene Restarbeit

### ADC-L10N – AD Kalender vollständig lokalisieren

Status: automatische Umsetzung am 31. Juli 2026 abgeschlossen; jede weitere
L10n-Arbeit einschließlich der offenen manuellen Browserabnahme ist später
und nicht freigegeben. Die manuelle Abnahme von ADC-ADMIN-DEFAULTS bleibt
davon getrennt nach bewusster Entscheidung offen.

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
- Spätere, nicht freigegebene Restarbeit: die deutsche und englische
  Kalender-/Adminoberfläche einschließlich langer Beschriftungen, Pluralen
  und veröffentlichter DAV-/Google-Ereignistexte anhand des Abnahmeformulars
  prüfen.

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

- Der aktuelle Fokus bleibt auf Fehlerbehebung, Stabilisierung und dem
  verbindlichen bestehenden Funktionsumfang. Neue Funktionen bleiben
  zurückgestellt.
- Die manuellen Prüfungen werden im ausfüllbaren
  [`docs/manual-acceptance.md`](docs/manual-acceptance.md) dokumentiert.
- Wochen- und Monatsplanung, Meeting-Lückensuche, persönliche Standards und optionale Urlaubsmarkierungen auf einem realitätsnahen Staging fachlich abnehmen.
- Rollen-, Bereichs- und Personenfilter einschließlich bereichsübergreifender Leitungen in der sichtbaren Oberfläche prüfen.
- Die ergänzten globalen Gruppen Stv. PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang mit ihrer Backend-Reihenfolge und Hierarchie im Kalender abnehmen.
- Den einseitigen Abgleich eigener Dienste, Termine und bounded gelesener Urlaube in den privaten Nextcloud-Kalender mit dem konfigurierten Zielnamen fachlich abnehmen.
- Den administrativen Zielkalendernamen einschließlich interner
  Bestandsumbenennung mit Abschnitt G des Abnahmeformulars prüfen.
- Die aggregierte Adminanzeige des letzten Hintergrundabgleichs für Erfolg und
  Teilfehler prüfen; sie darf keine Konten, Kalender, Provider, URLs oder
  Fehlerdetails offenlegen.

## Geplante Erweiterungen

### ADC-MOBILE – smartphone-taugliche Kalenderansicht und kompakte Menüs

Status: technisch umgesetzt am 12. August 2026; automatische JavaScript-,
Layout-, Accessibility- und Rechteprojektionstests sind grün. Die reale
Smartphone-/Browserabnahme bleibt bis zum gebündelten manuellen Abnahmelauf
offen.

- Wochen- und Monatsplanung, persönliche Einträge und die wichtigsten
  Kalenderaktionen erhalten eine auf kleinen Smartphone-Viewports vollständig
  nutzbare responsive Darstellung. Eine lediglich horizontal verschiebbare
  Desktop-Matrix reicht nicht aus; Zeitbezug, Person, Eintragstyp, Status und
  erlaubte Aktionen müssen im mobilen Nutzungspfad verständlich bleiben.
- Filter, Ansichtsumschaltung und Aktionsmenüs werden kompakter gruppiert.
  Häufige Aktionen bleiben direkt auffindbar; Beschriftungen, aktiver Zustand,
  Tastaturbedienung, sichtbarer Fokus und ausreichend große Touch-Ziele werden
  nicht zugunsten geringerer Höhe oder Breite entfernt.
- Vor der Umsetzung werden mobile Kernabläufe und die nötige alternative
  Darstellung für breite Planungsmatrizen festgelegt. Tests decken mindestens
  kleine Viewports, beide Matrixausrichtungen, Filter und Menübedienung,
  Anlegen/Bearbeiten eigener Einträge, berechtigte Fremdbearbeitung,
  Fokusreihenfolge, Zoom, lange Beschriftungen, Sticky-Kontext sowie vertikales
  und gegebenenfalls lokal begrenztes horizontales Scrollen ab.

### Externe Kalenderprovider – für spätere Erweiterungsphase vorgemerkt

Status: durch Produktentscheidung vom 9. August 2026 aus dem aktuellen
Grundumfang und Freigabenachweis zurückgestellt. Der vorhandene Code für
Kopano/CalDAV, Google, Apple und manuelle CalDAV-Verbindungen bleibt bestehen,
erhält im aktuellen Stabilisierungslauf aber keine reale Providerfreigabe.

- Wiederaufnahme nur nach ausdrücklicher Öffnung dieses Scopes.
- Vor einer Freigabe neutrale reale Testprovider beziehungsweise Testkonten
  bereitstellen; keine produktiven Zugangsdaten für Entwicklung oder Abnahme
  verwenden.
- Kopano/manuelles CalDAV: Export, Aktualisierung, Löschung, Trennung,
  Rückimportschutz, parallele Fehlerisolation, Fremdkalenderschutz und
  Bestandsumbenennung prüfen.
- Google und Apple: Autorisierung, begrenzte Berechtigungen, Secret-Schutz und
  denselben einseitigen Dienstexportvertrag prüfen.
- Providerabhängige Lokalisierungs- und Sprachwechselprüfungen folgen in
  diesem späteren Lauf. Der bereits geprüfte lokale Sprachvertrag bleibt Teil
  des Grundumfangs.

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
