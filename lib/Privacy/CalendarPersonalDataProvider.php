<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Privacy;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OCA\AdCalendar\AppInfo\AppId;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\LocalBase\Calendar\CalendarContextSettingsService;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataPage;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataProvider;
use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
use OCA\FilzmannDataProtection\PublicApi\V1\ProviderDescriptor;

final class CalendarPersonalDataProvider implements PersonalDataProvider {
    public function __construct(private CalendarEntryRepository $entries, private CalendarContextSettingsService $calendarContext) {}

    public function descriptor(): ProviderDescriptor {
        return new ProviderDescriptor(AppId::VALUE, 'AD Kalender', '1.0', ['nextcloud-user'], ['personal-data'], 500);
    }

    public function collect(PersonalDataRequest $request): PersonalDataPage {
        if ($request->subject()->subjectType() !== 'nextcloud-user') return new PersonalDataPage('not_applicable');
        if ($request->cursor() !== null) throw new InvalidArgumentException('AD Kalender does not support cursor paging.');
        $timezone = $this->calendarContext->context()->timezone();
        $subjectUid = $request->subject()->subjectId();
        $entries = $this->entries->findByEmployeeUid($subjectUid, $request->pageLimit() + 1);
        $limited = count($entries) > $request->pageLimit();
        if ($limited) $entries = array_slice($entries, 0, $request->pageLimit());
        $meetingUids = array_values(array_unique(array_filter(array_map(static fn(CalendarEntry $entry): ?string => $entry->meetingUid(), $entries))));
        $meetingsWithOthers = array_fill_keys($this->entries->findMeetingUidsWithOtherParticipants($subjectUid, $meetingUids), true);
        $items = array_map(
            fn(CalendarEntry $entry): PersonalDataEntry => $this->item($entry, $timezone, isset($meetingsWithOthers[$entry->meetingUid() ?? ''])),
            $entries,
        );
        if ($items === []) return new PersonalDataPage('not_applicable');
        return new PersonalDataPage($limited ? 'partial' : 'complete', $items, $limited ? ['Ausgabelimit erreicht; weitere Kalendereinträge können vorhanden sein.'] : []);
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
