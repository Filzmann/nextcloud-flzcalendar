# AGENTS.md - Filzmann Kalender

## Projekt

Nextcloud-App `flzcalendar` fuer die uebersichtliche Dienstplanung der Mitarbeiter*innen im Buero.

Lokale App-URL:

    https://nextcloud-dev.ddev.site/apps/flzcalendar/

Nextcloud-App-ID:

    flzcalendar

Die priorisierte Produktplanung und offene Entscheidungen stehen in `ROADMAP.md`; verbindliche Fach-, Sicherheits- und Architekturregeln bleiben in dieser Datei.
Der ausführliche geltende Ist-Vertrag steht in `docs/architecture.md`; diese
Datei hält die bei jeder Arbeit benötigten Grenzen und Prüfungen.

## Zielsetzung und Fachkontext

Filzmann Kalender uebertraegt den fachlichen Kern des bisherigen WordPress-Plugins `flz_calendar` in eine eigenstaendige Nextcloud-App. WordPress-Code, Rollen, Nonces, Shortcodes und die Abhaengigkeit `flz-wpdb-objects` werden nicht uebernommen.

Kernprozess:

- Eine Wochenansicht zeigt Mitarbeiter*innen als Zeilen und Kalendertage als Spalten.
- Eine umschaltbare Monatsansicht zeigt den gesamten sichtbaren Monatszeitraum in einer einzigen durchgehenden Planungsmatrix und unterstützt wie die Wochenansicht „Tage als Zeilen“ sowie „Personen als Zeilen“. Sie wiederholt weder Wochenplanung noch Tabellenköpfe je Kalenderwoche: Bei „Tage als Zeilen“ wächst die gemeinsame Tagesachse um Zeilen, bei „Personen als Zeilen“ um Spalten. Vor und nach dem gewählten Monat erscheinen jeweils höchstens drei gedimmte Randtage; dafür dürfen die erste und letzte sichtbare Kalenderwoche unvollständig sein. Die zu den Personen gehörende erste Spalte beziehungsweise Kopfzeile bleibt beim Scrollen sichtbar. Samstag und Sonntag werden flächig sowie ausschließlich über ihren Wochentagsnamen gekennzeichnet. Leere Wochenend- und Feiertagstage werden in beiden Ausrichtungen platzsparend dargestellt; bei „Tage als Spalten“ besitzen sie eine feste kompakte Spaltenbreite. Sobald für eine sichtbare Person ein Dienst oder Termin vorliegt, verwendet der gesamte Tag wieder die normale Größe. Ein Urlaubsmarker allein verhindert die kompakte Darstellung nicht. Gesetzliche Feiertage der organisationsweit konfigurierten Kalenderregion werden serverseitig über den gemeinsamen read-only LocalBase-Vertrag geliefert und erscheinen mit ihrem Namen als reine Anzeigeebene. `DE`, `DE-BE` und `Europe/Berlin` bleiben Bestandsdefaults; persönliche Zeitzonen beeinflussen nur individuelle Terminanzeigen. Feiertage verändern weder Einträge noch Verfügbarkeit oder Rechte.
- Dienste besitzen Mitarbeiter*in, Beginn und Ende. Der Titel bleibt bei Diensten optional.
- Die Mitarbeiter*innen-Zuordnung eines Eintrags wird durch die angeklickte Kalenderzelle festgelegt und bleibt nach dem Anlegen unveränderlich. Eine spätere Verschiebefunktion benötigt einen eigenen ausdrücklich freigegebenen Fachvertrag.
- Termine besitzen zusaetzlich einen sprechenden Titel.
- Termine innerhalb eines Dienstes werden diesem Dienst in der Darstellung zugeordnet.
- Termine ausserhalb eines Dienstes gelten fachlich als Sperrtermine und werden deutlich, nicht nur farblich, gekennzeichnet.
- Die Wochenmatrix verwendet für alle sichtbaren Zellen ein gemeinsames komprimiertes Tages-Zeitraster. Einträge werden dadurch zeitlich vergleichbar ausgerichtet; freie Intervalle erscheinen als Abstand, ohne die Ansicht auf eine starre 24-Stunden-Höhe aufzublähen.
- Pro Mitarbeiter*in werden Dienstanzahl und gesamte Dienstzeit fuer den sichtbaren Zeitraum ausgewiesen.
- Filter nach Mitarbeiter*innen und Nextcloud-Gruppen sollen die Ansicht begrenzen.
- Rollen und Bereiche werden jeweils als ODER-Auswahl und miteinander als Schnittmenge ausgewertet. Ein expliziter Rollenfilter wertet nur die nach Organisationsreihenfolge vorrangige Rolle einer Person aus; eine zusätzliche unterstellte Rolle zieht BL oder StvBL nicht in den BO- beziehungsweise EB-Filter. Ohne Rollenwahl zeigen gewählte Bereiche alle zugeordneten BL, stellvertretenden BL, BO und EB. Ohne Personen-, Rollen- und Bereichswahl erscheinen alle Personen mit einer im Filzmann Kalender sichtbaren Planerrolle.
- Gruppenüberschriften und mobiler Organisationskontext verwenden die zentralen LocalBase-Kürzel. Bereichsrollen erscheinen beispielsweise als `BO-NO` oder `EB-W`, globale Rollen als `PFK`, `BO-Pflege` oder `IT`; Filter und Administration behalten die Langnamen.
- Ohne bewusst gespeicherten Standard startet die Ansicht mit den Fachrollen und Bereichen des eingeloggten Kontos. Die aktuelle Filter-/Personen-/Ansichtskonfiguration wird nur ueber „Zum Standard machen“ als persoenlicher Nextcloud-Benutzerwert gespeichert.
- `flz-Stab-HR`, `flz-Stab-QMB`, `flz-AsdGF-Digi`, `flz-PDL`, beide GF-Rollen und `flz-Sekretariat` werden im Rollenfilter ueber einen gemeinsamen Anzeigen-/Ausblenden-Schalter gesteuert; der Schalter ist Teil des persoenlichen Standards. Diese Personen stehen in einem gemeinsamen Block und werden entlang der GF-AS- und GF-Digi-Hierarchie sortiert.
- Eine Meeting-Lückensuche schneidet die Dienste der ausgewählten Personen innerhalb einer Kalenderwoche und zieht deren vorhandene Termine ab; die Mindestdauer ist frei wählbar und beträgt initial 60 Minuten. Bleibt eine Woche ohne Treffer, kann wochenweise weitergesucht oder eine Person direkt abgewählt werden.
- Gefundene Slots können mit einem gemeinsamen Titel atomar für alle ausgewählten Personen blockiert werden, wenn der angemeldete Nutzer jeden Zielkalender nach dem normalen Hierarchie-/Peer-Vertrag bearbeiten darf. Die Funktion erweitert keine Fremdbearbeitungsrechte und prüft die Verfügbarkeit unmittelbar vor dem Speichern erneut.
- Die dabei je Person gespeicherten Termine tragen eine gemeinsame Meeting-Kennung. Zeitraum und Titel werden für alle Beteiligten gemeinsam geändert, und das Meeting wird nur vollständig gelöscht. Beide Aktionen sind nur erlaubt, wenn der angemeldete Nutzer weiterhin jeden beteiligten Kalender bearbeiten darf; isolierte Änderungen über die allgemeine Eintrags-API werden abgewiesen.
- Jedes angemeldete Konto kann im eigenen Einstellungs-Tab Standard-Dienstzeiten je Wochentag speichern. Sie dienen als Vorschlag beim Anlegen; ein Ende vor dem Beginn bildet einen Dienst bis zum Folgetag.
- Bewusst gespeicherte Standard-Dienstzeiten werden beim Aufruf einer Woche als normale Dienste materialisiert. Individuell bearbeitete Vorkommen bleiben einmalige Abweichungen; ein geloeschtes Vorkommen bleibt fuer genau dieses Datum dauerhaft unterdrueckt.
- Zeitwerte werden serverseitig eindeutig gespeichert und fuer die Anzeige in der konfigurierten Nextcloud-Zeitzone formatiert.
- Filzmann Kalender bleibt bei der Kalendersynchronisation die alleinige Quelle der Wahrheit. Der persönliche Abgleich ist standardmäßig aktiv und erzeugt für Konten mit eigenen Diensten, Terminen oder Urlauben einen privaten Nextcloud-Kalender mit dem administrativ konfigurierten sichtbaren Namen; ohne gesetzten AppConfig-Wert bleibt „Filzmann Dienste“ der Bestandsdefault. Veröffentlicht werden ausschließlich Einträge der jeweiligen Person. Urlaube werden über den öffentlichen LocalBase-Vertrag read-only und ohne Notizen bezogen; der begrenzte Horizont umfasst das laufende fachliche Kalenderjahr und die zwei folgenden Kalenderjahre. Im veröffentlichten Kalender ist geplanter Urlaub tentative und transparent, genehmigter Urlaub confirmed und opaque. Diese DAV-Transparenz ändert nicht die interne Dienstplanungsregel. Ohne Filzmann Urlaubsplanung bleibt dieser Anteil leer. Ein Rückimport ist nicht Bestandteil dieser Ausbaustufe.
- Das Opt-out entfernt alle von Filzmann Kalender erzeugten Dienst-, Termin- und Urlaubsobjekte. Fremde Objekte im reservierten Kalender bleiben unangetastet; der Kalender selbst wird nur gelöscht, wenn er danach leer ist. DAV-Fehler nach einer fachlichen Mutation rollen die führenden FLZ-Daten nicht zurück, sondern werden sicher protokolliert. Deterministische Kalender-, Objekt- und Ereigniskennungen halten Wiederholung und Statuswechsel idempotent.
- Ein zeitunkritischer, nicht paralleler Nextcloud-Hintergrundjob wird im 15-Minuten-Intervall fällig; der tatsächliche Start richtet sich nach der Nextcloud-Cron-Ausführung. Er leitet Konten ausschließlich aus eigenen Filzmann-Kalendereinträgen, ausdrücklichen persönlichen Einstellungen, verbundenen externen Dienstzielen und der begrenzten Urlaubs-Discovery ab, gleicht den privaten Bestand vollständig ab und respektiert gespeicherte Opt-outs; der Fehler einer Person blockiert keine weiteren Konten.
- Der Adminstatus dieses Hintergrundjobs speichert und zeigt ausschließlich Zeitpunkt sowie aggregierte Anzahlen des letzten ausgehenden Laufs. Konto-, Kalender- und Fehlerkennungen bleiben ausgeschlossen; eine explizite Richtungsangabe im internen Statusvertrag hält spätere getrennte Import-/Export-Aggregate offen.
- Jede angemeldete Person kann im eigenen Einstellungs-Tab Kopano, Google, Apple und einen manuellen CalDAV-Anbieter verbinden. Kopano verwendet die administrativ konfigurierte Vorgabe; ohne gesetzten AppConfig-Wert bleibt die Serveradresse leer und muss bewusst eingetragen werden. Die Adresse bleibt im persönlichen Dialog änderbar und eine bereits gespeicherte persönliche Serveradresse wird durch spätere Defaultänderungen nicht überschrieben. Externe Anbieter erhalten einen sichtbaren, app-eigenen Kalender mit dem administrativ konfigurierten Namen; ihre Kalenderinhalte werden nicht in Filzmann Calendar eingeblendet.
- Nextcloud-Admins verwalten Kopano-/CalDAV-Vorgabe und sichtbaren Zielkalendernamen im app-eigenen Adminabschnitt. Beide Werte werden vor jeder AppConfig-Mutation vollständig serverseitig validiert. Eine Namensänderung wird beim nächsten ausgehenden Abgleich nur auf anhand stabiler URI beziehungsweise Provider-ID und des gespeicherten bisherigen Namens sicher erkannte app-eigene Kalender angewendet; technische Kalender-, Provider-, Objekt- und Ereigniskennungen bleiben stabil. Fremde Kalender werden nicht umbenannt, Providerfehler bleiben isoliert und werden beim nächsten Abgleich erneut versucht.
- Persönliche CalDAV-Zugangsdaten und Google-Tokens werden mit Nextclouds Kryptodienst verschlüsselt und als sensible Benutzerkonfiguration gespeichert. Antworten und Logs enthalten weder Passwörter, Tokens, Konto- noch Kalenderkennungen. Google benötigt einen systemweit administrierten OAuth-Webclient; ohne ihn bleibt der persönliche Verbindungsweg deaktiviert.
- Der Google-OAuth-Webclient wird im app-eigenen Nextcloud-Adminabschnitt konfiguriert. Das Client-Secret ist nur schreibbar, wird als sensible lazy AppConfig gespeichert und niemals an Templates oder Statusantworten zurückgegeben; die installationsspezifische Redirect-URI wird dort kopierbar angezeigt.
- Nextcloud-Admins können im app-eigenen Adminabschnitt eine Kopano-CalDAV-Adresse mit temporär eingegebenen Zugangsdaten rein lesend prüfen. Der Test verwendet denselben Kopano-Pfadvertrag wie die persönliche Verbindung, speichert keine Zugangsdaten und legt keinen Kalender an.
- Persönliche Providerverbindungen können parallel bestehen und arbeiten unabhängig vom Opt-out für den internen Nextcloud-Kalender. Ein Providerfehler blockiert weder andere Provider noch die führende FLZ-Mutation. Der Abgleich bleibt einseitig und überträgt auch extern ausschließlich Dienste.
- Das Kalender-Demo-Pack wird ausschließlich nach ausdrücklicher Bestätigung im app-eigenen Nextcloud-Adminabschnitt installiert; `flzcalendar:demo:seed` delegiert auf denselben Service. Es synchronisiert neutrale, benannte Demokonten für jede Kalenderrolle und jeden Bürobereich. Namen tragen die fachliche Demo-Zuordnung in Klammern; Mehrfachrollen werden auch in Gruppentiteln als Hauptrolle mit weiteren Rollen in Klammern dargestellt.
- Demo-Provisioning übernimmt niemals ein vorhandenes fremdes oder LDAP-verwaltetes Konto. Read-only LDAP-Gruppen brechen das Pack im Preflight vor der ersten Mutation ab; eigene lokale Demokonten werden explizit in LocalBase registriert.
- Ist `flzurlaub` aktiviert, erscheinen geplante Urlaube read-only als `U?` und genehmigte Urlaube als `U`. Beide Status blockieren neue und materialisierte Standarddienste, lassen Sperrtermine zu und sind ausschließlich im Filzmann Urlaubsplanung bearbeitbar. Nur genehmigter Urlaub wird aus Meetingluecken entfernt. Genehmigungen mit bestehenden Eintraegen werden ueber einen read-only Konfliktvertrag bereits in `flzurlaub` abgelehnt.

