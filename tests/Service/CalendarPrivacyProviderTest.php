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
namespace OCP\Config {
    if (!interface_exists(IUserConfig::class)) {
        interface IUserConfig {
            public const FLAG_SENSITIVE = 1;
            public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string;
            public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool;
            public function getValuesByUsers(string $app, string $key, mixed $typedAs = null, ?array $userIds = null): array;
            public function deleteUserConfig(string $userId, string $app, string $key): void;
        }
    }
}
namespace OCP\Security {
    if (!interface_exists(ICrypto::class)) {
        interface ICrypto {
            public function encrypt(string $plaintext, string $password = ''): string;
            public function decrypt(string $authenticatedCiphertext, string $password = ''): string;
        }
    }
}
namespace OCA\AdCalendar\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application { public const APP_ID = 'adcalendar'; }
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
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\Service\CalendarPreferenceService;
    use OCA\LocalBase\Calendar\CalendarContextSettingsService;
    use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
    use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
    use OCP\Config\IUserConfig;
    use OCP\Security\ICrypto;

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
    $userConfig = new class implements IUserConfig {
        public array $values = [
            'self' => ['adcalendar' => [
                'filter_default' => '{"people":["other-person"],"roles":["ad-Buero"],"areas":["ad-Bereich-Sued"],"vertical":false,"period":"month","showLeadershipStaff":true,"leadershipStaffOnly":false}',
                'shift_defaults' => '{"1":{"enabled":true,"start":"07:30","end":"15:30"}}',
                'shift_calendar_sync_enabled' => '0',
                'external_calendar_google' => 'cipher:access-token-secret',
                'external_calendar_manual' => 'cipher:https://private.example.test/person/self',
                'external_calendar_google_oauth_state' => 'cipher:oauth-secret',
            ]],
        ];
        public function getValueString(string $userId, string $app, string $key, string $default = '', bool $lazy = false): string { return $this->values[$userId][$app][$key] ?? $default; }
        public function setValueString(string $userId, string $app, string $key, string $value, bool $lazy = false, int $flags = 0): bool { $this->values[$userId][$app][$key] = $value; return true; }
        public function getValuesByUsers(string $app, string $key, mixed $typedAs = null, ?array $userIds = null): array { return []; }
        public function deleteUserConfig(string $userId, string $app, string $key): void { unset($this->values[$userId][$app][$key]); }
    };
    $crypto = new class implements ICrypto {
        public int $decryptCalls = 0;
        public function encrypt(string $plaintext, string $password = ''): string { return 'cipher'; }
        public function decrypt(string $authenticatedCiphertext, string $password = ''): string { $this->decryptCalls++; throw new RuntimeException('Privacy provider must not decrypt stored secrets.'); }
    };
    $provider = new CalendarPersonalDataProvider(
        $entries,
        new CalendarContextSettingsService($config),
        new CalendarPreferenceService($userConfig),
        new ExternalCalendarConnectionStore($userConfig, $crypto),
    );
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'adcalendar' || $descriptor->contractVersion() !== '1.0' || !$descriptor->supportsSubjectType('nextcloud-user')) throw new RuntimeException('AD Kalender beschreibt den Standalone-V1-Vertrag nicht korrekt.');
    $subject = new DataSubjectRef('nextcloud-user', 'self');
    $report = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 50, []));
    if (count($report->entries()) !== 6 || $report->status() !== 'complete') throw new RuntimeException('Kalenderauskunft liefert fremde Einträge, lässt persönliche Einstellungen aus oder meldet einen falschen Status.');
    $items = array_map(static fn($item): array => [
        'categoryId'=>$item->categoryId(),'categoryLabel'=>$item->categoryLabel(),'reference'=>$item->reference(),
        'summary'=>$item->summary(),'purpose'=>$item->purpose(),'source'=>$item->source(),
        'recipientCategories'=>$item->recipientCategories(),'retention'=>$item->retention(),
        'thirdCountryTransfer'=>$item->thirdCountryTransfer(),'automatedDecision'=>$item->automatedDecision(),
        'thirdPartyContentNotice'=>$item->thirdPartyContentNotice(),'attributes'=>$item->attributes(),
    ], $report->entries());
    $appointment = array_values(array_filter($items, static fn(array $item): bool => $item['categoryId'] === 'appointment'))[0] ?? null;
    $shift = array_values(array_filter($items, static fn(array $item): bool => $item['categoryId'] === 'shift'))[0] ?? null;
    $filterPreference = array_values(array_filter($items, static fn(array $item): bool => $item['categoryId'] === 'calendar_filter_preference'))[0] ?? null;
    $shiftDefaults = array_values(array_filter($items, static fn(array $item): bool => $item['categoryId'] === 'shift_defaults'))[0] ?? null;
    $syncPreference = array_values(array_filter($items, static fn(array $item): bool => $item['categoryId'] === 'calendar_sync_preference'))[0] ?? null;
    $externalConnections = array_values(array_filter($items, static fn(array $item): bool => $item['categoryId'] === 'external_calendar_connections'))[0] ?? null;
    if ($appointment === null || $shift === null) throw new RuntimeException('Termin und Dienst sind nicht getrennt ausgewiesen.');
    if (array_key_exists('Art', $appointment['attributes']) || array_key_exists('Art', $shift['attributes'])) throw new RuntimeException('Der bereits als Tabellenabschnitt ausgewiesene Datentyp wird redundant als Art-Spalte ausgegeben.');
    foreach (['Termin', 'Gemeinsamer Termin', '12.08.26, 10:00 bis 11:00 Uhr', 'Termin- und Verfügbarkeitsplanung', 'Keine feste Löschfrist'] as $expected) {
        if (!str_contains(json_encode($appointment, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE), $expected)) throw new RuntimeException("Menschenlesbarer Terminbestand fehlt: {$expected}");
    }
    if (!str_contains((string)$appointment['thirdPartyContentNotice'], 'weitere Personen beteiligt') || str_contains(json_encode($appointment, JSON_THROW_ON_ERROR), 'other-person') || str_contains(json_encode($appointment, JSON_THROW_ON_ERROR), 'Fremder Titel')) {
        throw new RuntimeException('Drittpersonenhinweis nennt fremde Identitäten oder fehlt.');
    }
    foreach (['Dienst', '13.08.26, 08:00 bis 16:00 Uhr', 'Dienstplanung'] as $expected) {
        if (!str_contains(json_encode($shift, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE), $expected)) throw new RuntimeException("Menschenlesbarer Dienstbestand fehlt: {$expected}");
    }
    if ($shift['thirdPartyContentNotice'] !== null) throw new RuntimeException('Ein eigener Dienst behauptet weitere Beteiligte.');
    if ($filterPreference === null || ($filterPreference['attributes']['Ausgewählte Personen'] ?? null) !== 1 || str_contains(json_encode($filterPreference, JSON_THROW_ON_ERROR), 'other-person')) {
        throw new RuntimeException('Der persönliche Filterstandard fehlt oder gibt eine ausgewählte Drittperson preis.');
    }
    if ($shiftDefaults === null || !str_contains(json_encode($shiftDefaults, JSON_THROW_ON_ERROR), '07:30 bis 15:30 Uhr')) throw new RuntimeException('Persönliche Standard-Dienstzeiten fehlen.');
    if ($syncPreference === null || ($syncPreference['attributes']['Privater Nextcloud-Kalender'] ?? null) !== 'deaktiviert') throw new RuntimeException('Die persönliche DAV-Synchronisationseinstellung fehlt.');
    $externalJson = json_encode($externalConnections, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    foreach (['Google', 'Manuelles CalDAV', 'Autorisierungsvorgang vorhanden'] as $expected) if (!str_contains($externalJson, $expected)) throw new RuntimeException("Datensparsame externe Verbindungsmetadaten fehlen: {$expected}");
    foreach (['access-token-secret', 'private.example.test', 'oauth-secret', 'other-person'] as $forbidden) if (str_contains(json_encode($items, JSON_THROW_ON_ERROR), $forbidden)) throw new RuntimeException("Privacy-Auskunft gibt geschützte Verbindungs- oder Drittpersonendaten preis: {$forbidden}");
    if ($crypto->decryptCalls !== 0) throw new RuntimeException('Der Privacy-Provider entschlüsselt externe Zugangsdaten trotz reiner Metadatenauskunft.');
    if ($entries->participantQueries !== 1) throw new RuntimeException('Drittpersonenprüfung wird nicht gebündelt.');
    $limited = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 1, []));
    if ($limited->status() !== 'partial' || !in_array('Ausgabelimit erreicht; weitere Kalenderdaten können vorhanden sein.', $limited->restrictions(), true)) throw new RuntimeException('Begrenzter Kalenderbericht behauptet Vollständigkeit.');
    $unsupported = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-applicant', 'self'), 'de', 'access-report', 50, []));
    if ($unsupported->status() !== 'not_applicable' || $unsupported->entries() !== []) throw new RuntimeException('Ein nicht unterstützter Subject-Typ erhält Kalenderdaten.');
    $userConfig->values['corrupt']['adcalendar']['filter_default'] = '{private-person-value';
    $userConfig->values['corrupt']['adcalendar']['shift_calendar_sync_enabled'] = 'unexpected';
    $corruptReport = $provider->collect(new PersonalDataRequest(new DataSubjectRef('nextcloud-user', 'corrupt'), 'de', 'access-report', 50, []));
    if ($corruptReport->status() !== 'partial' || $corruptReport->entries() !== [] || !str_contains(implode(' ', $corruptReport->restrictions()), 'nicht sicher auswertbar')) {
        throw new RuntimeException('Gespeicherte unlesbare persönliche Kalenderwerte werden fälschlich als nicht vorhanden behandelt.');
    }
    if (str_contains(json_encode($corruptReport->restrictions(), JSON_THROW_ON_ERROR), 'private-person-value')) throw new RuntimeException('Eine sichere Diagnose gibt den unlesbaren persönlichen Rohwert aus.');
    try {
        $provider->collect((new PersonalDataRequest($subject, 'de', 'access-report', 50, ['adcalendar'=>'opaque']))->forProvider('adcalendar', 50));
        throw new RuntimeException('Ein unbekannter Provider-Cursor wurde akzeptiert.');
    } catch (InvalidArgumentException) {}

    $listener = new CalendarPrivacyProviderListener($provider);
    $registry = new RegisterPersonalDataProvidersEvent();
    $listener->handle($registry);
    if (array_keys($registry->providers()) !== ['adcalendar']) throw new RuntimeException('AD Kalender registriert seinen Privacy-Provider nicht.');
    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterPersonalDataProvidersEvent::class, CalendarPrivacyProviderListener::class)') || str_contains($application, 'PersonalDataProviderRegistryEvent')) throw new RuntimeException('AD Kalender registriert den Provider nicht ausschließlich am Standalone-V1-Event.');

    echo "AD Kalender privacy provider test passed\n";
}
