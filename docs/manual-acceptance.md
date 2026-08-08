# Manuelles Abnahmeformular – AD Kalender

Dieses Formular dokumentiert die fachliche und visuelle Abnahme auf einem
realitätsnahen Staging-System. Pro Prüffall wird genau ein Ergebnis markiert
und unter „Warum/Beleg/Abweichung“ knapp festgehalten, was beobachtet wurde.
Keine Passwörter, Tokens, personenbezogenen Echtdaten oder internen
Kalenderkennungen eintragen. Für Screenshots ausschließlich neutrale
Testkonten und synthetische Termine verwenden.

## Kopfdaten

| Feld | Eintrag |
|---|---|
| Datum und Uhrzeit | 02.08.2026, ca. 14:52–18:16 Uhr |
| Prüfer*in | Simon |
| Umgebung und URL | DEV/Staging – `https://nextcloud-dev.ddev.site/index.php/apps/adcalendar/` |
| AD-Kalender-Version | 0.13.0-rc.5 |
| Nextcloud-Version | Nextcloud Hub 26 Spring (34.0.2) |
| Browser und Version | Google Chrome 150.0.7871.186, offizieller 64-Bit-Build unter Ubuntu |
| Fenstergröße / Zoom | ca. zwei Drittel der Bildschirmbreite von 1920 px; Zoom 100 % |
| Verwendete neutrale Testkonten und Rollen | `admin`; `adc-demo-bl-now`; `adc-demo-eb-sued`; weitere erforderliche Gruppen-/Hierarchiekonten fehlten teilweise |

Ergebniskennzeichnung: `[ ] erfolgreich` / `[ ] nicht erfolgreich` /
`[ ] nicht geprüft`. Bei „nicht erfolgreich“ oder „nicht geprüft“ ist eine
Begründung verpflichtend.

## A. Navigation, Wochen- und Monatsansicht

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Wochenansicht als Ausgangszustand | App öffnen, „Woche“ wählen und zwischen beiden Ausrichtungen wechseln. | Eine Wochenmatrix erscheint; Personen-/Tagesachsen, Einträge und Aktionen bleiben bedienbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Wochenansicht in beiden Ausrichtungen geprüft; Matrix, Achsen, Einträge und Aktionen bedienbar; keine Abweichung. |
| A2 | Durchgehende Monatsmatrix, Tage als Spalten | „Monat“ und anschließend „Tage als Zeilen“ wählen, sodass Personen die Zeilen bilden. Vorherigen und nächsten Monat aufrufen. | Der gesamte Zeitraum erscheint als eine Matrix ohne KW-Blöcke oder wiederholte Tabellenköpfe. Randtage sind gedimmt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Durchgehende Monatsmatrix ohne KW-Blöcke oder wiederholte Tabellenköpfe; Randtage gedimmt. Änderungswunsch: vor und nach dem eigentlichen Monat höchstens drei Randtage anzeigen; im August 2026 wurden fünf Tage aus Juli und sechs aus September gezeigt. |
| A3 | Durchgehende Monatsmatrix, Tage als Zeilen | Auf „Personen als Zeilen“ umschalten, sodass Tage die Zeilen bilden. Vorherigen und nächsten Monat aufrufen. | Der gesamte Zeitraum erscheint als eine Matrix ohne KW-Blöcke oder wiederholte Tabellenköpfe. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Durchgehende Monatsmatrix mit Tagen als Zeilen; keine KW-Blöcke oder wiederholten Tabellenköpfe; keine Abweichung. |
| A4 | Erreichbare horizontale Scrollleiste | Einen Monat und so viele Personen anzeigen, dass die Matrix breiter und höher als das Fenster ist. Ohne zum Inhaltsende zu scrollen die horizontale Leiste am unteren Rand des sichtbaren Kalenderbereichs suchen und bis ganz rechts sowie zurück bewegen. Danach die Ausrichtung wechseln und wiederholen. | Die horizontale Leiste bleibt unabhängig von der Inhaltshöhe am unteren Rand des sichtbaren Kalender-Viewports erreichbar. Die vertikale Leiste bleibt ebenfalls bedienbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Horizontale Scrollleiste in beiden Ausrichtungen dauerhaft erreichbar; vertikale Scrollleiste blieb bedienbar. |
| A5 | Scrollen bei geöffnetem Filter und kleinem Viewport | Filter öffnen, Browserfenster verkleinern und A4 wiederholen. Filter schließen und erneut prüfen. | Navigation und Scrollleisten bleiben sichtbar und erreichbar; es entsteht kein zweiter horizontaler Seiten-Scrollbar. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Bei kleinem Viewport bleiben in „Personen als Zeilen“ die Icons für neuen Dienst und neuen Termin beim vertikalen Scrollen in der Kopfzeile und überdecken das Datum. Beim horizontalen Scrollen werden Gruppenbezeichnungen nach links aus dem sichtbaren Bereich geschoben. Zweiter horizontaler Seiten-Scrollbar nicht gesondert dokumentiert. |
| A6 | Fixierte Personenachse | Eine große Matrix horizontal und vertikal scrollen. | Die zu Personen gehörende erste Spalte beziehungsweise Kopfzeile bleibt sichtbar; Inhalte überdecken sie nicht. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Personenachse in beiden Ausrichtungen dauerhaft sichtbar; keine Überdeckungen festgestellt. |
| A7 | Wochenendbezeichnung | Samstag und Sonntag in beiden Ausrichtungen ansehen. | Es stehen nur Wochentag und Datum dort; das zusätzliche Wort „Wochenende“ erscheint nicht. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Das Wort „Wochenende“ erscheint nicht. Farbliche Markierung genügt; Tagesnamen dürfen aus Platzgründen als Kurzform erscheinen. |
| A8 | Kompakte leere Sondertage | Einen leeren Samstag, Sonntag und gesetzlichen Feiertag in beiden Ausrichtungen anzeigen. | Leere Sondertage sind deutlich platzsparender; bei Tagen als Spalten besitzen sie eine feste schmale Breite. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Leere Samstage und Sonntage sind kompakt; leere Feiertage werden vermutlich durch die Feiertagsbezeichnung verbreitert. Feiertagsname platzsparend über Tooltip oder Overlay darstellen. Feiertagsmarkierung zugleich deutlicher hervorheben, ohne die Zelle zu verbreitern. |
| A9 | Urlaubsmarker und Kompaktklasse | Auf einem sonst leeren Wochenend- oder Feiertag nur einen geplanten beziehungsweise genehmigten Urlaubsmarker anzeigen. | Der Urlaubsmarker allein vergrößert den Tag nicht. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Ein alleinstehender Urlaubsmarker hält den Sondertag nicht kompakt. Gewünscht ist ein schlichtes farblich markiertes `U`; Genehmigungsdetails werden im Urlaubsplaner bearbeitet. |
| A10 | Automatische Normalgröße bei Einträgen | Auf einem kompakten Sondertag für eine sichtbare Person einen Dienst oder Termin anlegen und danach löschen. | Mit Eintrag verwendet der gesamte Tag Normalgröße; nach dem Löschen wird er wieder kompakt, sofern kein anderer Dienst oder Termin vorliegt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Sondertag wechselt mit Dienst oder Termin auf Normalgröße und nach Löschung zurück auf kompakt. Zusätzlicher Fehler: Nach Bearbeitung springt die Tabelle auf Scrollposition 0,0; Scrollposition und bearbeitetes Feld sollen erhalten bleiben. |
| A11 | Tastatur und Fokus | Tabs, Zeitraum, Navigation, Ausrichtung, Filter, Schnellaktionen und Dialoge nur mit Tab, Umschalt+Tab, Eingabe und Escape bedienen. | Alle Funktionen sind erreichbar; Fokus ist sichtbar; kein Dialog erzeugt eine Tastaturfalle. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Tastaturnavigation grundsätzlich möglich und Fokusreihenfolge sinnvoll. Fokus ist jedoch nicht jederzeit sichtbar, insbesondere bei Checkboxen im Filter. Mindestens eine Tastaturfalle vorhanden; genauer Ort und Fokusziel nach Dialogschluss nicht dokumentiert. |