Verbindliches Gruppenschema:

Die folgenden Gruppen-IDs beschreiben ausschließlich die initiale Standardkonfiguration. Gruppen-IDs, sichtbare Namen, zentrale Kalenderkürzel, Bereiche, Reihenfolge, Peer-Fähigkeit, Assistenzteam-Konventionen und direkte Hierarchiekanten werden gemeinsam über `FlzOrganizationDefinition` geliefert. Bearbeitbare Organisationsfelder werden im Nextcloud-Adminbereich der OrgSuite gepflegt. App-Code darf daneben keine parallelen Rollenregister führen.

- Rollen werden als eigenständige Nextcloud-Gruppen gepflegt. Bereichsgebundene Kalenderrollen sind insbesondere `flz-BL`, `flz-StvBL`, `flz-Buero` und `flz-EB`; `flz-StvPDL`, `flz-Bueroorganisation-Pflege`, `flz-PFK`, `flz-Fahrzeugverwaltung` und `flz-Empfang` bleiben global.
- Bereiche werden separat als `flz-Bereich-<Name>` gepflegt.
- `flz-BL`, `flz-StvBL`, `flz-Buero` und `flz-EB` werden einem oder mehreren Buero-Bereichen zugeordnet. `flz-PFK`, `flz-Stab-HR` und `flz-Stab-QMB` sind bereichsunabhaengig; versehentliche Bereichsmitgliedschaften duerfen ihre Kalenderdarstellung oder Rechte nicht veraendern.
- Kombinierte Gruppen sind abgeleitete Schnittmengen, zum Beispiel Mitgliedschaft in `flz-EB` und `flz-Bereich-Nordost`; es werden keine Kombinationsgruppen dupliziert.
- `flz-EB` und `flz-PFK` sind Zielrollen im Kalender; die EB-Rolle allein verleiht keine globale Fremdbearbeitung.
- `flz-EB` und `flz-Bereich-*` sind derselbe kanonische Rollen-/Bereichsvertrag wie im FlzPlaner; kombinierte Altgruppen wie `flz-EB-*` werden nicht als Rollenquelle verwendet.
- Alle angemeldeten Nutzer*innen duerfen alle Kalenderdaten lesen; alle duerfen eigene Eintraege bearbeiten.
- Alle angemeldeten Nutzer*innen duerfen aus den ohnehin sichtbaren Kalenderdaten gemeinsame Meetingluecken berechnen.
- Das gemeinsame Blockieren nutzt ausschließlich bestehende Bearbeitungsrechte für jede einzelne ausgewählte Person; fehlt eines davon, wird kein Teil des Meetings gespeichert.
- `flz-PDL` führt `flz-StvPDL`, `flz-Bueroorganisation-Pflege` und `flz-PFK`. `flz-StvPDL` führt Büroorganisation Pflege sowie Pflegefachkräfte und steht im Pflegebereich des Kalenders an erster Stelle.
- Bueroleitungen werden wie BO dynamisch aus `flz-BL` plus `flz-Bereich-*` gebildet. BL NOW ist Mitglied in `flz-BL`, `flz-Bereich-Nordost` und `flz-Bereich-West` und wird dadurch in BL-NO sowie BL-W gefunden.
- Stellvertretungen werden aus `flz-StvBL` plus genau ihrem `flz-Bereich-*` gebildet; ihre zusaetzliche Hauptberufsrolle `flz-EB` bleibt davon getrennt.
- In der initialen Kalenderreihenfolge stehen Einsatzbegleitungen unter stellvertretenden Büroleitungen und über Büromitarbeiter*innen. Die im Adminbereich gespeicherte Organisationsreihenfolge ist maßgeblich; bei Mehrfachmitgliedschaft bleibt die erste passende Rolle die vorrangige Kalenderrolle.
- Fahrzeugverwaltung folgt initial auf IT und ist GF-Digi unterstellt. Empfang folgt auf das Sekretariat und ist diesem direkt unterstellt. Beide Teams bleiben außerhalb des gemeinsamen Leitungs-/Stabsblocks.
- Die Zahl der Bueroleitungen und Stellvertretungen wird nicht festgeschrieben. Eine bereichsuebergreifende BL erscheint als eine Person mit allen zugeordneten Bereichen und wird durch jeden passenden Bereichsfilter gefunden; sie wird nicht als doppelte Kalenderzeile dargestellt.
- `flz-Stab-HR` und `flz-Stab-QMB` sind sichtbare Stabsstellen; gegenseitige Bearbeitung innerhalb der jeweiligen Gruppe wird ueber den Peer-Schalter gesteuert.
- Peer-Bearbeitung kann im Nextcloud-Adminbereich der OrgSuite getrennt fuer die peer-fähigen Fachrollen aktiviert werden. Sie ist standardmaessig aus. Bei BO und EB gilt sie nur innerhalb mindestens eines gemeinsamen Buerobereichs; PFK und Stabsstellen bleiben mangels Buerobereich innerhalb ihrer Fachgruppe berechtigt.
- Assistent*innen erscheinen nicht in dieser App; ihre Planung bleibt im FlzPlaner.
- Nextcloud-Admins duerfen alle Eintraege verwalten.

