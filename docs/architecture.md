# Fach- und Integrationsarchitektur von AD Kalender

Diese Datei dokumentiert den geltenden Ist-Vertrag. Zukünftige Ziele und
offene Entscheidungen stehen ausschließlich in `ROADMAP.md`; kurze harte
Arbeits-, Rechte- und Testregeln stehen in `AGENTS.md`.

## Kalendereinträge und Ansichten

AD Kalender unterscheidet Dienste und Termine in einem gemeinsamen
Kalendereintragsmodell mit explizitem Typ. Dienste besitzen Mitarbeiter*in,
Beginn und Ende; ihr Titel ist optional. Termine besitzen einen sprechenden
Titel. Termine innerhalb eines Dienstes referenzieren ihn über
`parent_entry_id`; Termine ohne Parent sind Sperrtermine.

Die Wochenansicht und die durchgehende Monatsmatrix unterstützen „Tage als
Zeilen“ und „Personen als Zeilen“. Die Monatsansicht wiederholt keine
Wochenblöcke: Ihr vollständiger sichtbarer Tagesbereich bildet je nach
Ausrichtung eine gemeinsame Zeilen- oder Spaltenachse. Personenachsen bleiben
beim Scrollen sichtbar, Randtage werden gedimmt und Wochenenden ausschließlich
über ihren Wochentagsnamen gekennzeichnet. Leere Wochenend- und Feiertagstage
verwenden eine kompakte Tagesachse und in der Tages-Spalten-Ausrichtung eine
feste schmale Breite; vorhandene Dienste oder Termine stellen den betroffenen
Tag für alle sichtbaren Personen auf die normale Größe zurück. Ein Urlaubsmarker
allein verhindert die kompakte Darstellung nicht.
Der direkte App-Root füllt die verfügbare Fensterbreite; nur die innere
Kalendermatrix scrollt innerhalb des verfügbaren sichtbaren Bereichs in beide
Richtungen. Ihre horizontale Scrollleiste bleibt unabhängig von der Höhe des
Tabelleninhalts am unteren Rand des sichtbaren Kalender-Viewports; beide
Scrollrichtungen sind dort jederzeit erreichbar.
Gesetzliche Feiertage werden über den gemeinsamen read-only
LocalBase-Kalendervertrag geliefert und verändern weder Einträge, Verfügbarkeit
noch Rechte.

Filter nach Personen, Rollen und Bereichen verwenden den konfigurierten
Organisationsvertrag. Rollen und Bereiche werden jeweils als ODER-Auswahl und
miteinander als Schnittmenge ausgewertet. Die im Organisationsvertrag erste
passende Rolle ist die vorrangige Kalenderrolle. Persönliche Filter-,
Zeitraum- und Ausrichtungskonfigurationen werden nur durch „Zum Standard
machen“ als Nextcloud-Benutzerwert gespeichert.

## Standarddienste, Meetings und Serien

Jedes Konto kann persönliche Standard-Dienstzeiten je Wochentag speichern.
Bewusst gespeicherte Defaults werden beim Wochenaufruf materialisiert.
`default_modified` schützt Einzelabweichungen;
`default_deleted` bewahrt Löschungen als datumsbezogene Tombstones.

Die Meeting-Lückensuche schneidet Dienste ausgewählter Personen innerhalb
einer Woche und zieht Termine ab. Gemeinsame Blöcke werden nur atomar
gespeichert, geändert oder gelöscht, wenn der Akteur jeden Zielkalender nach
dem normalen Rechtevertrag bearbeiten darf. Zeitraum und Titel bleiben für
alle Beteiligten gemeinsam.

Einzeltermine und Sperrtermine können täglich, wöchentlich oder monatlich mit
Intervall und verpflichtendem Enddatum wiederholt werden. Eine Serie enthält
mindestens zwei und höchstens 500 materialisierte Vorkommen. Die beim Anlegen
verwendete IANA-Zeitzone hält lokale Uhrzeiten über Zeitumstellungen stabil;
nicht vorhandene Monatstage werden ausgelassen.

Serien werden vollständig vorgeprüft und atomar gespeichert. Ein blockierender
Urlaub oder eine andere serverseitige Sperre bricht die gesamte Mutation ab.
Einzelvorkommen können als Ausnahme oder gemeinsam mit der vollständigen Serie
bearbeitet und gelöscht werden. „Dieses und folgende“, Dienste und gemeinsam
verknüpfte Meetings gehören nicht zu diesem Serienvertrag.

