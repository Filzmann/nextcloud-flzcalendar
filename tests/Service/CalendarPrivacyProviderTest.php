<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}
namespace OCP {
    interface IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string;
        public function setValueString(string $appId, string $key, string $value): void;
    }
}
namespace OCA\AdCalendar\Repository {
    use OCA\AdCalendar\Model\CalendarEntry;
    class CalendarEntryRepository {
        /** @var list<CalendarEntry> */ public array $items = [];
        public int $participantQueries = 0;
        /** @return list<CalendarEntry> */
        public function findByEmployeeUid(string $uid, int $limit): array {
            return array_slice(array_values(array_filter($this->items, static fn(CalendarEntry $entry): bool => $entry->employeeUid() === $uid)), 0, $limit);
        }
        /** @return list<string> */
        public function findMeetingUidsWithOtherParticipants(string $subjectUid, array $meetingUids): array {
            $this->participantQueries++;
            return array_values(array_unique(array_filter(array_map(
                static fn(CalendarEntry $entry): ?string => $entry->employeeUid() !== $subjectUid && in_array($entry->meetingUid(), $meetingUids, true) ? $entry->meetingUid() : null,
                $this->items,
            ))));
        }
    }
}

namespace {
    use OCA\AdCalendar\Model\CalendarEntry;
    use OCA\AdCalendar\Privacy\CalendarPersonalDataProvider;
    use OCA\AdCalendar\Privacy\CalendarPrivacyProviderListener;
    use OCA\AdCalendar\Repository\CalendarEntryRepository;
    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCA\LocalBase\Privacy\PersonalDataProviderRegistryEvent;
    use OCA\LocalBase\Privacy\PersonalDataRequest;
    use OCA\LocalBase\Privacy\PersonalDataSubject;

    $entries = new CalendarEntryRepository();
    $entries->items = [
        CalendarEntry::get(['id'=>1,'employeeUid'=>'self','start'=>'2026-08-12T08:00:00+00:00','end'=>'2026-08-12T09:00:00+00:00','type'=>'appointment','title'=>'Gemeinsamer Termin','meetingUid'=>'meeting-a']),
        CalendarEntry::get(['id'=>2,'employeeUid'=>'self','start'=>'2026-08-13T06:00:00+00:00','end'=>'2026-08-13T14:00:00+00:00','type'=>'shift','title'=>'Frühdienst']),
        CalendarEntry::get(['id'=>3,'employeeUid'=>'other-person','start'=>'2026-08-12T08:00:00+00:00','end'=>'2026-08-12T09:00:00+00:00','type'=>'appointment','title'=>'Fremder Titel','meetingUid'=>'meeting-a']),
    ];
    $config = new class implements OCP\IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string { return $default; }
        public function setValueString(string $appId, string $key, string $value): void {}
    };
    $provider = new CalendarPersonalDataProvider($entries, new CalendarContextSettingsService($config));
    $report = $provider->collect(new PersonalDataRequest(new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'self'), 'de', PersonalDataRequest::PURPOSE_SELF_SERVICE, 50));
    if (count($report->items()) !== 2) throw new RuntimeException('Kalenderauskunft liefert fremde Einträge oder lässt eigene aus.');
    $items = array_map(static fn($item): array => $item->toArray(), $report->items());
    $appointment = array_values(array_filter($items, static fn(array $item): bool => $item['category'] === 'appointment'))[0] ?? null;
    $shift = array_values(array_filter($items, static fn(array $item): bool => $item['category'] === 'shift'))[0] ?? null;
    if ($appointment === null || $shift === null) throw new RuntimeException('Termin und Dienst sind nicht getrennt ausgewiesen.');
    if (array_key_exists('Art', $appointment['attributes']) || array_key_exists('Art', $shift['attributes'])) throw new RuntimeException('Der bereits als Tabellenabschnitt ausgewiesene Datentyp wird redundant als Art-Spalte ausgegeben.');
    foreach (['Du hast im Kalender folgende Termine gespeichert:', 'Gemeinsamer Termin', '12.08.26, 10:00 bis 11:00 Uhr', 'Termin- und Verfügbarkeitsplanung', 'Keine feste Löschfrist'] as $expected) {
        if (!str_contains(json_encode($appointment, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE), $expected)) throw new RuntimeException("Menschenlesbarer Terminbestand fehlt: {$expected}");
    }
    if (!str_contains((string)$appointment['thirdPartyNote'], 'weitere Personen beteiligt') || str_contains(json_encode($appointment, JSON_THROW_ON_ERROR), 'other-person') || str_contains(json_encode($appointment, JSON_THROW_ON_ERROR), 'Fremder Titel')) {
        throw new RuntimeException('Drittpersonenhinweis nennt fremde Identitäten oder fehlt.');
    }
    foreach (['Im Kalender sind folgende Dienste für dich gespeichert:', '13.08.26, 08:00 bis 16:00 Uhr', 'Dienstplanung'] as $expected) {
        if (!str_contains(json_encode($shift, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE), $expected)) throw new RuntimeException("Menschenlesbarer Dienstbestand fehlt: {$expected}");
    }
    if ($shift['thirdPartyNote'] !== null) throw new RuntimeException('Ein eigener Dienst behauptet weitere Beteiligte.');
    if ($entries->participantQueries !== 1) throw new RuntimeException('Drittpersonenprüfung wird nicht gebündelt.');
    $limited = $provider->collect(new PersonalDataRequest(new PersonalDataSubject(PersonalDataSubject::NEXTCLOUD_USER, 'self'), 'de', PersonalDataRequest::PURPOSE_SELF_SERVICE, 1));
    if ($limited->isComplete() || !in_array('Ausgabelimit erreicht; weitere Kalendereinträge können vorhanden sein.', $limited->limitations(), true)) throw new RuntimeException('Begrenzter Kalenderbericht behauptet Vollständigkeit.');

    $listener = new CalendarPrivacyProviderListener($provider);
    $registry = new PersonalDataProviderRegistryEvent();
    $listener->handle($registry);
    if (array_keys($registry->providers()) !== ['adcalendar']) throw new RuntimeException('AD Kalender registriert seinen Privacy-Provider nicht.');

    echo "AD Kalender privacy provider test passed\n";
}