Hierarchie fuer Fremdbearbeitung:

- `flz-GF-AS` fuehrt PDL, beide Bueroleitungen, deren Stellvertretungen, PFK, BO, EB, HR, QMB und das Sekretariat direkt oder indirekt.
- `flz-GF-Digi` führt `flz-AsdGF-Digi`, `flz-Leitung-Finanzen-Lohn`, `flz-Finanzen-Lohn`, IT, Fahrzeugverwaltung, das Sekretariat und darüber den Empfang direkt oder indirekt.
- `flz-AsdGF-Digi` fuehrt `flz-IT`; `flz-Leitung-Finanzen-Lohn` fuehrt `flz-Finanzen-Lohn`.
- `flz-PDL` führt die stellvertretende PDL, Büroorganisation Pflege und PFK; die stellvertretende PDL führt Büroorganisation Pflege und PFK.
- Bueroleitungen und ihre bereichsbezogenen Stellvertretungen fuehren ausschliesslich BO und EB im passenden Buerobereich, nicht PFK.
- `flz-Sekretariat` ist beiden Geschaeftsfuehrungen direkt zugeordnet.
- Leitungsrollen schuetzen immer vor Peer-Bearbeitung durch unterstellte Hauptberufsgruppen. Eine StvBL kann zum Beispiel zugleich EB sein, darf aber niemals durch normale EBs bearbeitet werden.
- Vorgesetzte duerfen Kalenderdaten aller direkt und indirekt unterstellten Personen bearbeiten; Untergebene duerfen keine uebergeordneten Rollen bearbeiten.

