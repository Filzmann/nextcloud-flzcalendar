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
| Datum und Uhrzeit | 3.11.226 11:12 |
| Prüfer*in | filzmann |
| Umgebung und URL | ddev lokal  |
| AD-Kalender-Version | 0.13.0-rc.5 |
| Nextcloud-Version | 34.0.2 |
| Browser und Version | Google Chrome Version 154.0.8037.57 (Offizieller Build) (x86_64) |
| Fenstergröße / Zoom | 100% bei 1920er breite |
| Verwendete neutrale Testkonten und Rollen | `admin`; `adc-demo-bl-now`; `adc-demo-eb-sued`; weitere erforderliche Gruppen-/Hierarchiekonten fehlten teilweise |

Ergebniskennzeichnung: `[ ] erfolgreich` / `[ ] nicht erfolgreich` /
`[ ] nicht geprüft`. Bei „nicht erfolgreich“ oder „nicht geprüft“ ist eine
Begründung verpflichtend. Nach einer Änderung des geprüften Verhaltens bleiben
Ergebnis und Beleg bis zur erneuten manuellen Prüfung leer; erfolgreiche
Ergebnisse zu unverändertem Verhalten bleiben bestehen.

## A. Navigation, Wochen- und Monatsansicht

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Wochenansicht als Ausgangszustand | App öffnen, „Woche“ wählen und zwischen beiden Ausrichtungen wechseln. | Eine Wochenmatrix erscheint; Personen-/Tagesachsen, Einträge und Aktionen bleiben bedienbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A2 | Durchgehende Monatsmatrix, Tage als Spalten | „Monat“ und anschließend „Tage als Zeilen“ wählen, sodass Personen die Zeilen bilden. Vorherigen und nächsten Monat aufrufen. | Der gesamte Zeitraum erscheint als eine Matrix ohne KW-Blöcke oder wiederholte Tabellenköpfe. Randtage sind gedimmt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A3 | Durchgehende Monatsmatrix, Tage als Zeilen | Auf „Personen als Zeilen“ umschalten, sodass Tage die Zeilen bilden. Vorherigen und nächsten Monat aufrufen. | Der gesamte Zeitraum erscheint als eine Matrix ohne KW-Blöcke oder wiederholte Tabellenköpfe. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | Erreichbare horizontale Scrollleiste | Einen Monat und so viele Personen anzeigen, dass die Matrix breiter und höher als das Fenster ist. Ohne zum Inhaltsende zu scrollen die horizontale Leiste am unteren Rand des sichtbaren Kalenderbereichs suchen und bis ganz rechts sowie zurück bewegen. Danach die Ausrichtung wechseln und wiederholen. | Die horizontale Leiste bleibt unabhängig von der Inhaltshöhe am unteren Rand des sichtbaren Kalender-Viewports erreichbar. Die vertikale Leiste bleibt ebenfalls bedienbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | Scrollen bei geöffnetem Filter und kleinem Viewport | Filter öffnen, Browserfenster verkleinern und A4 wiederholen. Filter schließen und erneut prüfen. | Navigation und Scrollleisten bleiben sichtbar und erreichbar; es entsteht kein zweiter horizontaler Seiten-Scrollbar. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | Der Filterbereich verdrängte den Kalenderinhalt statt ihn zu überlagern; siehe `ADC-STAGING-FOLLOWUP` in `ROADMAP.md`. |
| A6 | Fixierte Personenachse | Eine große Matrix horizontal und vertikal scrollen. | Die zu Personen gehörende erste Spalte beziehungsweise Kopfzeile bleibt sichtbar; Inhalte überdecken sie nicht. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A7 | Wochenendbezeichnung | Samstag und Sonntag in beiden Ausrichtungen ansehen. | Es stehen nur Wochentag und Datum dort; das zusätzliche Wort „Wochenende“ erscheint nicht. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A8 | Kompakte leere Sondertage | Einen leeren Samstag, Sonntag und gesetzlichen Feiertag in beiden Ausrichtungen anzeigen. | Leere Sondertage sind deutlich platzsparender; bei Tagen als Spalten besitzen sie eine feste schmale Breite. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A9 | Urlaubsmarker und Kompaktklasse | Auf einem sonst leeren Wochenend- oder Feiertag nur einen geplanten beziehungsweise genehmigten Urlaubsmarker anzeigen. | Der Urlaubsmarker allein vergrößert den Tag nicht. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A10 | Automatische Normalgröße bei Einträgen | Auf einem kompakten Sondertag für eine sichtbare Person einen Dienst oder Termin anlegen und danach löschen. | Mit Eintrag verwendet der gesamte Tag Normalgröße; nach dem Löschen wird er wieder kompakt, sofern kein anderer Dienst oder Termin vorliegt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A11 | Tastatur und Fokus | Tabs, Zeitraum, Navigation, Ausrichtung, Filter, Schnellaktionen und Dialoge nur mit Tab, Umschalt+Tab, Eingabe und Escape bedienen. | Alle Funktionen sind erreichbar; Fokus ist sichtbar; kein Dialog erzeugt eine Tastaturfalle. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## B. Personen-, Rollen- und Bereichsfilter

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Personenfilter | Nach einem neutralen Testkonto suchen, auswählen und die Auswahl wieder zurücksetzen. | Nur die ausgewählte Person erscheint; Status und Rücksetzen sind verständlich und vollständig. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | ODER innerhalb der Rollen | Zwei Rollen auswählen, die jeweils mindestens ein unterschiedliches Testkonto enthalten. | Personen mit Rolle A oder Rolle B erscheinen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | ODER innerhalb der Bereiche | Zwei Bürobereiche auswählen. | Personen aus Bereich A oder Bereich B erscheinen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | Schnittmenge Rolle und Bereich | Eine bereichsgebundene Rolle und genau einen Bereich auswählen. | Nur Personen erscheinen, die beide Kriterien erfüllen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | Vorrangige Rolle | Ein Testkonto mit Leitungsrolle und zusätzlicher unterstellter Rolle über beide Rollenfilter suchen. | Die Person wird nur über ihre nach Organisationsreihenfolge vorrangige Rolle gefunden. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B6 | Bereichsübergreifende Büroleitung | Eine Büroleitung mit Nordost und West nacheinander über beide Bereichsfilter sowie gemeinsam anzeigen. | Sie wird über beide Bereiche gefunden, erscheint in der Matrix aber nur einmal. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B7 | Leitungs-/Stabsblock | Den gemeinsamen Anzeigen-/Ausblenden-Schalter für GF, PDL, Sekretariat, HR, QMB und Assistenz GF Digi betätigen. | Der Block wird vollständig ein- beziehungsweise ausgeblendet und gemäß Organisationshierarchie sortiert. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Der Schalter war nur bei zusätzlicher Bereichsauswahl bedienbar; siehe `ADC-STAGING-FOLLOWUP` in `ROADMAP.md`. |
| B8 | Persönlicher Standard | Filter, Zeitraum und Ausrichtung wählen, „Zum Standard machen“ drücken, App neu laden und anschließend eine andere Konfiguration ohne Speichern testen. | Nur bewusst gespeicherte Werte werden nach dem Neuladen wiederhergestellt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## C. Reihenfolge, Hierarchie und Bearbeitungsrechte

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| C1 | Gruppenreihenfolge und Betriebskürzel | BO und EB aus mehreren Bereichen sowie PFK, Büroorganisation Pflege, IT, Stv. PDL, Fahrzeugverwaltung und Empfang gemeinsam anzeigen. | Die Übersicht zeigt unter anderem `BO-NO`, `EB-W`, `PFK`, `BO-Pflege` und `IT`; Stv. PDL steht vor Büroorganisation Pflege und PFK, Fahrzeugverwaltung folgt auf IT und Empfang auf Sekretariat. Filter und Administration behalten die Langnamen. | [ ] erfolgreich [x] nicht erfolgreich [ ] nicht geprüft | Bei gewähltem Bereich fehlten globale Rollen ohne natürliche Bereichszuordnung, darunter IT und PFK; siehe `ADC-STAGING-FOLLOWUP` in `ROADMAP.md`. |
| C2 | Hierarchie der globalen Gruppen | Mit passenden neutralen Leitungskonten Einträge der jeweils unterstellten Testkonten anlegen oder ändern. Gegenrichtung ebenfalls versuchen. | PDL/Stv. PDL, GF Digi/Fahrzeugverwaltung und Sekretariat/Empfang folgen der festgelegten Hierarchie; Untergebene dürfen Vorgesetzte nicht bearbeiten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C3 | Bereichsgebundene Führung | Mit BL und StvBL BO/EB im eigenen und in einem fremden Bereich bearbeiten. PFK ebenfalls versuchen. | BO/EB im passenden Bereich sind bearbeitbar; fremder Bereich und PFK sind nicht bearbeitbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C4 | Schutz bei Mehrfachrollen | Mit einem normalen EB-Konto versuchen, eine StvBL zu bearbeiten, die zusätzlich EB ist. | Die Leitungsrolle schützt die Person; Bearbeitung wird serverseitig abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| C5 | Eigene Einträge | Mit einem normalen Testkonto eigenen Dienst und eigenen Termin anlegen, ändern und löschen. | Eigene Einträge sind vollständig bearbeitbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## D. Dienste, Termine, Meetings, Standards und Urlaub

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| D1 | Dienst mit zugeordnetem Termin | Dienst anlegen und darin einen Termin anlegen. | Der Termin erscheint innerhalb des Dienstes und bleibt diesem fachlich zugeordnet. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D2 | Sperrtermin | Termin außerhalb eines Dienstes anlegen. | Er erscheint ausdrücklich als Sperrtermin und nicht nur mit einer anderen Farbe. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D3 | Wiederholter Termin | Eine tägliche, wöchentliche und monatliche Testserie mit Enddatum anlegen; ein Vorkommen einzeln ändern und löschen. | Serie und Einzelabweichungen folgen den gewählten Regeln; ungültige Serien werden verständlich abgewiesen. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D4 | Meeting-Lückensuche | Mindestens zwei Personen auswählen, eine Mindestdauer setzen, freie Woche suchen, weitersuchen und eine Person abwählen. | Nur gemeinsame freie Zeiten innerhalb vorhandener Dienste erscheinen; Termine und genehmigte Urlaube werden abgezogen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D5 | Atomares gemeinsames Meeting | Einen gefundenen Slot mit berechtigtem Konto blockieren, gemeinsam ändern und löschen. Danach mit fehlendem Recht auf mindestens eine Person wiederholen. | Berechtigt entstehen/ändern/löschen sich alle Einträge gemeinsam; bei fehlendem Recht entsteht kein Teileintrag. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D6 | Standard-Dienstzeiten | Für einen Wochentag Beginn/Ende speichern, Woche öffnen und einen Standard über Mitternacht testen. | Standards werden als normale Dienste materialisiert; Ende vor Beginn reicht in den Folgetag. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D7 | Abweichung und Lösch-Tombstone | Einen materialisierten Standarddienst ändern und einen anderen löschen; Woche neu laden. | Änderung bleibt einmalig erhalten; gelöschter Dienst erscheint für genau dieses Datum nicht erneut. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D8 | Geplanter Urlaub | Mit aktiver AD-Urlaub-Integration einen geplanten Urlaub anzeigen und am selben Tag einen Dienst sowie einen Sperrtermin anlegen. Standardmaterialisierung und Meetinglücke ebenfalls prüfen. | `U?` erscheint read-only, blockiert Dienste und Standardmaterialisierung, lässt Sperrtermine zu und wird nicht aus Meetinglücken entfernt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D9 | Genehmigter Urlaub | Einen genehmigten Urlaub anzeigen und Dienst, Sperrtermin, Standardmaterialisierung sowie Meetinglücke für diesen Tag prüfen. | `U` erscheint read-only, blockiert Dienste und Standardmaterialisierung, lässt Sperrtermine zu und wird aus Meetinglücken entfernt. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| D10 | Assistenzkonflikt mit aktivem AdPlaner | Für ein neutrales in beiden Apps schichtfähiges Konto eine Assistenzschicht belegen. In breiter und schmaler Kalenderzelle ansehen; einen überlappenden und einen direkt anschließenden Dienst sowie einen regelmäßigen Standarddienst versuchen. Danach AdPlaner deaktivieren und mit synthetischen Daten erneut prüfen. | Die Sperrzeit erscheint read-only als `Assistenz` beziehungsweise `AS`. Echte Überlappungen werden manuell und automatisch verhindert, direkte Anschlüsse bleiben möglich. Ohne AdPlaner bleibt der Kalender nutzbar und zeigt keine Assistenzsperre. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## E. Persönlicher Nextcloud-Dienstkalender

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| E1 | Erster einseitiger Abgleich | Für ein neutrales Konto mit vorhandenem Dienst die Synchronisation aktiviert lassen und den nächsten Abgleich abwarten. Nextcloud-Kalender öffnen. | Ein privater Kalender mit dem aktuell administrativ konfigurierten Namen entsteht und enthält den vorhandenen Dienst. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E2 | Ausschließlich eigene Dienste | Eigenen und fremden Dienst sowie eigenen Termin und Urlaub vergleichen. | Im persönlichen Zielkalender erscheinen ausschließlich die Dienste des Kontos. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E3 | Idempotente Aktualisierung | Einen Dienst ändern, den Abgleich abwarten und denselben Abgleich ohne weitere Änderung erneut abwarten. | Das vorhandene Kalenderobjekt wird aktualisiert; es entsteht kein Duplikat. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E4 | Löschung | Einen synchronisierten Dienst im AD Kalender löschen und den Abgleich abwarten. | Nur das zugehörige app-eigene Kalenderobjekt wird entfernt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E5 | Opt-out und fremdes Objekt | Im Zielkalender ein neutrales fremdes Objekt anlegen, Synchronisation deaktivieren und den Abgleich abwarten. | App-eigene Dienst-, Termin- und Urlaubsobjekte verschwinden; das fremde Objekt bleibt. Der Kalender wird nur gelöscht, wenn er anschließend leer ist. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E6 | Erneute Aktivierung | Synchronisation wieder aktivieren und den Abgleich abwarten. | Der vollständige aktuelle Bestand eigener Dienste, Termine und bounded gelesener Urlaube wird wiederhergestellt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| E7 | Aggregierter Hintergrundstatus | Einen erfolgreichen Abgleich und anschließend mit einem neutralen absichtlich nicht erreichbaren Testprovider einen Teilfehler auslösen. Als Admin den AD-Kalender-Abschnitt öffnen; als Nichtadmin denselben Bereich versuchen. | Der Admin sieht Zeitpunkt und ausschließlich aggregierte Anzahlen sowie Erfolg/Warnung. Nichtadmin erhält keinen Adminbereich. Konten, Kalender, Provider, URLs, Kennungen, Zugangsdaten und Fehlerdetails erscheinen nicht. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## F. Kopano und manuelles CalDAV

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| F1 | Kopano-Verbindung | Unter „Einstellungen“ Kopano öffnen, vorbelegte Adresse prüfen, gültiges neutrales Testkonto verbinden und Passwort nicht dokumentieren. | Verbindung wird bestätigt; das Passwort ist danach nicht mehr im Formular sichtbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F2 | Manueller CalDAV-Anbieter | Einen freigegebenen HTTPS-CalDAV-Endpunkt mit neutralem Testkonto verbinden. | Verbindung wird bestätigt; unsichere oder ungültige Adressen werden verständlich abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F3 | Externer Exportvertrag | Nach F1/F2 Dienst anlegen, ändern und löschen; beim Anbieter jeweils den Kalender mit dem aktuell administrativ konfigurierten Namen prüfen. Zusätzlich einen Termin und Urlaub anlegen. | Dienst wird ohne Duplikate nachgeführt und gelöscht; Termin und Urlaub werden nicht exportiert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F4 | Kein Rückimport | Im externen Zielkalender ein neutrales Objekt anlegen und AD Kalender neu laden. | Das externe Objekt erscheint nicht im AD Kalender. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F5 | Parallele Verbindungen und Fehlerisolation | Kopano und manuelles CalDAV parallel verbinden; einen Anbieter vorübergehend mit ungültigen Testdaten stören und einen Dienst ändern. | Der andere Anbieter und die führenden AD-Daten bleiben funktionsfähig. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F6 | Trennen | Beide Provider einzeln trennen. | Status wird je Provider aktualisiert; die jeweils andere Verbindung bleibt unberührt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F7 | Administrativer read-only Kopano-Test | Als Nextcloud-Admin Adresse und temporäre Zugangsdaten im AD-Kalender-Adminabschnitt prüfen; danach als Nichtadmin denselben API-/UI-Weg versuchen. | Admin erhält das Ergebnis einer reinen Leseprüfung; Passwort wird geleert und nicht gespeichert. Nichtadmin-Zugriff wird abgewiesen. Es wird kein Kalender angelegt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| F8 | Diagnose eines nicht freigegebenen Endpunkts | Einen dafür vorgesehenen Testendpunkt verwenden, der CalDAV mit HTTP 405 ablehnt. | Die Meldung erklärt den nicht freigegebenen CalDAV-Zugriff und behauptet nicht, AD Kalender könne ihn selbst freischalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## G. Administrative Kalenderdefaults und Bestandsumbenennung

