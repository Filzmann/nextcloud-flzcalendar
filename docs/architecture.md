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

Die angeklickte Kalenderzelle bestimmt die Mitarbeiter*innen-Zuordnung. Sie
wird im Dialog nur angezeigt und bleibt nach dem Anlegen unveränderlich; auch
ein grundsätzlich für beide Kalender berechtigter API-Akteur darf einen
Eintrag nicht durch Änderung der `employeeUid` verschieben.

Die Wochenansicht und die durchgehende Monatsmatrix unterstützen „Tage als
Zeilen“ und „Personen als Zeilen“. Die Monatsansicht wiederholt keine
Wochenblöcke: Ihr vollständiger sichtbarer Tagesbereich bildet je nach
Ausrichtung eine gemeinsame Zeilen- oder Spaltenachse. Je Monatsgrenze werden
höchstens drei Randtage ergänzt; unvollständige erste und letzte sichtbare
Kalenderwochen sind dafür zulässig. Personenachsen bleiben beim Scrollen
sichtbar, Randtage werden gedimmt und Wochenenden ausschließlich
über ihren Wochentagsnamen gekennzeichnet. Leere Wochenend- und Feiertagstage
verwenden eine kompakte Tagesachse und in der Tages-Spalten-Ausrichtung eine
feste schmale Breite; vorhandene Dienste oder Termine stellen den betroffenen
Tag für alle sichtbaren Personen auf die normale Größe zurück. Ein Urlaubsmarker
allein verhindert die kompakte Darstellung nicht.
Ein über Mitternacht reichender Dienst bleibt ein einziges Fachobjekt. Seine
Kalenderprojektion kappt die sichtbare Zeit je Tageszelle an `24:00`
beziehungsweise `00:00` und kennzeichnet die Fortsetzung zugänglich; die
gespeicherten Start- und Endzeitpunkte werden dabei nicht verändert.
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

Gruppenüberschriften und der mobile Organisationskontext verwenden die vom
LocalBase-Organisationsvertrag gelieferten Kürzel. Bereichsrollen verbinden
Rollen- und Bereichskürzel mit einem Bindestrich, beispielsweise `BO-NO` oder
`EB-W`; globale Rollen erscheinen beispielsweise als `PFK`, `BO-Pflege` oder
`IT`. Filter und Organisationsverwaltung verwenden weiterhin die Langnamen.

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

AD Kalender ist alleinige Quelle der Wahrheit. Der standardmäßig aktive
persönliche Abgleich veröffentlicht ausschließlich eigene Dienste, Termine und
Urlaube in einem privaten Nextcloud-Kalender mit dem administrativ
konfigurierten sichtbaren Namen. Ohne gesetzten AppConfig-Wert bleibt
„AD Dienste“ der Bestandsdefault. Urlaube kommen read-only und ohne Notizen
über LocalBase aus einem optionalen Provider. Ihr halboffener Horizont reicht
vom Beginn des laufenden fachlichen Kalenderjahres bis zum Beginn des dritten
Folgejahres. Ohne Provider bleibt der Urlaubsbestand leer.
Ein bewusstes Opt-out entfernt nur die
von AD Kalender erzeugten Objekte; fremde Objekte bleiben unangetastet und der
Kalender wird nur gelöscht, wenn er danach leer ist.

Deterministische Kalender-, Objekt- und Ereigniskennungen machen Wiederholungen
idempotent. DAV-Fehler rollen führende AD-Daten nicht zurück, sondern werden
sicher protokolliert. Der nicht parallele Hintergrundjob wird alle 15 Minuten
fällig, gleicht vorhandene Dienste vollständig ab, respektiert Opt-outs und
isoliert Fehler je Konto. Sein Adminstatus enthält nur Zeitpunkt, Richtung und
aggregierte Anzahlen.

Der interne DAV-Zugriff ist hinter `PersonalCalendarPublisher` gekapselt. Der
interne Nextcloud-DAV-Vertrag bleibt auf
`NextcloudDavShiftCalendarPublisher` begrenzt.