Offene Fachentscheidungen:
- Dienste derselben Person duerfen sich nicht ueberschneiden; dadurch bleibt die Terminzuordnung eindeutig.
- Welche Auswertungszeitraeume neben Woche und Monat benoetigt werden.
- Die offenen Entscheidungen für externe Systeme, Wiederholungsmechanismen und einen möglichen späteren Rückimport sind in `ROADMAP.md` beschrieben.

Nicht Bestandteil:

- WordPress-Bestandsdaten werden nicht importiert. Es gibt keine Legacy-Importstrecke; Test- und Vorführdaten stammen ausschließlich aus bewusst installierten synthetischen Demo-Packs.

## Rechte- und Zugriffsschutz

Es gilt deny by default fuer schreibende Zugriffe:

- Sehen: alle angemeldeten Nutzer*innen.
- Eigene Eintraege anlegen/aendern/loeschen: alle angemeldeten Nutzer*innen.
- Fremde Eintraege anlegen/aendern/loeschen: nur mit expliziter Planungsberechtigung.
- Mitarbeiter*innen und Gruppenzuordnung verwalten: nur App-Administration.
- Jeder API-Endpunkt prueft die Berechtigung serverseitig ueber einen zentralen `CalendarAccessService`.
- UI-Ausblendungen sind Komfort und niemals die einzige Zugriffskontrolle.
- Der Einstellungs-Tab ist fuer alle sichtbar und enthält nur persönliche Einstellungen des eingeloggten Kontos. Organisationsweite Gruppenbearbeitungsrechte werden ausschließlich im Nextcloud-Adminbereich der OrgSuite gelesen und geändert.
- Die persönliche Kalenderaktivierung kann ausschließlich für das angemeldete Konto geändert werden. Sie ist standardmäßig aktiv, kann als Opt-out deaktiviert werden und erweitert keine Planungs- oder Leserechte; berechtigte Fremdänderungen lösen nur die Veröffentlichung des ohnehin erlaubten Dienstes im aktiven Kalender der Zielperson aus.
- Listen werden bereits serverseitig auf den erlaubten Personenkreis eingeschraenkt.
- Allow- und Deny-Faelle sowie direkte unberechtigte API-Aufrufe werden getestet.