## B. Personen-, Rollen- und Bereichsfilter

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Personenfilter | Nach einem neutralen Testkonto suchen, auswählen und die Auswahl wieder zurücksetzen. | Nur die ausgewählte Person erscheint; Status und Rücksetzen sind verständlich und vollständig. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Personenfilter, verständliche Auswahl und vollständiges Zurücksetzen funktionierten; konkretes Testkonto nicht dokumentiert. |
| B2 | ODER innerhalb der Rollen | Zwei Rollen auswählen, die jeweils mindestens ein unterschiedliches Testkonto enthalten. | Personen mit Rolle A oder Rolle B erscheinen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | ODER-Verknüpfung innerhalb der Rollen funktionierte; konkrete Rollen nicht dokumentiert. |
| B3 | ODER innerhalb der Bereiche | Zwei Bürobereiche auswählen. | Personen aus Bereich A oder Bereich B erscheinen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | ODER-Verknüpfung innerhalb der Bereiche funktionierte; konkrete Bereiche und Prüfung auf Duplikate nicht separat dokumentiert. |
| B4 | Schnittmenge Rolle und Bereich | Eine bereichsgebundene Rolle und genau einen Bereich auswählen. | Nur Personen erscheinen, die beide Kriterien erfüllen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Rolle und Bereich wurden als Schnittmenge gefiltert; konkrete Auswahl nicht dokumentiert. |
| B5 | Vorrangige Rolle | Ein Testkonto mit Leitungsrolle und zusätzlicher unterstellter Rolle über beide Rollenfilter suchen. | Die Person wird nur über ihre nach Organisationsreihenfolge vorrangige Rolle gefunden. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Vorrangige Rolle wurde korrekt verwendet und die Person bei gemeinsamer Auswahl nur einmal angezeigt; konkrete Rollen nicht dokumentiert. |
| B6 | Bereichsübergreifende Büroleitung | Eine Büroleitung mit Nordost und West nacheinander über beide Bereichsfilter sowie gemeinsam anzeigen. | Sie wird über beide Bereiche gefunden, erscheint in der Matrix aber nur einmal. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Bereichsübergreifende Büroleitung wurde über Nordost und West gefunden und gemeinsam nur einmal angezeigt; Testkonto nicht dokumentiert. |
| B7 | Leitungs-/Stabsblock | Den gemeinsamen Anzeigen-/Ausblenden-Schalter für GF, PDL, Sekretariat, HR, QMB und Assistenz GF Digi betätigen. | Der Block wird vollständig ein- beziehungsweise ausgeblendet und gemäß Organisationshierarchie sortiert. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Der gemeinsame Schalter umfasst zusätzlich IT, Sekretariat sowie FiBu/LoBu. Die Gruppen müssen weiter aufgetrennt werden. Das bisherige Soll im Formular ist entsprechend zu präzisieren. |
| B8 | Persönlicher Standard | Filter, Zeitraum und Ausrichtung wählen, „Zum Standard machen“ drücken, App neu laden und anschließend eine andere Konfiguration ohne Speichern testen. | Nur bewusst gespeicherte Werte werden nach dem Neuladen wiederhergestellt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Bewusst gespeicherter Zeitraum, Ausrichtung und Filter wurden nach Neuladen wiederhergestellt; nicht gespeicherte Änderungen wurden verworfen. |