Für diese Fälle zuerst einen neutralen Ausgangsnamen und eine ausschließlich
für die Abnahme vorgesehene gültige HTTPS-CalDAV-Adresse verwenden. Nach der
Prüfung die gewünschten Betriebswerte wiederherstellen. Keine internen
Kalender- oder Providerkennungen in diesem Formular notieren.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| G1 | Bestandsdefaults ohne gesetzte Konfiguration | Vor der ersten bewussten Speicherung den AD-Kalender-Adminabschnitt und einen neuen persönlichen Kopano-Dialog öffnen. | `https://mail.adberlin.org/` und `AD Dienste` erscheinen als unveränderte Bestandsdefaults. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G2 | Gültiges administratives Speichern | Als Nextcloud-Admin eine gültige HTTPS-CalDAV-Vorgabe und einen neutralen neuen Kalendernamen speichern, Seite neu laden und einen noch nicht verbundenen persönlichen Kopano-Dialog öffnen. | Beide normalisierten Werte bleiben nach dem Neuladen erhalten; der neue Dialog verwendet die neue URL-Vorgabe und neue Kalender erhalten den neuen Namen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G3 | Validierung ohne Teilkonfiguration | Nacheinander HTTP, direkte IP, URL mit Zugangsdaten, leeren Namen, Namen über 255 Zeichen und Namen mit Steuerzeichen absenden; danach die Adminseite neu laden. | Jeder ungültige Versuch wird abgewiesen und beide zuvor gültigen Werte bleiben gemeinsam unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G4 | Admin-, Anonym- und CSRF-Schutz | Als Nichtadmin den Adminabschnitt und den Speichervorgang versuchen. Den API-Aufruf zusätzlich ohne gültiges Requesttoken sowie ohne Sitzung wiederholen, ohne Zugangsdaten im Werkzeug zu protokollieren. | Nur der bestätigte Admin mit gültigem CSRF-Token darf speichern; alle anderen Versuche ändern keinen AppConfig-Wert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G5 | Gespeicherte persönliche Serveradresse bleibt stabil | Vor G2 ein neutrales Kopano-/CalDAV-Testkonto mit einer von der Vorgabe abweichenden HTTPS-Adresse verbinden. G2 durchführen, Dienst abgleichen und persönlichen Status erneut öffnen. | Die bereits gespeicherte persönliche Server- und Kalenderadresse bleibt unverändert; nur der sichtbare app-eigene Kalendername wird fällig. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G6 | Interne Bestandsumbenennung | Für ein neutrales Konto mit bestehendem internem Dienstkalender einen Dienst synchronisieren, den Kalendernamen administrativ ändern und den nächsten ausgehenden Abgleich abwarten. Den Dienst danach ändern und erneut abgleichen. | Derselbe Kalender trägt den neuen sichtbaren Namen; Dienst und Aktualisierung bleiben ohne Duplikat erhalten. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G7 | Externe CalDAV-Bestandsumbenennung | Mit einer bestehenden neutralen CalDAV-Verbindung und synchronisiertem Dienst den Namen administrativ ändern und den nächsten Abgleich abwarten. Danach denselben Abgleich wiederholen. | Derselbe externe Kalender trägt den neuen Namen; URL und Dienstobjekte bleiben stabil und die Wiederholung erzeugt weder weiteren Kalender noch Duplikat. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G8 | Fremdkalenderschutz | Den sichtbaren Namen eines verbundenen Testkalenders direkt beim Anbieter auf einen dritten, AD Kalender unbekannten Namen ändern und anschließend einen neuen administrativen Namen sowie einen Abgleich auslösen. | AD Kalender benennt den nicht mehr sicher als app-eigen belegten Kalender nicht um und meldet den Providerfehler, ohne führende AD-Daten zu verändern. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G9 | Providerfehler, Isolation und Wiederholung | Zwei neutrale Provider parallel verbinden. Einen davon während einer fälligen Umbenennung unerreichbar machen, abgleichen, anschließend wieder erreichbar machen und erneut abgleichen. | Der erreichbare Provider wird umbenannt und weiter synchronisiert; der Fehler des anderen blockiert ihn nicht. Nach Wiederherstellung wird die ausstehende Umbenennung erfolgreich nachgeholt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| G10 | Kontextgerechte Ausgabe des Namens | Einen gültigen Testnamen mit `&`, `<`, `>`, Anführungszeichen und Umlauten speichern und in Adminseite, persönlicher Einstellung sowie an einem Test-CalDAV-Ziel prüfen. | Der Name erscheint als Text und als korrekter DAV-Anzeigename; kein Zeichen wird als HTML oder XML ausgeführt und es entsteht kein Markupfehler. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## H. Aufgeschobene Providerabnahme