Die Gruppenlogik wird zentral implementiert und serverseitig erzwungen.

Eine Änderung technischer Gruppen-IDs verschiebt keine bestehenden Nextcloud-Gruppenmitgliedschaften. Zielgruppen und Mitgliedschaften müssen vor einer Umstellung in Nextcloud vorbereitet werden.

Urlaubsansichten sind dynamisch ergänzbare Rollen-/Bereichsschnitte. Die Standardansichten Büro Nordost, Büro West und Büro Süd bleiben getrennt; eine bereichsübergreifende Büroleitung erscheint aufgrund ihrer Mitgliedschaften in allen passenden Ansichten.

## Architektur

- Filzmann Kalender registriert einen subjectgebundenen PersonalDataProvider lazy
  über den öffentlichen Standalone-V1-Vertrag von
  `flz_data_protection`. Er liefert eigene Dienste und Termine sowie
  tatsächlich gespeicherte persönliche Filter-, Standarddienst- und
  Kalendersynchronisationswerte. Externe Verbindungen werden ausschließlich
  als Anbieter- und OAuth-Vorhandenseinsmetadaten ausgewiesen; Serveradressen,
  Kontonamen, Kalenderkennungen, Passwörter, Tokens und OAuth-State werden
  dafür nicht entschlüsselt oder ausgegeben. Bei einem gemeinsamen Meeting
  fragt das Repository nur ab, ob weitere Beteiligte existieren; deren UIDs,
  Namen und fremde Einträge gelangen nicht in den Bericht. Ausgewählte
  Personen eines persönlichen Filters werden ebenfalls nur gezählt. Zeiten
  verwenden die gemeinsame fachliche Organisationszeitzone. Der private
  Nextcloud-DAV-Kalender und externe Kalender sind abgeleitete Darstellungen
  der bereits ausgewiesenen führenden Daten und werden nicht als zweite
  Datenwahrheit aus fremden Kalenderobjekten gelesen. Solange keine
  Kalender-Retention-Policy freigegeben ist, wird ehrlich „keine feste
  Löschfrist“ ausgewiesen.
- Filzmann Kalender registriert zusätzlich einen `ProcessingMetadataProvider` lazy
  über den öffentlichen V1-Vertrag des Datenschutz-Centers. Seine einzige
  fachliche Policyquelle ist `resources/privacy-processing.json`; sie enthält
  keine personenbezogenen Laufzeitdaten oder entschlüsselten Secrets und
  markiert ungeklärte Entscheidungen als `PRIVACY-DECISION-REQUIRED`.