## C. Reihenfolge, Hierarchie und Bearbeitungsrechte

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| C1 | Ergänzte globale Gruppen | Stv. PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang gemeinsam anzeigen. | Stv. PDL steht vor Büroorganisation Pflege und PFK; Fahrzeugverwaltung folgt auf IT; Empfang folgt auf Sekretariat. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Passende Testkonten für Stv. PDL, Büroorganisation Pflege, Fahrzeugverwaltung und Empfang fehlen. Künftig muss jede fachliche Gruppe durch mindestens ein Demoaccount vertreten sein. |
| C2 | Hierarchie der globalen Gruppen | Mit passenden neutralen Leitungskonten Einträge der jeweils unterstellten Testkonten anlegen oder ändern. Gegenrichtung ebenfalls versuchen. | PDL/Stv. PDL, GF Digi/Fahrzeugverwaltung und Sekretariat/Empfang folgen der festgelegten Hierarchie; Untergebene dürfen Vorgesetzte nicht bearbeiten. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Passende Demoaccounts für die globalen Hierarchien fehlen. Stv. PDL, BuS, Büroorganisation Pflege und Empfang sollen gesammelt nachgerüstet werden; zusätzlich werden Leitung-/Unterstellungs- und Mehrfachrollenkonstellationen benötigt. |
| C3 | Bereichsgebundene Führung | Mit BL und StvBL BO/EB im eigenen und in einem fremden Bereich bearbeiten. PFK ebenfalls versuchen. | BO/EB im passenden Bereich sind bearbeitbar; fremder Bereich und PFK sind nicht bearbeitbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `adc-demo-bl-now` war BO/EB im eigenen Bereich bearbeitbar; BO/EB im fremden Bereich und PFK waren nicht bearbeitbar. |
| C4 | Schutz bei Mehrfachrollen | Mit einem normalen EB-Konto versuchen, eine StvBL zu bearbeiten, die zusätzlich EB ist. | Die Leitungsrolle schützt die Person; Bearbeitung wird serverseitig abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Mit `adc-demo-eb-sued` wurde für `adc-demo-bl-now` keine Bearbeitung in der Oberfläche angeboten. Ein direkter serverseitiger Manipulations-/API-Versuch wurde nicht durchgeführt; serverseitige Abweisung daher nicht belegt. |
| C5 | Eigene Einträge | Mit einem normalen Testkonto eigenen Dienst und eigenen Termin anlegen, ändern und löschen. | Eigene Einträge sind vollständig bearbeitbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `adc-demo-eb-sued` konnten eigener Dienst und eigener Termin angelegt, geändert und gelöscht werden. Zusätzlicher UI-Mangel: Validierungsfehler bei identischem Terminbeginn und -ende ist zu blass und braucht deutlich mehr Kontrast. |