Bestehende Einträge wechseln ihren Typ nicht. Beim Löschen eines Dienstes wird
ausdrücklich zwischen dem gemeinsamen Löschen zugeordneter Termine und deren
Erhalt als Sperrtermine gewählt.

## Persönlicher Kalenderabgleich

AD Kalender ist zunächst alleinige Quelle der Wahrheit. Der standardmäßig
aktive persönliche Abgleich veröffentlicht ausschließlich Dienste der
jeweiligen Person in einem privaten Nextcloud-Kalender „AD Dienste“. Termine
und Urlaube werden nicht übertragen. Ein bewusstes Opt-out entfernt nur die
von AD Kalender erzeugten Objekte; fremde Objekte bleiben unangetastet und der
Kalender wird nur gelöscht, wenn er danach leer ist.

Deterministische Kalender-, Objekt- und Ereigniskennungen machen Wiederholungen
idempotent. DAV-Fehler rollen führende AD-Daten nicht zurück, sondern werden
sicher protokolliert. Der nicht parallele Hintergrundjob wird alle 15 Minuten
fällig, gleicht vorhandene Dienste vollständig ab, respektiert Opt-outs und
isoliert Fehler je Konto. Sein Adminstatus enthält nur Zeitpunkt, Richtung und
aggregierte Anzahlen.

Der interne DAV-Zugriff ist hinter `ShiftCalendarPublisher` gekapselt. Der
interne Nextcloud-DAV-Vertrag bleibt auf
`NextcloudDavShiftCalendarPublisher` begrenzt.

## Externe Kalender

Kopano, Google, Apple und generisches CalDAV können parallel verbunden werden.
Sie erhalten einen sichtbaren, app-eigenen Kalender „AD Dienste“ und
exportieren ebenfalls ausschließlich Dienste. Providerinhalte werden nicht in
AD Kalender eingeblendet oder zurückimportiert.

Persönliche CalDAV-Zugangsdaten und Google-Tokens werden mit Nextclouds
Kryptodienst verschlüsselt als sensible Benutzerkonfiguration gespeichert.
Antworten und Logs enthalten keine Passwörter, Tokens, Konto- oder
Kalenderkennungen. Der Google-Web-OAuth-Client ist eine systemweite
App-Admin-Einstellung; sein Secret ist nur schreibbar, `lazy` und `sensitive`.

Provideradapter verwenden ausschließlich den Nextcloud-HTTP-Client.
Nutzerkonfigurierte CalDAV-Adressen müssen HTTPS verwenden, auf demselben
Ursprung bleiben und unterliegen Nextclouds SSRF-Schutz. Zugangsdaten werden
nie an einen Discovery-Ursprung auf einem anderen Host weitergereicht.
Providerfehler bleiben voneinander und von der führenden AD-Mutation isoliert.

## Organisation, Rechte und optionale Urlaube

Alle angemeldeten Nutzer*innen dürfen Kalenderdaten lesen und eigene Einträge
bearbeiten. Fremdbearbeitung folgt ausschließlich der konfigurierten
Organisationshierarchie und freigegebenen Peergrenzen; Nextcloud-Admins dürfen
alle Einträge verwalten. Jeder API-Endpunkt erzwingt dies serverseitig über
den zentralen `CalendarAccessService`. Listen werden bereits serverseitig auf
den erlaubten Personenkreis begrenzt.

Rollen, Bereiche, sichtbare Bezeichnungen, Reihenfolge, Peer-Fähigkeit,
Assistenzteam-Konventionen und Hierarchiekanten stammen aus
`AdOrganizationDefinition`. Fachcode führt kein paralleles Rollenregister.
Eine Änderung technischer Gruppen-IDs verschiebt keine bestehenden
Nextcloud-Mitgliedschaften.

Ist AD Urlaub aktiv, erscheinen geplante Urlaube als read-only `U?` ohne
Blockade. Genehmigte Urlaube erscheinen als `U`, blockieren neue Dienste und
Termine, verhindern Standarddienst-Materialisierung und werden aus
Meetinglücken entfernt. Ohne AD Urlaub bleiben manuelle Sperrtermine der
gültige Standalone-Weg.

## Demo- und Legacy-Grenzen

WordPress-Code, Rollen, Nonces, Shortcodes, Tabellen und Bestandsdaten werden
nicht übernommen. Das Demo-Pack läuft nur nach ausdrücklicher Bestätigung,
verwendet neutrale synthetische Konten und übernimmt niemals fremde oder
LDAP-verwaltete Konten. Read-only LDAP-Gruppen brechen den Preflight vor der
ersten Mutation ab.