- Controller bleiben duenn.
- `CalendarAccessService` buendelt Sicht-, Erstell-, Aenderungs- und Loeschrechte.
- Repository-/Store-Klassen kapseln Datenzugriffe und QueryBuilder-Parameter.
- Fachregeln fuer Zeitraeume, Zuordnung und Summen liegen in Services und Value Objects.
- Persistente Kernobjekte nutzen `get(...)`, `get_all([...])`, `toArray()` und nur bei Store-Bindung `save()`.
- Dienste und Termine werden als ein gemeinsamer Kalendereintrag mit explizitem Typ modelliert; die fachliche Darstellung eines externen Termins als Sperrtermin wird abgeleitet und nicht als widerspruechliche zweite Datenwahrheit gespeichert.
- Termine innerhalb eines Dienstes referenzieren diesen explizit ueber `parent_entry_id`; Termine ohne Parent sind Sperrtermine.
- Einzeltermine und Sperrtermine können täglich, wöchentlich oder monatlich mit Intervall und verpflichtendem Enddatum wiederholt werden. Eine Serie enthält mindestens zwei und höchstens 500 materialisierte Vorkommen; Monate ohne den gewählten Kalendertag werden ausgelassen. Die lokale Uhrzeit bleibt anhand der beim Anlegen verwendeten IANA-Zeitzone über Zeitumstellungen stabil.
- Ein Vorkommen einer Terminserie kann als Ausnahme einzeln oder gemeinsam mit der vollständigen Serie bearbeitet und gelöscht werden. „Dieses und folgende“ ist nicht Bestandteil dieser Ausbaustufe. Dienste und gemeinsam verknüpfte Meetings werden nicht über diesen Serienvertrag wiederholt.
- Serien werden vollständig vorgeprüft und atomar gespeichert. Eine serverseitige Sperre an einem Vorkommen bricht die gesamte Mutation mit Datumsangabe ab; kein Vorkommen wird stillschweigend ausgelassen. Urlaub blockiert Dienste, nicht Sperrtermine.
- Materialisierte Standarddienste tragen ein eindeutiges Mitarbeiter*innen-/Datumsmerkmal. `default_modified` schuetzt Einzelabweichungen vor spaeterer Seriensynchronisierung; `default_deleted` bewahrt eine Loeschausnahme als Tombstone.
- Bestehende Eintraege duerfen ihren Typ nicht wechseln; Dienst und Termin haben unterschiedliche Folge- und Loeschvertraege.
- Ein über Mitternacht reichender Dienst bleibt ein einziges Fachobjekt, wird in jeder betroffenen Tageszelle jedoch nur mit seinem dortigen Teilzeitraum dargestellt: am Starttag bis `24:00`, am Folgetag ab `00:00`, jeweils mit sicht- und zugänglicher Fortsetzungskennzeichnung.
- Beim Loeschen eines Dienstes muss zwischen gemeinsamem Loeschen der Termine und deren Erhalt als Sperrtermine gewaehlt werden.
- API-Zugriffe liegen im Frontend in Repositories, Daten in Modellen/ViewModels und Rendering/Eventbindung in Komponenten.
- Der Frontend-Unterbau nutzt die vorhandenen LocalBase-Vertraege `ApiClient`, `Repository`, `Model` und `Notice`; Filzmann-Kalender-spezifische API-Pfade, Modelle und Renderinglogik bleiben in diesem Repo.
- Sichtbare Gruppenbezeichnungen, Filter, Leitungsblock, Hierarchiedarstellung und Demo-Gruppenzuordnungen werden aus der gemeinsamen Organisationsdefinition abgeleitet.
- Die Fachschicht spricht für den privaten Nextcloud-Kalender ausschließlich `PersonalCalendarPublisher` an; dienst-only externe Ziele bleiben hinter `ShiftCalendarPublisher` beziehungsweise ihren Provideradaptern. Der bewusst freigegebene interne Nextcloud-DAV-Vertrag `OCA\DAV\CalDAV\CalDavBackend` bleibt auf `NextcloudDavShiftCalendarPublisher` begrenzt und kann durch einen anderen Provideradapter ersetzt werden.
- Externe Provideradapter verwenden ausschließlich den Nextcloud-HTTP-Client. Nutzerkonfigurierte CalDAV-Adressen müssen HTTPS nutzen, bleiben auf denselben Ursprung begrenzt und unterliegen zusätzlich Nextclouds SSRF-Schutz; Zugangsdaten werden nie an einen Discovery-Ursprung auf einem anderen Host weitergereicht.
- `ShiftCalendarReconciliationService` respektiert native Nextcloud-Opt-outs und stellt den vollständigen privaten Bestand aus eigenen Diensten, Terminen und bounded gelesenen Urlauben wieder her. Background-Jobs erhalten dadurch keine zusätzlichen Planungs- oder Leserechte, enumerieren nicht pauschal alle Nextcloud-Konten und erzeugen keine zweite Fachdatenhaltung.
- Der direkte App-Root nutzt die vollständige verfügbare Fensterbreite; breite und hohe Kalendermatrizen scrollen ausschließlich in ihrem sichtbaren inneren Tabellenwrapper. Dessen horizontale Scrollleiste bleibt unabhängig von der Inhaltshöhe am unteren Rand des sichtbaren Kalender-Viewports und zusammen mit der vertikalen Scrollleiste jederzeit erreichbar.
- Bis einschließlich 700 Pixel Viewportbreite ersetzt eine semantische, nach Tagen gruppierte mobile Liste die ausgeblendete Desktopmatrix. Sie verwendet dieselben gefilterten Personen, Einträge, Urlaube und `canManage`-Entscheidungen, zeigt Person, Organisationskontext, Datum, Eintragstyp und Status ohne horizontales Seitenscrollen und bietet nur bereits erlaubte Aktionen mit mindestens 44 Pixel großen Touch-Zielen an. Die gesamte mobile Oberfläche scrollt vertikal ausschließlich am App-Root; Kalender-Zwischencontainer schneiden die Tagesliste weder ab noch erzeugen weitere vertikale Scrollbereiche. Ein tastaturbedienbarer Floating-Button springt zum Anfang dieses App-Scrollers zurück. In der Monatsansicht sind die Tagesgruppen einklappbar; der erste Tag des gewählten Monats ist initial geöffnet. Der auf der mobilen Liste wirkungslose Matrix-Ausrichtungsumschalter bleibt dort verborgen.
- Die UI bleibt per Tastatur bedienbar, verwendet semantische Tabellen/Listen, sichtbare Fokuszustaende und Textkennzeichnungen zusaetzlich zu Farben.
- Keine vorsorgliche gemeinsame Library und keine WordPress-Kompatibilitaetsschicht.

