<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Privacy;

use DateTimeImmutable;
use DateTimeZone;
use OCA\AdCalendar\AppInfo\AppId;
use OCA\AdCalendar\Model\CalendarEntry;
use OCA\AdCalendar\Repository\CalendarEntryRepository;
use OCA\LocalBase\Calendar\CalendarContextSettingsService;
use OCA\LocalBase\Privacy\PersonalDataItem;
use OCA\LocalBase\Privacy\PersonalDataProcessingInfo;
use OCA\LocalBase\Privacy\PersonalDataProvider;
use OCA\LocalBase\Privacy\PersonalDataReport;
use OCA\LocalBase\Privacy\PersonalDataRequest;
use OCA\LocalBase\Privacy\PersonalDataSubject;

final class CalendarPersonalDataProvider implements PersonalDataProvider {
    public function __construct(private CalendarEntryRepository $entries, private CalendarContextSettingsService $calendarContext) {}

    public function appId(): string { return AppId::VALUE; }
    public function supportedSubjectTypes(): array { return [PersonalDataSubject::NEXTCLOUD_USER]; }

    public function collect(PersonalDataRequest $request): PersonalDataReport {
        $timezone = $this->calendarContext->context()->timezone();
        $subjectUid = $request->subject()->id();
        $entries = $this->entries->findByEmployeeUid($subjectUid, $request->limit());
        $limited = count($entries) >= $request->limit();
        $meetingUids = array_values(array_unique(array_filter(array_map(static fn(CalendarEntry $entry): ?string => $entry->meetingUid(), $entries))));
        $meetingsWithOthers = array_fill_keys($this->entries->findMeetingUidsWithOtherParticipants($subjectUid, $meetingUids), true);
        $items = array_map(
            fn(CalendarEntry $entry): PersonalDataItem => $this->item($entry, $timezone, isset($meetingsWithOthers[$entry->meetingUid() ?? ''])),
            $entries,
        );

        return new PersonalDataReport($items, new PersonalDataProcessingInfo(
            purposes: ['Dienstplanung', 'Termin- und Verfügbarkeitsplanung', 'Optionaler persönlicher Kalenderabgleich'],
            categories: ['Nextcloud-Kennung', 'Dienste mit Zeitangaben', 'Termine mit Titel und Zeitangaben', 'Technische Serien- oder Meetingzuordnung'],
            recipients: ['Angemeldete Nutzer*innen der Instanz', 'Berechtigte planende Personen', 'Bei aktiviertem persönlichen Abgleich die von der betroffenen Person verbundenen Kalenderdienste'],
            source: 'Eingaben der betroffenen oder einer berechtigten planenden Person sowie materialisierte persönliche Standarddienste',
            retentionCriteria: 'Derzeit ist keine feste Löschfrist konfiguriert. Einträge bleiben bis zu ihrer fachlich oder gesetzlich veranlassten Löschung gespeichert.',
            thirdCountryTransfers: 'AD Kalender selbst sieht keine Drittlandübermittlung vor. Bei persönlich verbundenen externen Kalenderdiensten hängt sie von der gewählten Verbindung und deren Betreiber ab.',
            automatedDecisionMaking: 'Konflikt-, Verfügbarkeits- und Zuordnungsprüfungen unterstützen die Planung; sie treffen keine Entscheidung mit rechtlicher oder vergleichbar erheblicher Wirkung.',
        ), complete: !$limited, limitations: $limited ? ['Ausgabelimit erreicht; weitere Kalendereinträge können vorhanden sein.'] : [], appName: 'AD Kalender');
    }

    private function item(CalendarEntry $entry, DateTimeZone $timezone, bool $hasOtherParticipants): PersonalDataItem {
        $appointment = $entry->type() === CalendarEntry::TYPE_APPOINTMENT;
        $time = self::dateTimeRange($entry->start()->setTimezone($timezone), $entry->end()->setTimezone($timezone));
        $thirdPartyNote = null;
        if ($appointment && $hasOtherParticipants) {
            $thirdPartyNote = 'An diesem gemeinsamen Termin waren weitere Personen beteiligt. Ihre Namen und fremden Kalendereinträge werden hier nicht genannt.';
        }

        return new PersonalDataItem(
            $appointment ? 'appointment' : 'shift',
            $appointment ? $entry->title() . ' – ' . $time : $time . ($entry->title() !== '' ? ' – ' . $entry->title() : ''),
            'calendar-entry:' . (string)$entry->id(),
            [
                'Titel' => $entry->title() !== '' ? $entry->title() : 'Kein Titel hinterlegt',
                'Beginn' => self::germanDateTime($entry->start()->setTimezone($timezone)),
                'Ende' => self::germanDateTime($entry->end()->setTimezone($timezone)),
            ],
            $appointment ? 'Termin- und Verfügbarkeitsplanung' : 'Dienstplanung und Berechnung geplanter Arbeitszeiten',
            'Keine feste Löschfrist festgelegt; gespeichert bis zur fachlich oder gesetzlich veranlassten Löschung.',
            $appointment ? 'Du hast im Kalender folgende Termine gespeichert:' : 'Im Kalender sind folgende Dienste für dich gespeichert:',
            $thirdPartyNote,
            $appointment ? 'Termin' : 'Dienst',
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