## D. Dienste, Termine, Meetings, Standards und Urlaub

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| D1 | Dienst mit zugeordnetem Termin | Dienst anlegen und darin einen Termin anlegen. | Der Termin erscheint innerhalb des Dienstes und bleibt diesem fachlich zugeordnet. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `adc-demo-eb-sued` wurde ein Dienst mit zugeordnetem Termin angelegt; Darstellung und Zuordnung blieben nach Neuladen erhalten. |
| D2 | Sperrtermin | Termin außerhalb eines Dienstes anlegen. | Er erscheint ausdrücklich als Sperrtermin und nicht nur mit einer anderen Farbe. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Gesamtprüfung erfolgreich; keine Abweichungen angegeben. Einzelangaben und Testkonto nicht separat dokumentiert. |
| D3 | Wiederholter Termin | Eine tägliche, wöchentliche und monatliche Testserie mit Enddatum anlegen; ein Vorkommen einzeln ändern und löschen. | Serie und Einzelabweichungen folgen den gewählten Regeln; ungültige Serien werden verständlich abgewiesen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `adc-demo-eb-sued` wurden tägliche, wöchentliche und monatliche Serien angelegt; Einzelvorkommen geändert und gelöscht. Ungültige Serie ließ sich über die Oberfläche nicht erzeugen. UI-Anforderung: redundante Auswahl „Typ“ aus Dienst- und Termindialog entfernen, da der öffnende Button den Typ festlegt. |
| D4 | Meeting-Lückensuche | Mindestens zwei Personen auswählen, eine Mindestdauer setzen, freie Woche suchen, weitersuchen und eine Person abwählen. | Nur gemeinsame freie Zeiten innerhalb vorhandener Dienste erscheinen; Termine und genehmigte Urlaube werden abgezogen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Meeting-Lückensuche insgesamt erfolgreich; Testkonten, Mindestdauer und Einzelprüfungen nicht separat dokumentiert. |
| D5 | Atomares gemeinsames Meeting | Einen gefundenen Slot mit berechtigtem Konto blockieren, gemeinsam ändern und löschen. Danach mit fehlendem Recht auf mindestens eine Person wiederholen. | Berechtigt entstehen/ändern/löschen sich alle Einträge gemeinsam; bei fehlendem Recht entsteht kein Teileintrag. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Atomare Meetingverarbeitung insgesamt erfolgreich. UI-Mangel: Termintitel wird wegen zu geringer Feldhöhe abgeschnitten. |
| D6 | Standard-Dienstzeiten | Für einen Wochentag Beginn/Ende speichern, Woche öffnen und einen Standard über Mitternacht testen. | Standards werden als normale Dienste materialisiert; Ende vor Beginn reicht in den Folgetag. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Standarddienste wurden materialisiert; Dienst über Mitternacht reicht korrekt in den Folgetag. Darstellungswunsch: je Kalendertag segmentieren, z. B. erster Tag `20–24`, Folgetag `0–8`, mit Fortsetzungskennzeichnung. |
| D7 | Abweichung und Lösch-Tombstone | Einen materialisierten Standarddienst ändern und einen anderen löschen; Woche neu laden. | Änderung bleibt einmalig erhalten; gelöschter Dienst erscheint für genau dieses Datum nicht erneut. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `admin` blieb eine einmalig geänderte Standardmaterialisierung erhalten; gelöschter Dienst erschien nicht erneut; Standard blieb an anderen Tagen wirksam. |
| D8 | Geplanter Urlaub | Mit aktiver AD-Urlaub-Integration einen geplanten Urlaub anzeigen und am selben Tag einen Eintrag anlegen. | `U?` erscheint read-only und blockiert nicht. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | `U?` sichtbar und nach Neuladen erhalten; Marker jedoch bearbeitbar. Dienste werden blockiert, Sperrtermine ebenfalls. Geändertes fachliches Soll: Urlaub blockiert Dienste, Sperrtermine bleiben möglich, Urlaubsmarker sind im AD Kalender read-only. |
| D9 | Genehmigter Urlaub | Einen genehmigten Urlaub anzeigen und Dienst, Termin, Standardmaterialisierung sowie Meetinglücke für diesen Tag prüfen. | `U` erscheint read-only und blockiert alle vier Wege. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Genehmigter Urlaub verhält sich wie geplanter Urlaub: Marker bearbeitbar; Dienste und Sperrtermine blockiert. Nach geändertem Soll sollen Dienste blockiert, Sperrtermine möglich und Marker read-only sein. Standardmaterialisierung und Meeting-Lückensuche nicht separat verifiziert. |

## E. Persönlicher Nextcloud-Dienstkalender

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| E1 | Erster einseitiger Abgleich | Für ein neutrales Konto mit vorhandenem Dienst die Synchronisation aktiviert lassen und den nächsten Abgleich abwarten. Nextcloud-Kalender öffnen. | Ein privater Kalender mit dem aktuell administrativ konfigurierten Namen entsteht und enthält den vorhandenen Dienst. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `admin` wurde der Testdienst „nextcloud-kalender“ beim Hintergrundabgleich in den privaten Kalender `AD Dienste` übertragen; Termine oder Urlaube waren in diesem konkreten Test nicht ungewollt enthalten. |
| E2 | Ausschließlich eigene Dienste | Eigenen und fremden Dienst sowie eigenen Termin und Urlaub vergleichen. | Im persönlichen Zielkalender erscheinen ausschließlich die Dienste des Kontos. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Eigener Dienst und eigener Termin wurden synchronisiert, fremder Dienst nicht; eigener Urlaub fehlte. Geändertes Soll: Persönlicher Nextcloud-Kalender enthält eigene Dienste, Termine und Urlaube, aber keine fremden Einträge. |
| E3 | Idempotente Aktualisierung | Einen Dienst ändern, den Abgleich abwarten und denselben Abgleich ohne weitere Änderung erneut abwarten. | Das vorhandene Kalenderobjekt wird aktualisiert; es entsteht kein Duplikat. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `admin` wurden Dienst und Termin aktualisiert; wiederholte Abgleiche erzeugten keine Duplikate. Urlaubsaktualisierung nicht separat geprüft. |
| E4 | Löschung | Einen synchronisierten Dienst im AD Kalender löschen und den Abgleich abwarten. | Nur das zugehörige app-eigene Kalenderobjekt wird entfernt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit `admin` wurde das zugehörige app-eigene Objekt nach Dienstlöschung entfernt; andere app-eigene und manuell angelegte fremde Objekte blieben unverändert. |
| E5 | Opt-out und fremdes Objekt | Im Zielkalender ein neutrales fremdes Objekt anlegen, Synchronisation deaktivieren und den Abgleich abwarten. | App-eigene Dienstobjekte verschwinden; das fremde Objekt bleibt. Der Kalender wird nur gelöscht, wenn er anschließend leer ist. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Nach Opt-out und nachweislich forciertem Hintergrundjob wurden app-eigene Dienste, Termine und Urlaube nicht entfernt. Fremdes Objekt und nicht leerer Kalender blieben korrekt erhalten. Löschung eines anschließend leeren Kalenders nicht geprüft. |
| E6 | Erneute Aktivierung | Synchronisation wieder aktivieren und den Abgleich abwarten. | Der vollständige aktuelle Dienstbestand wird wiederhergestellt. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wiederherstellung nicht prüfbar, da die app-eigenen Einträge in E5 nie entfernt wurden. Nach Behebung von E5 erneut prüfen. |
| E7 | Aggregierter Hintergrundstatus | Einen erfolgreichen Abgleich und anschließend mit einem neutralen absichtlich nicht erreichbaren Testprovider einen Teilfehler auslösen. Als Admin den AD-Kalender-Abschnitt öffnen; als Nichtadmin denselben Bereich versuchen. | Der Admin sieht Zeitpunkt und ausschließlich aggregierte Anzahlen sowie Erfolg/Warnung. Nichtadmin erhält keinen Adminbereich. Konten, Kalender, Provider, URLs, Kennungen, Zugangsdaten und Fehlerdetails erscheinen nicht. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Auf Wunsch übersprungen. |