Die folgenden optionalen Providerprüfungen werden bei verfügbarer, freigegebener Testumgebung ausgeführt.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| H1 | Google OAuth und Export | OAuth-Konfiguration prüfen, neutrales Testkonto verbinden und Dienstexport/-aktualisierung/-löschung sowie fehlenden Rückimport prüfen. | Autorisierung verwendet den angezeigten Redirect und begrenzten Scope; ausschließlich Dienste werden idempotent exportiert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| H2 | Apple CalDAV und Export | Mit app-spezifischem Testpasswort verbinden und F3 bis F6 wiederholen. | Ausschließlich Dienste werden idempotent exportiert; keine Zugangsdaten erscheinen in Anzeige oder Formular. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## I. Lokalisierung

Für den Sprachwechsel nur neutrale Testdaten verwenden; technische IDs und
konfigurierte Eigennamen dürfen sich nicht ändern.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| I1 | Deutsche und englische Oberfläche | Nextcloud-Sprache nacheinander auf Deutsch und English (United Kingdom) stellen. Kalender, persönliche Einstellungen, Dialoge und Adminbereich jeweils neu laden. | Alle sichtbaren Bedienelemente, Hilfen, Status- und Fehlermeldungen folgen der aktiven Sprache; es erscheinen keine gemischten deutschen/englischen Rohtexte oder internen Schlüssel. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| I2 | Lange Beschriftungen und kleine Viewports | In englischer Sprache Kalendernavigation, Zeitraumumschalter, Filter, Dialoge und Adminbereich bei normaler Breite sowie höchstens 700 px prüfen. | Beschriftungen umbrechen ohne Überlagerung oder abgeschnittene Bedienaktionen; Fokus, Scrollleisten und Dialogaktionen bleiben erreichbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| I3 | Plural und dynamische Werte | Suchergebnisse, Filterstatus und Serienaktionen jeweils mit einem und mehreren Treffern/Vorkommen auslösen. Einen neutralen Kalendernamen mit Umlauten und HTML-Sonderzeichen verwenden. | Singular/Plural und eingesetzte Zahlen/Namen sind grammatisch und vollständig; keine Platzhalter bleiben sichtbar und Sonderzeichen werden nicht als Markup ausgeführt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| I4 | Lokalisierte Fehler bei stabilen Codes | In beiden Sprachen je eine ungültige Eintrags-, Provider- und Adminaktion auslösen und die JSON-Antwort nur ohne Geheimnisse prüfen. | Die Meldung wechselt die Sprache; maschinenlesbarer Fehlercode und HTTP-Status bleiben identisch. Keine URL, Kennung oder Zugangsdaten erscheinen in der Meldung. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| I5 | Veröffentlichte Dienstereignisse | Einen betitelten und einen unbetitelten synthetischen Dienst in den privaten Nextcloud-Kalender sowie – sobald H1 freigegeben ist – nach Google synchronisieren. | Eigene Titel bleiben unverändert. Fallbacktitel und Beschreibung folgen der aktiven Locale; technische Event-ID, Eigentumsmarker und konfigurierter Kalendername bleiben stabil. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| I6 | Sprachwechsel ohne fachliche Zustandsänderung | Zeitraum, Ausrichtung, Filter, Serienstatus und Providerverbindungen merken, Sprache wechseln und dieselben Objekte erneut öffnen. | Nur die Darstellung ändert sich. Rechte, Auswahl, technische Statuswerte, Termine, Serien und Verbindungen bleiben unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## J. DPO-gesteuerte Adminfreigabe