Die am 2. September 2026 geprüften öffentlichen Nextcloud-Verträge decken
diesen Publisher nicht vollständig ab: `OCP\Calendar\IManager` und
`OCP\Calendar\ICalendar` ermöglichen Ermittlung und lesenden Zugriff;
`OCP\Calendar\ICreateFromString` kann ein ICS-Objekt in einem bereits
schreibbaren Kalender anlegen. Öffentliche Verträge für das Anlegen,
Umbenennen und bedingte Löschen des app-eigenen Kalenders sowie für das
deterministische Aktualisieren und Löschen seiner Objekte fehlen. Deshalb
bleibt der private `OCA\DAV\CalDAV\CalDavBackend` ausschließlich innerhalb
des genannten Adapters zulässig. Seine Signaturen werden testbar gekapselt
und vor jeder Erweiterung der Nextcloud-Kompatibilität durch einen eigenen
Source- und Runtime-Nachweis geprüft. Eine loopback-basierte CalDAV-
Authentifizierung innerhalb derselben Nextcloud-Instanz ist kein Ersatz für
diese Grenze.

Beim Umbenennen reicht das Registrieren der Änderung über `PropPatch` nicht
aus: Der Adapter führt den Patch explizit mit `commit()` aus und behandelt
einen fehlgeschlagenen Commit als sichtbaren Fehler, bevor er Objekte
abgleicht.

## Responsive Kalenderdarstellung

Die Desktopdarstellung bleibt die durchgehende Wochen- beziehungsweise
Monatsmatrix. Bis einschließlich 700 Pixel Viewportbreite wird sie ausgeblendet
und aus denselben bereits gefilterten Kalenderdaten eine semantische Tagesliste
abgeleitet. Jede Tagesgruppe nennt Datum und gegebenenfalls Feiertag sichtbar;
Personenkarten nennen Person und Organisationskontext und verwenden für
Einträge, Urlaubsstatus und Aktionen denselben `CalendarCell`- und
`canManage`-Vertrag wie die Matrix. Die mobile Projektion ist keine zweite
Daten- oder Rechtequelle. Monatsgruppen sind einklappbar, Touch-Ziele
mindestens 44 Pixel groß und die mobile Ansicht erzeugt keinen horizontalen
Seiten-Scrollbar. Vertikal scrollt die gesamte mobile Oberfläche ausschließlich
am App-Root; die Kalender-Zwischencontainer wachsen mit der Tagesliste und
schneiden sie nicht ab. Ein tastaturbedienbarer Floating-Button scrollt diesen
App-Root zum Seitenanfang zurück.

Die Kopano-/CalDAV-Vorgabe und der sichtbare Kalendername stammen aus einer
zentralen, validierten Nextcloud-AppConfig-Quelle. Nur bestätigte
Nextcloud-Admins dürfen sie mit normalem CSRF-Schutz ändern. Eine
Namensänderung benennt beim nächsten ausgehenden Abgleich ausschließlich
Kalender um, deren App-Eigentum über die deterministische interne URI oder die
gespeicherte externe Kalender-URL beziehungsweise Provider-ID zusammen mit
dem bekannten bisherigen Namen belegt ist. Technische Kennungen bleiben
stabil; bei unklarem Eigentum wird abgebrochen.

## Externe Kalender

Kopano, Google, Apple und generisches CalDAV können parallel verbunden werden.
Sie erhalten einen sichtbaren, app-eigenen Kalender mit dem administrativ
konfigurierten Namen und
exportieren ebenfalls ausschließlich Dienste. Providerinhalte werden nicht in
AD Kalender eingeblendet oder zurückimportiert.

Die administrativ konfigurierte Kopano-Adresse ist nur die Vorgabe für neue
persönliche Verbindungen. Bereits gespeicherte persönliche Server- und
Kalenderadressen werden durch eine spätere Defaultänderung nicht ersetzt.
Fehlschläge bei einer fälligen Bestandsumbenennung verändern weder den
globalen Default noch führende AD-Daten und blockieren keine anderen Provider;
der nächste ausgehende Abgleich versucht sie erneut.

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