## F. Kopano und manuelles CalDAV

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| F1 | Kopano-Verbindung | Unter „Einstellungen“ Kopano öffnen, vorbelegte Adresse prüfen, gültiges neutrales Testkonto verbinden und Passwort nicht dokumentieren. | Verbindung wird bestätigt; das Passwort ist danach nicht mehr im Formular sichtbar. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Kopano-CalDAV ist administrativ nicht freigegeben; Verbindungstest nicht möglich. |
| F2 | Manueller CalDAV-Anbieter | Einen freigegebenen HTTPS-CalDAV-Endpunkt mit neutralem Testkonto verbinden. | Verbindung wird bestätigt; unsichere oder ungültige Adressen werden verständlich abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität übersprungen. |
| F3 | Externer Exportvertrag | Nach F1/F2 Dienst anlegen, ändern und löschen; beim Anbieter jeweils den Kalender mit dem aktuell administrativ konfigurierten Namen prüfen. Zusätzlich einen Termin und Urlaub anlegen. | Dienst wird ohne Duplikate nachgeführt und gelöscht; Termin und Urlaub werden nicht exportiert. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Keine verbundene Kopano- oder manuelle CalDAV-Testverbindung vorhanden. |
| F4 | Kein Rückimport | Im externen Zielkalender ein neutrales Objekt anlegen und AD Kalender neu laden. | Das externe Objekt erscheint nicht im AD Kalender. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Keine verbundene externe Kopano- oder CalDAV-Testverbindung vorhanden. |
| F5 | Parallele Verbindungen und Fehlerisolation | Kopano und manuelles CalDAV parallel verbinden; einen Anbieter vorübergehend mit ungültigen Testdaten stören und einen Dienst ändern. | Der andere Anbieter und die führenden AD-Daten bleiben funktionsfähig. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Keine parallelen Kopano- und manuellen CalDAV-Testverbindungen vorhanden. |
| F6 | Trennen | Beide Provider einzeln trennen. | Status wird je Provider aktualisiert; die jeweils andere Verbindung bleibt unberührt. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Keine verbundenen Kopano- oder manuellen CalDAV-Testprovider vorhanden. |
| F7 | Administrativer read-only Kopano-Test | Als Nextcloud-Admin Adresse und temporäre Zugangsdaten im AD-Kalender-Adminabschnitt prüfen; danach als Nichtadmin denselben API-/UI-Weg versuchen. | Admin erhält das Ergebnis einer reinen Leseprüfung; Passwort wird geleert und nicht gespeichert. Nichtadmin-Zugriff wird abgewiesen. Es wird kein Kalender angelegt. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Admin-Test ausgelöst; anschließend sprang die Seite ohne Erfolgs- oder Fehlermeldung an den Anfang und setzte alle Felder zurück. Reine Leseprüfung, Passwortspeicherung, Kalenderanlage und Nichtadmin-Schutz konnten nicht verifiziert werden. |
| F8 | Diagnose eines nicht freigegebenen Endpunkts | Einen dafür vorgesehenen Testendpunkt verwenden, der CalDAV mit HTTP 405 ablehnt. | Die Meldung erklärt den nicht freigegebenen CalDAV-Zugriff und behauptet nicht, AD Kalender könne ihn selbst freischalten. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Durch F7 blockiert: Da der administrative Test keine auswertbare Meldung liefert, kann die spezifische HTTP-405-Diagnose nicht geprüft werden. |

## G. Administrative Kalenderdefaults und Bestandsumbenennung