Diese Runtime-Abnahme verwendet ausschließlich neutrale Testkonten. Sie bleibt
offen, bis eine ausdrücklich freigegebene DDEV- oder Staging-Umgebung verfügbar
ist.

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| J1 | DPO ohne native Adminrolle | Mit einem Mitglied von `Datenschutzbeauftragte` ohne Adminrolle AD Kalender öffnen, Historie laden und einem aktiven nativen Testadmin für eine Stunde Zugriff erteilen. | Steuerung und Historie sind erreichbar; die Freigabe wird genau einmal protokolliert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| J2 | Nativer Admin ohne DPO-Rolle | Als nativer Testadmin ohne DPO-Rolle AD Kalender ohne aktive Freigabe öffnen und die Freigabe-API direkt lesen sowie schreibend aufrufen. | Sichere Hinweismeldung ohne Direktlink; Historie und Mutation werden verweigert und bleiben unverändert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| J3 | Gewöhnliches Konto und ungültige Ziele | Als gewöhnliches Konto direkte API-Aufrufe versuchen; als DPO ein Nichtadmin-Ziel und eine Dauer über 24 Stunden senden. | Keine Steuerung oder Historie wird offengelegt; alle Aufrufe werden ohne Mutation verweigert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| J4 | CSRF, Ablauf, Widerruf und Rollenverlust | Schreibenden Request ohne CSRF senden, eine aktive Freigabe widerrufen, Ablauf abwarten beziehungsweise kontrolliert simulieren und Ziel-Adminstatus sowie DPO-Rolle jeweils entziehen. | Fehlendes CSRF mutiert nicht; Widerruf, Ablauf und Verlust des Ziel-Adminstatus beenden den fachlichen Zugriff; ohne DPO-Rolle sind Steuerung und Historie nicht mehr erreichbar. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| J5 | Tastaturbedienung und Fokus | Steuerung, Dauer, Bestätigung, Absenden, Historie und Widerruf nur per Tastatur bedienen; Fehlermeldung auslösen. | Alle Funktionen sind erreichbar, Fokus sichtbar, Status verständlich und Fehler werden als Alert ausgegeben. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |



## Abschlussentscheidung

| Feld | Eintrag |
|---|---|
| Anzahl erfolgreich | |
| Anzahl nicht erfolgreich | |
| Anzahl nicht geprüft | |
| Kritische Abweichungen / Ticketreferenzen | A5, B7 und C1: siehe `ADC-STAGING-FOLLOWUP` in `ROADMAP.md`. Die UI-Nacharbeit zur temporären Adminfreigabe ist systemweit als `DP-11` im Parent-Zukunftsplan erfasst. |
| Erneute Prüfung erforderlich bis | |
| Gesamtentscheidung | [ ] abgenommen [ ] mit Auflagen abgenommen [x] nicht abgenommen |
| Begründung der Gesamtentscheidung | Nicht abgenommen wegen der dokumentierten Filterabweichungen, der noch nicht zusammengeführten Adminfreigabe-Oberfläche und fehlender neutraler Testkonten für alle Bereiche. |
| Name / Datum | |