## Repository und gemeinsamer Arbeitsablauf

- Dieses Verzeichnis ist ein eigenstaendiges Git-Repository.
- Diese Datei und lokal referenzierte Skills bilden bei einem direkten Start in diesem Repository die vollständige Repository-Steuerung.
- Fuer Git-, Sandbox-, DDEV-/`occ`-Sicherheit, Verifikation und Learning Candidates gilt der lokal mitgefuehrte Skill `work-in-nextcloud-app`; die folgenden Kalender-Regeln und Pruefungen ergaenzen ihn.

## DDEV

Die gemeinsame Umgebung wird aus dem dokumentierten Parent-Unterverzeichnis
`nextcloud-dev` gesteuert. Bei einem eigenständigen Checkout ist der lokale
DDEV-Pfad zuerst anhand der realen Umgebung zu ermitteln.

Geplante Checks:

    ddev exec -d /var/www/html/html php occ status
    ddev exec -d /var/www/html/html php occ app:list | grep -i flzcalendar

## Tests

- Schnelle PHP-Suite: `php tests/run.php`
- Schnelle JavaScript-Suite: `node tests/run-js.mjs`
- Fachregeln erhalten Unit-/Charakterisierungstests vor Repository- und Controller-Anbindung.
- Berechtigungstests decken mindestens Lesen, eigene Bearbeitung, Fremdbearbeitung und direkte Deny-Aufrufe ab.
- Bei Migration, Controller, DI oder Nextcloud-Container zusaetzlich gezielte DDEV-/`occ`-Checks.
- Testdaten bleiben kuenstlich, neutral und datenschutzarm.
- Authentifizierter App-/API-Smoke: `FLZC_BASE_URL=... FLZC_USER=... FLZC_PASSWORD=... tests/http-smoke.sh`
- Serverseitiger Rechte-Smoke: `FLZC_BASE_URL=... FLZC_USER=... FLZC_PASSWORD=... FLZC_EXPECTED='uid=true,...' tests/access-http-smoke.sh`
- Selbstaufräumende DDEV-Rollenmatrix: `FLZC_BASE_URL=https://nextcloud-dev.ddev.site tests/access-matrix-ddev-smoke.sh`
- Selbstaufräumender DDEV-Adminstatus-Smoke: `FLZC_BASE_URL=https://nextcloud-dev.ddev.site tests/reconciliation-status-ddev-smoke.sh`
- Reale Tombstone-/Urlaubsintegration in DDEV: `ddev exec -d /var/www/html/html php custom_apps/flzcalendar/tests/integration/DefaultShiftVacationSmoke.php`
- Selbstaufräumender persönlicher DAV-Dienstabgleich in DDEV: `ddev exec -d /var/www/html/html php custom_apps/flzcalendar/tests/integration/ShiftCalendarSyncSmoke.php`

## Verbindliche Navigation und optionale Integration