Für diese Fälle zuerst einen neutralen Ausgangsnamen und eine ausschließlich
für die Abnahme vorgesehene gültige HTTPS-CalDAV-Adresse verwenden. Nach der
Prüfung die gewünschten Betriebswerte wiederherstellen. Keine internen
Kalender- oder Providerkennungen in diesem Formular notieren.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| G1 | Bestandsdefaults ohne gesetzte Konfiguration | Vor der ersten bewussten Speicherung den AD-Kalender-Adminabschnitt und einen neuen persönlichen Kopano-Dialog öffnen. | `https://mail.adberlin.org/` und `AD Dienste` erscheinen als unveränderte Bestandsdefaults. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Mit Testkonto `admin`: Admin-Adresse `https://mail.adberlin.org/`, Kalendername `AD Dienste`, Kopano-Vorbelegung `https://mail.adberlin.org/`; zuvor keine Defaults gespeichert; keine Abweichung. |
| G2 | Gültiges administratives Speichern | Als Nextcloud-Admin eine gültige HTTPS-CalDAV-Vorgabe und einen neutralen neuen Kalendernamen speichern, Seite neu laden und einen noch nicht verbundenen persönlichen Kopano-Dialog öffnen. | Beide normalisierten Werte bleiben nach dem Neuladen erhalten; der neue Dialog verwendet die neue URL-Vorgabe und neue Kalender erhalten den neuen Namen. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Test-URL `https://mail.adberlin.org/` und Name `AD Dienste Abnahmetest` eingegeben. Keine Erfolgs- oder Fehlermeldung; Werte wirkten nicht gespeichert. Verlässliche Prüfung nach Neuladen nicht möglich. |
| G3 | Validierung ohne Teilkonfiguration | Nacheinander HTTP, direkte IP, URL mit Zugangsdaten, leeren Namen, Namen über 255 Zeichen und Namen mit Steuerzeichen absenden; danach die Adminseite neu laden. | Jeder ungültige Versuch wird abgewiesen und beide zuvor gültigen Werte bleiben gemeinsam unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Durch G2 blockiert: Ohne funktionierendes administratives Speichern lässt sich nicht unterscheiden, ob ungültige Werte korrekt abgewiesen werden oder der Speicherweg generell ausfällt. |
| G4 | Admin-, Anonym- und CSRF-Schutz | Als Nichtadmin den Adminabschnitt und den Speichervorgang versuchen. Den API-Aufruf zusätzlich ohne gültiges Requesttoken sowie ohne Sitzung wiederholen, ohne Zugangsdaten im Werkzeug zu protokollieren. | Nur der bestätigte Admin mit gültigem CSRF-Token darf speichern; alle anderen Versuche ändern keinen AppConfig-Wert. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Positivfall bereits nicht erfüllt: Selbst der angemeldete Administrator konnte mit regulärem Formular keine Werte nachweisbar speichern und erhielt keine Rückmeldung. Nichtadmin-, anonymer und tokenloser Zugriff wurden nicht separat geprüft. |
| G5 | Gespeicherte persönliche Serveradresse bleibt stabil | Vor G2 ein neutrales Kopano-/CalDAV-Testkonto mit einer von der Vorgabe abweichenden HTTPS-Adresse verbinden. G2 durchführen, Dienst abgleichen und persönlichen Status erneut öffnen. | Die bereits gespeicherte persönliche Server- und Kalenderadresse bleibt unverändert; nur der sichtbare app-eigene Kalendername wird fällig. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Vor G2 bestand keine persönliche Kopano-/CalDAV-Testverbindung mit abweichender Serveradresse; notwendiger Ausgangszustand fehlte. |
| G6 | Interne Bestandsumbenennung | Für ein neutrales Konto mit bestehendem internem Dienstkalender einen Dienst synchronisieren, den Kalendernamen administrativ ändern und den nächsten ausgehenden Abgleich abwarten. Den Dienst danach ändern und erneut abgleichen. | Derselbe Kalender trägt den neuen sichtbaren Namen; Dienst und Aktualisierung bleiben ohne Duplikat erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Durch G2 blockiert: Neuer administrativer Kalendername konnte nicht gespeichert werden. |
| G7 | Externe CalDAV-Bestandsumbenennung | Mit einer bestehenden neutralen CalDAV-Verbindung und synchronisiertem Dienst den Namen administrativ ändern und den nächsten Abgleich abwarten. Danach denselben Abgleich wiederholen. | Derselbe externe Kalender trägt den neuen Namen; URL und Dienstobjekte bleiben stabil und die Wiederholung erzeugt weder weiteren Kalender noch Duplikat. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Keine bestehende externe CalDAV-Testverbindung mit synchronisiertem Dienst vorhanden. |
| G8 | Fremdkalenderschutz | Den sichtbaren Namen eines verbundenen Testkalenders direkt beim Anbieter auf einen dritten, AD Kalender unbekannten Namen ändern und anschließend einen neuen administrativen Namen sowie einen Abgleich auslösen. | AD Kalender benennt den nicht mehr sicher als app-eigen belegten Kalender nicht um und meldet den Providerfehler, ohne führende AD-Daten zu verändern. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Kein externer, sicher als app-eigen identifizierter Testkalender vorhanden. |
| G9 | Providerfehler, Isolation und Wiederholung | Zwei neutrale Provider parallel verbinden. Einen davon während einer fälligen Umbenennung unerreichbar machen, abgleichen, anschließend wieder erreichbar machen und erneut abgleichen. | Der erreichbare Provider wird umbenannt und weiter synchronisiert; der Fehler des anderen blockiert ihn nicht. Nach Wiederherstellung wird die ausstehende Umbenennung erfolgreich nachgeholt. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Keine zwei parallel verbundenen neutralen Provider vorhanden. |
| G10 | Kontextgerechte Ausgabe des Namens | Einen gültigen Testnamen mit `&`, `<`, `>`, Anführungszeichen und Umlauten speichern und in Adminseite, persönlicher Einstellung sowie an einem Test-CalDAV-Ziel prüfen. | Der Name erscheint als Text und als korrekter DAV-Anzeigename; kein Zeichen wird als HTML oder XML ausgeführt und es entsteht kein Markupfehler. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Durch G2 blockiert: Sonderzeichen-Testname konnte nicht gespeichert werden. |

## H. Aufgeschobene Providerabnahme

Google und Apple werden Mitte bis Ende August 2026 mit demselben Grundvertrag
wie in F3 bis F6 geprüft. Bis dahin werden die folgenden Zeilen als „nicht
geprüft – planmäßig verschoben“ markiert.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| H1 | Google OAuth und Export | OAuth-Konfiguration prüfen, neutrales Testkonto verbinden und Dienstexport/-aktualisierung/-löschung sowie fehlenden Rückimport prüfen. | Autorisierung verwendet den angezeigten Redirect und begrenzten Scope; ausschließlich Dienste werden idempotent exportiert. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität übersprungen; ursprünglich planmäßig auf spätere Providerabnahme verschoben. |
| H2 | Apple CalDAV und Export | Mit app-spezifischem Testpasswort verbinden und F3 bis F6 wiederholen. | Ausschließlich Dienste werden idempotent exportiert; keine Zugangsdaten erscheinen in Anzeige oder Formular. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität übersprungen; ursprünglich planmäßig auf spätere Providerabnahme verschoben. |

