<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Privacy;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\AdCalendar\AppInfo\AppId;
use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\AdCalendar\Repository\TemporaryAdminAccessRepository;
use OCA\AdCalendar\Service\CalendarPreferenceService;
use OCA\LocalBase\Calendar\CalendarContextSettingsService;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;

final class CalendarPersonalDataProvider implements PersonalDataProvider {
    public function __construct(
        private CalendarEntryRepository $entries,
        private CalendarContextSettingsService $calendarContext,
        private CalendarPreferenceService $preferences,
        private ExternalCalendarConnectionStore $externalConnections,
        private TemporaryAdminAccessRepository $adminAccess,
    ) {}

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor(AppId::VALUE, 'AD Kalender', '1.0', ['nextcloud-user'], ['personal-data'], 500);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('AD Kalender does not support cursor paging.');
        $timezone = $this->calendarContext->context()->timezone();
        $subjectUid = $request->subject()->subjectId();
        $entries = $this->entries->findByEmployeeUid($subjectUid, $request->pageLimit() + 1);
        $meetingUids = array_values(array_unique(array_filter(array_map(static fn(CalendarEntry $entry): ?string => $entry->meetingUid(), $entries))));
        $meetingsWithOthers = array_fill_keys($this->entries->findMeetingUidsWithOtherParticipants($subjectUid, $meetingUids), true);
        $items = array_map(
            fn(CalendarEntry $entry): PersonalDataEntry => $this->item($entry, $timezone, isset($meetingsWithOthers[$entry->meetingUid() ?? ''])),
            $entries,
        );

        $preferenceProjection = $this->preferences->personalDataProjection($subjectUid);
        $items = [...$items, ...$this->preferenceItems($preferenceProjection)];
        $items = [...$items, ...$this->externalConnectionItems($subjectUid)];
        $items = [...$items, ...$this->adminAccessItems($subjectUid, $timezone, $request->pageLimit() + 1)];
        $limited = count($items) > $request->pageLimit();
        if ($limited) $items = array_slice($items, 0, $request->pageLimit());
        $restrictions = [];
        if ($limited) $restrictions[] = 'Ausgabelimit erreicht; weitere Kalenderdaten können vorhanden sein.';
        if (($preferenceProjection['incomplete'] ?? false) === true) $restrictions[] = 'Mindestens ein persönlicher Kalenderwert war gespeichert, aber nicht sicher auswertbar.';
        if ($items === [] && $restrictions === []) return new PersonalDataPage('not_applicable');