- Ohne aktive OrgSuite registriert Filzmann Kalender einen eigenen Nextcloud-Hauptnavigationseintrag. Ab zwei FLZ-Produkten ersetzt `orgsuite` diesen durch den gemeinsamen Einstieg `FLZ`.
- Das Template stellt den optionalen Menühost mit `data-suite="flz"` und `data-current-app="flzcalendar"` bereit, lädt aber keine OrgSuite-Assets direkt.
- Ohne Filzmann Urlaubsplanung bleiben Sperrtermine der manuelle Abwesenheitsweg; fehlende optionale Provider dürfen die Wochenansicht nicht verhindern.
- Fachliche Lese- und Bearbeitungsrechte bleiben ausschliesslich serverseitig im Filzmann Kalender; Menuesichtbarkeit ist keine Berechtigung.
- Native Nextcloud-Administration erteilt keinen fachlichen Kalender-Vollzugriff. Er setzt pro Administrationskonto eine aktive, app-lokale Freigabe von höchstens 24 Stunden voraus; Beginn, geplantes Ende und Widerruf bleiben historisch protokolliert. Ausschließlich Mitglieder der Nextcloud-Gruppe `Datenschutzbeauftragte` verwalten Freigaben und Historie im Filzmann-Kalender-Hauptbereich; native Administration allein genügt weder für die Steuerung noch für den fachlichen Zugriff.
- Ein natives Administrationskonto ohne aktive Freigabe erhält im Hauptbereich eine sichere Hinweismeldung. Der Direktlink zur Freigabesteuerung erscheint nur, wenn dasselbe Konto zugleich Mitglied von `Datenschutzbeauftragte` ist.
- Technische Appkonfiguration wie OAuth und CalDAV bleibt native Administration. Das Installieren fachlicher Demodaten benötigt dagegen die aktive app-lokale Vollzugriffsfreigabe.
- Änderungen an Freigabehistorie oder Kalenderrechten werden gleichzeitig im PersonalDataProvider und PermissionProvider nachgeführt.

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand,
  Installation, Betrieb, Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/architecture.md` ist die ausführliche Quelle für geltende fachliche
  und technische Architekturverträge.
- `docs/manual-acceptance.md` enthält wiederholbare manuelle Prüfungen und
  keine Produktplanung.
- `AGENTS.md` enthält ausschließlich verbindliche Arbeits-, Sicherheits-,
  Architektur- und Prüfregeln. Zusätzliche Dokumente werden in `README.md`
  mit eindeutiger Zuständigkeit eingeordnet.

## Parent-Governance-Vertrag: 2

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.

### Entwicklungsphase und Kompatibilitätsbedarf

Entscheidung vom 5. September 2026: Das Gesamtprojekt befindet sich vollständig
in der Entwicklung. Es gibt kein PROD, keinen produktiven Datenbestand und
keinen bereits betriebenen Bestand mit zu erhaltendem Upgradepfad. STAGING
ist eine wegwerfbare Entwicklungs- und Integrationsumgebung und darf im
konkret beauftragten Reinstall vollständig neu aufgebaut werden. Wenige
externe Testnutzer ändern diese Einordnung nicht.

Vor einer Datenmigration, Legacy-Unterstützung, Compatibility Layer,
Deprecated API, Dual-Read/Dual-Write, einem Altschema-Fallback, Übergangsformat
oder der Unterstützung historischer Entwicklungsstände wird geprüft:

1. Wurde der betroffene Zustand jemals produktiv eingesetzt?
2. Benötigen reale Daten oder Nutzer seine Erhaltung?
3. Gibt es einen anderen konkreten technischen Erhaltungsgrund, insbesondere
   einen geltenden Plattform- oder externen API-Vertrag?

Sind alle relevanten Antworten nein, ist die saubere Breaking-Change-/
Reinstall-Lösung der Standard. Frühere rein interne Entwicklungsstände
begründen weder Abwärtskompatibilität noch eine Deprecationfrist.
Entwicklungsschemata dürfen durch ein kanonisches Installationsschema ersetzt,
alte interne APIs und Konfigurationsformate samt ausschließlich dafür
benötigten Adaptern und Tests entfernt werden. Architekturqualität und der
saubere Zielzustand haben Vorrang. Nextclouds nötige Installationsmigrationen
bleiben erhalten; ein Verzeichnisname `Migration` beweist keine Altlast.

Breaking Changes werden im selben Änderungskontext vollständig durchgezogen:
betroffene Provider, Consumer, standardisierte APIs, Vertragsversionen,
Metadaten, Tests und Dokumentation müssen zusammenpassen. Unterstützte
Nextcloud-/openDesk-Plattformverträge, externe Standards, Autorisierung und
Datenschutz gelten unverändert. Fehlende oder inkompatible optionale Provider
bleiben kontrolliert sichtbar. Ein Reinstall erlaubt keine privaten
Fremdtabellenzugriffe oder parallel erfundenen Plattformmechanismen.

Vor destruktiver Arbeit werden die tatsächlich benötigten externen
Testidentitäten, Gruppen, Rollen und nicht reproduzierbaren Testdaten gezielt
gesichert oder über bestehende native Setup-Strukturen reproduzierbar gemacht.
Echte Personen- und Zugangsdaten bleiben außerhalb von Git. Diese begrenzte
Sicherung begründet keine allgemeine Legacy-Unterstützung. Ein Reinstall
bleibt ein normaler unterstützter Entwicklungsweg; der vorhandene
Compatibility-Workflow besitzt den Fresh-Install-Nachweis, dessen aktueller
Belegstatus in `docs/workspace.md` beschrieben ist.

Diese Phase endet ausschließlich durch einen ausdrücklich dokumentierten,
von Simon freigegebenen **Production-Readiness-/Production-Freeze-Entscheid**.
Ein Release Candidate, eine Versionsnummer, ein Staging-Deployment oder ein
externer Testzugang lösen den Wechsel nicht aus. Der Entscheid wird in dieser
kanonischen Lifecycle-Quelle mit Datum, Geltungsbereich und betroffenem
Versions-/Datenstand festgehalten und in die lokale Steuerung projiziert.
Dann werden Upgradepfade, Datenbankmigrationen, Persistenz, Backup/Restore,
Rollback, Release-/API-Kompatibilitätszusagen, Deployment-/Freigabeprozess und
PROD→STAGING/COPY-Strategie neu bewertet. Eine vollständige PROD-Governance
wird jetzt nicht vorweggenommen.

Diese Regel entscheidet den Kompatibilitätsbedarf, erweitert aber keinen
Repository-Schreibauftrag und ersetzt keine Freigabe für eine konkrete
destruktive Aktion. Lokale Regelprojektionen folgen dem bestehenden
`docs/parent-governance-contract.md`; ein unsynchronisierter Einzel-Checkout
darf keinen abweichenden Phasenstand stillschweigend annehmen.

### Prüfaufwand

- Vor einem neuen Test, Scan, Linter, Architektur- oder Systemcheck wird
  geprüft, welcher bestehende Check dieselbe Eigenschaft bereits nachweist.
  Diesen erweitern oder sein nachweislich passendes Ergebnis wiederverwenden;
  ein zusätzlicher Check braucht eine benannte zusätzliche Fehlerklasse oder
  Vertrauensgrenze. Gleicher Input, gleiche Prüfung, gleiche Fehlerklasse und
  gleiche Phase begründen keinen zweiten Lauf. Gestaffelte Unit-, Contract-
  und Runtime-Nachweise bleiben erhalten. Die dokumentierten lokalen
  Prüfeinstiege bestimmen Umfang und Ergebnisgültigkeit.