## I. Lokalisierung

Diese Prüfungen werden bis zur ausdrücklich gestarteten manuellen
L10N-Abnahme als „nicht geprüft – planmäßig verschoben“ markiert. Für den
Sprachwechsel nur neutrale Testdaten verwenden; technische IDs und
konfigurierte Eigennamen dürfen sich nicht ändern.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| I1 | Deutsche und englische Oberfläche | Nextcloud-Sprache nacheinander auf Deutsch und English (United Kingdom) stellen. Kalender, persönliche Einstellungen, Dialoge und Adminbereich jeweils neu laden. | Alle sichtbaren Bedienelemente, Hilfen, Status- und Fehlermeldungen folgen der aktiven Sprache; es erscheinen keine gemischten deutschen/englischen Rohtexte oder internen Schlüssel. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Deutsche und englische Kalenderoberfläche, Einstellungen und Dialoge vollständig; keine gemischten Sprachen, Rohtexte oder internen Schlüssel. Konfigurierte Gruppen-/Rollennamen bleiben bewusst unübersetzte Organisationsdaten. |
| I2 | Lange Beschriftungen und kleine Viewports | In englischer Sprache Kalendernavigation, Zeitraumumschalter, Filter, Dialoge und Adminbereich bei normaler Breite sowie höchstens 700 px prüfen. | Beschriftungen umbrechen ohne Überlagerung oder abgeschnittene Bedienaktionen; Fokus, Scrollleisten und Dialogaktionen bleiben erreichbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Englische Oberfläche bei normaler Breite und bis höchstens 700 px erfolgreich geprüft; keine Abweichungen angegeben. |
| I3 | Plural und dynamische Werte | Suchergebnisse, Filterstatus und Serienaktionen jeweils mit einem und mehreren Treffern/Vorkommen auslösen. Einen neutralen Kalendernamen mit Umlauten und HTML-Sonderzeichen verwenden. | Singular/Plural und eingesetzte Zahlen/Namen sind grammatisch und vollständig; keine Platzhalter bleiben sichtbar und Sonderzeichen werden nicht als Markup ausgeführt. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität der Mehrsprachigkeit übersprungen. |
| I4 | Lokalisierte Fehler bei stabilen Codes | In beiden Sprachen je eine ungültige Eintrags-, Provider- und Adminaktion auslösen und die JSON-Antwort nur ohne Geheimnisse prüfen. | Die Meldung wechselt die Sprache; maschinenlesbarer Fehlercode und HTTP-Status bleiben identisch. Keine URL, Kennung oder Zugangsdaten erscheinen in der Meldung. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität der Mehrsprachigkeit übersprungen. |
| I5 | Veröffentlichte Dienstereignisse | Einen betitelten und einen unbetitelten synthetischen Dienst in den privaten Nextcloud-Kalender sowie – sobald H1 freigegeben ist – nach Google synchronisieren. | Eigene Titel bleiben unverändert. Fallbacktitel und Beschreibung folgen der aktiven Locale; technische Event-ID, Eigentumsmarker und konfigurierter Kalendername bleiben stabil. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität der Mehrsprachigkeit übersprungen; Google-Provider zudem nicht eingerichtet. |
| I6 | Sprachwechsel ohne fachliche Zustandsänderung | Zeitraum, Ausrichtung, Filter, Serienstatus und Providerverbindungen merken, Sprache wechseln und dieselben Objekte erneut öffnen. | Nur die Darstellung ändert sich. Rechte, Auswahl, technische Statuswerte, Termine, Serien und Verbindungen bleiben unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Wegen geringer Priorität der Mehrsprachigkeit übersprungen. |


## Zu bearbeitende Punkte

### Abnahmeblockierende Funktionsfehler

| Ref. | Bezug | Zu bearbeiten / Akzeptanzkriterium |
|---|---|---|
| P-01 | G2, G4, F7 | Administrativen Speicher- und Testweg reparieren: Speichern/Testen ohne Seitensprung, verständliche Erfolgs-/Fehlermeldung, sichere Passwortbehandlung; berechtigter Admin kann speichern. Anschließend G2–G4, G6, G10 sowie F7/F8 wiederholen. |
| P-02 | E5, E6 | Opt-out-Bereinigung implementieren: alle app-eigenen Dienste, Termine und Urlaube entfernen, fremde Objekte erhalten, leeren app-eigenen Kalender nach Soll löschen; Reaktivierung stellt aktuellen Bestand idempotent wieder her. |
| P-03 | D8, D9 | Urlaubslogik und Read-only-Verhalten nach präzisiertem Soll umsetzen: Urlaub blockiert Dienste, Sperrtermine bleiben möglich, Marker sind ausschließlich im Urlaubsplaner bearbeitbar. Standardmaterialisierung und Meeting-Lückensuche anschließend vollständig nachprüfen. |
| P-04 | E2 | Persönlichen Nextcloud-Kalender auf das neue Soll anpassen: eigene Dienste, eigene Termine und eigene Urlaube synchronisieren; fremde Einträge anderer Personen ausschließen. |
| P-05 | A11 | Tastaturzugänglichkeit herstellen: sichtbarer Fokus für alle Elemente, insbesondere Filter-Checkboxen; Tastaturfalle beseitigen; Fokus nach Dialogschluss sinnvoll zurückgeben. |