        return new PersonalDataPage($restrictions === [] ? 'complete' : 'partial', $items, $restrictions);
    }

    private function preferenceItems(array $projection): array {
        $items = [];
        $filter = is_array($projection['filter'] ?? null) ? $projection['filter'] : null;
        if ($filter !== null) {
            $selectedPeopleCount = (int)($filter['selectedPeopleCount'] ?? 0);
            $items[] = new PersonalDataEntry(
                categoryId: 'calendar_filter_preference',
                categoryLabel: 'Persönlicher Kalenderfilter',
                reference: 'calendar-preference:filter-default',
                summary: 'Bewusst gespeicherter Standard für die Kalenderansicht',
                purpose: 'Persönliche Vorauswahl und Darstellung der Dienstplanung',
                source: 'Persönliche Nextcloud-Benutzerkonfiguration der betroffenen Person',
                recipientCategories: ['Die betroffene Person innerhalb der AD-Kalender-Oberfläche'],
                retention: 'Bis zur Änderung des persönlichen Standards oder zur Bereinigung der Nextcloud-Benutzerkonfiguration.',
                thirdCountryTransfer: 'Durch diese persönliche Filtereinstellung ist keine Drittlandübermittlung vorgesehen.',
                automatedDecision: 'Die Einstellung steuert nur die Vorauswahl der Ansicht und trifft keine Entscheidung über Personen.',
                thirdPartyContentNotice: $selectedPeopleCount > 0 ? 'Ausgewählte Personen werden nur gezählt; ihre Nextcloud-IDs werden als Angaben Dritter nicht ausgegeben.' : null,
                attributes: [
                    'Ausgewählte Personen' => $selectedPeopleCount,
                    'Rollen' => self::listLabel($filter['roles'] ?? []),
                    'Bereiche' => self::listLabel($filter['areas'] ?? []),
                    'Zeitraum' => ($filter['period'] ?? 'week') === 'month' ? 'Monat' : 'Woche',
                    'Ausrichtung' => ($filter['vertical'] ?? true) ? 'Tage als Zeilen' : 'Personen als Zeilen',
                    'Leitung und Stab anzeigen' => ($filter['showLeadershipStaff'] ?? true) ? 'ja' : 'nein',
                    'Nur Leitung und Stab' => ($filter['leadershipStaffOnly'] ?? false) ? 'ja' : 'nein',
                ],
            );
        }

        $defaults = is_array($projection['shiftDefaults'] ?? null) ? $projection['shiftDefaults'] : null;
        if ($defaults !== null) {
            $weekdayNames = ['1' => 'Montag', '2' => 'Dienstag', '3' => 'Mittwoch', '4' => 'Donnerstag', '5' => 'Freitag', '6' => 'Samstag', '7' => 'Sonntag'];
            $attributes = [];
            foreach ($weekdayNames as $weekday => $label) {
                $value = is_array($defaults[$weekday] ?? null) ? $defaults[$weekday] : [];
                $attributes[$label] = ($value['enabled'] ?? false)
                    ? (string)($value['start'] ?? '') . ' bis ' . (string)($value['end'] ?? '') . ' Uhr'
                    : 'deaktiviert';
            }
            $items[] = new PersonalDataEntry(
                categoryId: 'shift_defaults',
                categoryLabel: 'Persönliche Standard-Dienstzeiten',
                reference: 'calendar-preference:shift-defaults',
                summary: 'Bewusst gespeicherte Vorschlagszeiten für wiederkehrende Dienste',
                purpose: 'Vorschlag und Materialisierung persönlicher Standarddienste',
                source: 'Persönliche Nextcloud-Benutzerkonfiguration der betroffenen Person',
                recipientCategories: ['Angemeldete Nutzer*innen der Instanz nach Materialisierung als normaler Dienst', 'Berechtigte planende Personen'],
                retention: 'Bis zur Änderung der persönlichen Standard-Dienstzeiten oder zur Bereinigung der Nextcloud-Benutzerkonfiguration.',
                thirdCountryTransfer: 'AD Kalender selbst sieht für diese Standardwerte keine Drittlandübermittlung vor.',
                automatedDecision: 'Aktivierte Standardzeiten können Dienste vorschlagen oder materialisieren; Urlaubs- und Konfliktregeln bleiben wirksam.',
                thirdPartyContentNotice: null,
                attributes: $attributes,
            );
        }

        if (is_bool($projection['calendarSyncEnabled'] ?? null)) {
            $enabled = $projection['calendarSyncEnabled'];
            $items[] = new PersonalDataEntry(
                categoryId: 'calendar_sync_preference',
                categoryLabel: 'Persönlicher Nextcloud-Kalenderabgleich',
                reference: 'calendar-preference:native-sync',
                summary: 'Bewusst gespeicherter ' . ($enabled ? 'Opt-in-Zustand' : 'Opt-out-Zustand') . ' des privaten Kalenderabgleichs',
                purpose: 'Steuerung des abgeleiteten privaten Nextcloud-Kalenders für eigene Dienste, Termine und Urlaube',
                source: 'Persönliche Nextcloud-Benutzerkonfiguration der betroffenen Person',
                recipientCategories: ['Die betroffene Person im privaten Nextcloud-Kalender'],
                retention: 'Bis zur Änderung der persönlichen Einstellung oder zur Bereinigung der Nextcloud-Benutzerkonfiguration.',
                thirdCountryTransfer: 'Der interne Nextcloud-Kalenderabgleich sieht keine zusätzliche Drittlandübermittlung vor.',
                automatedDecision: 'Der Abgleich erzeugt nur eine abgeleitete Kalenderdarstellung und verändert die führenden AD-Kalenderdaten nicht.',
                thirdPartyContentNotice: 'Die abgeleiteten Kalenderobjekte werden nicht erneut ausgegeben; Dienste und Termine stammen aus den bereits aufgeführten AD-Kalendereinträgen, Urlaube aus der zuständigen Abwesenheits-App.',
                attributes: ['Privater Nextcloud-Kalender' => $enabled ? 'aktiviert' : 'deaktiviert'],
            );
        }

        return $items;
    }

    private function adminAccessItems(string $subjectUid, DateTimeZone $timezone, int $limit): array {
        $items = [];
        foreach ($this->adminAccess->historyForUid($subjectUid, $limit) as $grant) {
            $roles = [];
            if ($grant['targetUid'] === $subjectUid) $roles[] = 'Ziel der Vollzugriffsfreigabe';
            if ($grant['grantedBy'] === $subjectUid) $roles[] = 'Freigebendes Mitglied von Datenschutzbeauftragte';
            if ($grant['revokedBy'] === $subjectUid) $roles[] = 'Widerrufendes Mitglied von Datenschutzbeauftragte';
            $actualEnd = $grant['revokedAt'] ?? $grant['endsAt'];
            $items[] = new PersonalDataEntry(
                categoryId: 'admin-access', categoryLabel: 'Zeitlich begrenzter Admin-Vollzugriff', reference: 'admin-access:' . (string)$grant['id'],
                summary: sprintf('%s bis %s', self::germanDateTime($grant['startsAt']->setTimezone($timezone)), self::germanDateTime($actualEnd->setTimezone($timezone))),
                purpose: 'Nachweis einer zeitlich begrenzten administrativen Kalenderfreigabe', source: 'App-lokale Freigabesteuerung im AD Kalender',
                recipientCategories: ['Betroffene Person und ausdrücklich berechtigte Datenschutz-Prüfrolle'],
                retention: 'Keine feste Löschfrist festgelegt; die sicherheitsrelevante Freigabehistorie bleibt bis zu einer gesonderten Aufbewahrungsentscheidung erhalten.',
                thirdCountryTransfer: 'Durch AD Kalender sind keine Drittlandübermittlungen für diese Freigabehistorie vorgesehen.',
                automatedDecision: 'Der Server beendet den Vollzugriff spätestens nach 24 Stunden automatisch.',
                thirdPartyContentNotice: 'Kennungen anderer beteiligter Personen werden in dieser subjectgebundenen Auskunft nicht ausgegeben.',
                attributes: ['Eigene Rolle im Vorgang' => implode(', ', $roles), 'Beginn' => self::germanDateTime($grant['startsAt']->setTimezone($timezone)), 'Geplantes Ende' => self::germanDateTime($grant['endsAt']->setTimezone($timezone)), 'Tatsächliches Ende' => self::germanDateTime($actualEnd->setTimezone($timezone)), 'Status' => $grant['revokedAt'] === null ? 'planmäßig beendet oder noch aktiv' : 'widerrufen'],
            );
        }
        return $items;
    }

    private function externalConnectionItems(string $subjectUid): array {
        $providers = $this->externalConnections->privacyConnectedProviders($subjectUid);
        $pendingOAuth = $this->externalConnections->hasPendingGoogleOAuthState($subjectUid);
        if ($providers === [] && !$pendingOAuth) return [];
        $labels = ['kopano' => 'Kopano', 'google' => 'Google', 'apple' => 'Apple', 'manual' => 'Manuelles CalDAV'];
        $providerLabels = array_map(static fn(string $provider): string => $labels[$provider] ?? 'Unbekannter Anbieter', $providers);

        return [new PersonalDataEntry(
            categoryId: 'external_calendar_connections',
            categoryLabel: 'Persönliche externe Kalenderverbindungen',
            reference: 'calendar-preference:external-connections',
            summary: 'Gespeicherte Verbindungsmetadaten ohne Adressen, Kontonamen, Kalenderkennungen oder Zugangsdaten',
            purpose: 'Einseitige Veröffentlichung eigener Dienste in persönlich verbundenen Kalenderdiensten',
            source: 'Verschlüsselte sensible Nextcloud-Benutzerkonfiguration der betroffenen Person',
            recipientCategories: $providerLabels === [] ? ['Noch kein verbundener externer Kalenderdienst'] : $providerLabels,
            retention: 'Bis zum Trennen der Verbindung, Verbrauch beziehungsweise Ablauf des OAuth-Vorgangs oder zur Bereinigung der Nextcloud-Benutzerkonfiguration.',
            thirdCountryTransfer: 'Eine mögliche Drittlandübermittlung hängt vom persönlich gewählten Kalenderdienst und dessen Betreiber ab.',
            automatedDecision: 'Der einseitige Abgleich veröffentlicht Dienste; er trifft keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung.',
            thirdPartyContentNotice: 'Serveradressen, Benutzernamen, Kalenderkennungen, Passwörter, OAuth-Tokens und OAuth-State werden nicht entschlüsselt oder ausgegeben.',
            attributes: [
                'Verbundene Anbieter' => $providerLabels === [] ? 'Keine abgeschlossene Verbindung' : implode(', ', $providerLabels),
                'Google-OAuth' => $pendingOAuth ? 'Autorisierungsvorgang vorhanden' : 'Kein gespeicherter Autorisierungsvorgang',
            ],
        )];
    }

    private static function listLabel(mixed $values): string {
        if (!is_array($values) || $values === []) return 'Keine Auswahl gespeichert';

        return implode(', ', array_map('strval', $values));
    }

    private function item(CalendarEntry $entry, DateTimeZone $timezone, bool $hasOtherParticipants): PersonalDataEntry {
        $appointment = $entry->type() === CalendarEntry::TYPE_APPOINTMENT;
        $time = self::dateTimeRange($entry->start()->setTimezone($timezone), $entry->end()->setTimezone($timezone));
        $thirdPartyNote = null;
        if ($appointment && $hasOtherParticipants) {
            $thirdPartyNote = 'An diesem gemeinsamen Termin waren weitere Personen beteiligt. Ihre Namen und fremden Kalendereinträge werden hier nicht genannt.';
        }

        return new PersonalDataEntry(
            categoryId: $appointment ? 'appointment' : 'shift',
            categoryLabel: $appointment ? 'Termin' : 'Dienst',
            reference: 'calendar-entry:' . (string)$entry->id(),
            summary: $appointment ? $entry->title() . ' – ' . $time : $time . ($entry->title() !== '' ? ' – ' . $entry->title() : ''),
            purpose: $appointment ? 'Termin- und Verfügbarkeitsplanung' : 'Dienstplanung und Berechnung geplanter Arbeitszeiten',
            source: 'Eingaben der betroffenen oder einer berechtigten planenden Person sowie materialisierte persönliche Standarddienste',
            recipientCategories: ['Angemeldete Nutzer*innen der Instanz', 'Berechtigte planende Personen', 'Bei aktiviertem persönlichen Abgleich die von der betroffenen Person verbundenen Kalenderdienste'],
            retention: 'Keine feste Löschfrist festgelegt; gespeichert bis zur fachlich oder gesetzlich veranlassten Löschung.',
            thirdCountryTransfer: 'AD Kalender selbst sieht keine Drittlandübermittlung vor. Bei persönlich verbundenen externen Kalenderdiensten hängt sie von der gewählten Verbindung und deren Betreiber ab.',
            automatedDecision: 'Konflikt-, Verfügbarkeits- und Zuordnungsprüfungen unterstützen die Planung; sie treffen keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung.',
            thirdPartyContentNotice: $thirdPartyNote,
            attributes: [
                'Titel' => $entry->title() !== '' ? $entry->title() : 'Kein Titel hinterlegt',
                'Beginn' => self::germanDateTime($entry->start()->setTimezone($timezone)),
                'Ende' => self::germanDateTime($entry->end()->setTimezone($timezone)),
            ],
        );
    }

    private static function dateTimeRange(DateTimeImmutable $start, DateTimeImmutable $end): string {
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return self::germanDate($start) . ', ' . $start->format('H:i') . ' bis ' . $end->format('H:i') . ' Uhr';
        }
        return self::germanDateTime($start) . ' bis ' . self::germanDateTime($end);
    }

    private static function germanDateTime(DateTimeImmutable $date): string { return self::germanDate($date) . ', ' . $date->format('H:i') . ' Uhr'; }
    private static function germanDate(DateTimeImmutable $date): string { return $date->format('d.m.y'); }
}