## Organisation, Rechte und optionale Urlaube und Planungskonflikte

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

Ist AD Urlaub aktiv, erscheinen geplante Urlaube als read-only `U?` und
genehmigte Urlaube als `U`. Beide Status blockieren neue und materialisierte
Standarddienste, lassen Sperrtermine zu und sind ausschließlich im AD Urlaub
bearbeitbar. Nur genehmigter Urlaub wird aus Meetinglücken entfernt. Im
persönlichen DAV-Kalender bleibt geplanter Urlaub davon unabhängig tentative
und transparent, genehmigter Urlaub confirmed und opaque. Ohne AD Urlaub
bleiben manuelle Sperrtermine der gültige Standalone-Weg.

Ist AdPlaner aktiv, konsumiert AD Kalender dessen belegte Schichten über den
versionierten `ScheduleConflictQueryEvent` ausschließlich read-only. Die
Kalenderantwort projiziert sie mit sicherem Label `Assistenz`, Provider-ID und
Verfügbarkeitsstatus; die UI zeigt sie ohne Bearbeitungsaktionen als
`Assistenz` oder bei schmaler Zelle als `AS`. Echte Überlappungen blockieren
manuelle und materialisierte Standarddienste, direkte Randberührungen nicht.
Fehlende Listener bilden den Standalone-Zustand. Providerfehler werden in der
Leseantwort als `unavailable` ausgewiesen und verhindern neue Dienste, statt
Konfliktfreiheit zu behaupten. Umgekehrt publiziert AD Kalender seine Dienste
datensparsam als `Dienst/Büro`; private Termintitel verlassen die App nicht.

## Zeitlich begrenzter fachlicher Admin-Vollzugriff

Native Nextcloud-Administration bleibt von fachlicher Kalenderberechtigung
getrennt. Vollzugriff entsteht nur für ein aktuell natives Administrationskonto
mit aktiver app-lokaler Freigabe und endet spätestens nach 24 Stunden. Nur
Mitglieder der kanonischen Nextcloud-Gruppe `Datenschutzbeauftragte` dürfen
Freigaben erteilen, widerrufen und deren Historie lesen; ein nativer Admin ohne
diese Rolle und gewöhnliche Konten werden ohne Zustandsänderung abgewiesen.

Die Steuerung liegt im authentifizierten AD-Kalender-Hauptbereich und bleibt
außerhalb der technischen Nextcloud-Administration. Schreibende Requests
verwenden den Nextcloud-CSRF-Schutz. Ein natives Administrationskonto ohne
aktive Freigabe sieht eine sichere Hinweismeldung; der Direktlink zur
Freigabesteuerung erscheint nur bei gleichzeitiger DPO-Rolle. Audit- und
Art.-15-Projektionen geben ausschließlich die subjectgebundene Beteiligung und
Zeitpunkte aus und neutralisieren Kennungen anderer beteiligter Personen.

## Processing-Metadaten

Der zusätzliche `ProcessingMetadataProvider` veröffentlicht den app-eigenen
Katalog `resources/privacy-processing.json` lazy über den öffentlichen
Standalone-V1-Vertrag des Datenschutz-Centers. Er trennt führende
Kalendereinträge, persönliche UserConfig-Werte, verschlüsselte externe
Verbindungen, abgeleitete Zielkalender und temporäre Adminfreigaben. Der
Katalog enthält keine personenbezogenen Laufzeitdaten oder entschlüsselten
Secrets und ersetzt fehlende fachliche Entscheidungen nicht durch technische
Defaults.

## Demo- und Legacy-Grenzen

WordPress-Code, Rollen, Nonces, Shortcodes, Tabellen und Bestandsdaten werden
nicht übernommen. Das Demo-Pack läuft nur nach ausdrücklicher Bestätigung,
verwendet neutrale synthetische Konten und übernimmt niemals fremde oder
LDAP-verwaltete Konten. Read-only LDAP-Gruppen brechen den Preflight vor der
ersten Mutation ab.