### Darstellung und Bedienbarkeit

| Ref. | Bezug | Zu bearbeiten / Akzeptanzkriterium |
|---|---|---|
| P-06 | A5 | Sticky-/Scroll-Verhalten korrigieren: Aktionsicons dürfen Datumsangaben nicht überdecken; Gruppenbezeichnungen dürfen beim horizontalen Scrollen nicht unkontrolliert verschwinden. |
| P-07 | A8 | Leere Feiertage kompakt halten; Feiertagsnamen aus der Zellenbreite lösen, etwa per Tooltip/Overlay; Feiertagsmarkierung zugleich deutlicher gestalten. |
| P-08 | A9 | Alleinstehender Urlaubsmarker darf Sondertage nicht vergrößern; kompakte farbliche `U`-Kennzeichnung verwenden. |
| P-09 | A10 | Scrollposition nach Bearbeitung erhalten und bearbeitetes Feld sichtbar beziehungsweise fokussiert halten. |
| P-10 | B7 | Anzeigen-/Ausblenden-Schalter fachlich auftrennen; IT, Sekretariat und FiBu/LoBu nicht ungewollt gemeinsam mit dem Leitungs-/Stabsblock schalten. Sollbeschreibung danach aktualisieren. |
| P-11 | C5 | Validierungsfehlermeldung bei identischem Terminbeginn und -ende deutlich kontrastreicher darstellen. |
| P-12 | D3 | Redundante Auswahl „Typ“ aus Dienst- und Termindialog entfernen, sofern der öffnende Button den Typ eindeutig festlegt. |
| P-13 | D5 | Höhe des Termineintrags so anpassen, dass der Titel nicht abgeschnitten wird. |
| P-14 | D6 | Dienste über Mitternacht je Kalendertag mit Teilzeitraum darstellen, z. B. `20–24` und `0–8`, mit erkennbarer Fortsetzung. |
| P-15 | A2 | Produktentscheidung treffen: Monatsansicht auf höchstens drei Randtage je Monatsgrenze begrenzen; Konflikt mit vollständigen Montag-bis-Sonntag-Wochen klären. |

### Testinfrastruktur und nachzuholende Abnahme

| Ref. | Bezug | Zu bearbeiten / Akzeptanzkriterium |
|---|---|---|
| P-16 | C1, C2 | Demo-Seeding vervollständigen: jede fachliche Gruppe mindestens einmal vertreten; zusätzlich geeignete Leitung-/Unterstellungs-, Bereichs- und Mehrfachrollenkonstellationen. Insbesondere Stv. PDL, BuS, Büroorganisation Pflege und Empfang nachrüsten. |
| P-17 | C4 | Serverseitige Rechteprüfung durch direkten API-/Manipulationsversuch nachprüfen; reine UI-Ausblendung genügt nicht. |
| P-18 | F1–F6, G5, G7–G9 | Neutrale externe Kopano-/CalDAV-Testprovider bereitstellen oder diese Funktionen bewusst aus dem aktuellen Freigabeumfang nehmen. Danach Export, Rückimportschutz, Trennung, parallele Fehlerisolation und Bestandsumbenennung prüfen. |
| P-19 | E7 | Aggregierten Hintergrundstatus mit Erfolg und absichtlichem Teilfehler sowie Nichtadmin-Schutz nachprüfen. |
| P-20 | H1, H2, I3–I6 | Niedrig priorisierte Provider- und Lokalisierungsprüfungen in einem späteren Abnahmelauf nachholen oder ausdrücklich aus dem Releaseumfang ausnehmen. |

### Präzisierte fachliche Sollvorgaben

- Persönlicher Nextcloud-Kalender: eigene Dienste, Termine und Urlaube synchronisieren; keine fremden Einträge anderer Personen.
- Geplanter und genehmigter Urlaub: Dienste blockieren, Sperrtermine zulassen, Urlaubsmarker im AD Kalender read-only.
- Konfigurierte Gruppen-, Rollen- und Eigennamen werden beim Sprachwechsel nicht übersetzt; nur die umgebende Benutzeroberfläche wird lokalisiert.
- Für spätere Abnahmen muss jede fachliche Gruppe durch geeignete Demoaccounts abgedeckt sein.

## Abschlussentscheidung

| Feld | Eintrag |
|---|---|
| Anzahl erfolgreich | 29 |
| Anzahl nicht erfolgreich | 12 |
| Anzahl nicht geprüft | 25 |
| Kritische Abweichungen / Ticketreferenzen | P-01 bis P-05; weitere Punkte P-06 bis P-20 siehe Abschnitt „Zu bearbeitende Punkte“ |
| Erneute Prüfung erforderlich bis | Nach Behebung der abnahmeblockierenden Punkte; kein Termin festgelegt |
| Gesamtentscheidung | [ ] abgenommen [ ] mit Auflagen abgenommen [x] nicht abgenommen |
| Begründung der Gesamtentscheidung | Mehrere fachliche, administrative und barrierefreiheitsrelevante Fehler bestehen fort. Insbesondere funktionieren administratives Speichern/Testen, Opt-out-Bereinigung, die präzisierte Urlaubslogik, Urlaubssynchronisation und Tastaturfokus nicht vollständig. 25 weitere Fälle blieben wegen fehlender Testkonten, Provider, blockierender Vorfehler oder niedriger Priorität ungeprüft. |
| Name / Datum | Simon / 02.08.2026 |