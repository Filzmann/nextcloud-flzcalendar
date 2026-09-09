<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace {
    require_once dirname(__DIR__) . '/bootstrap.php';

    use OCA\AdCalendar\Privacy\CalendarProcessingMetadataProvider;
    use OCA\AdCalendar\Privacy\CalendarProcessingMetadataProviderListener;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
    use OCP\EventDispatcher\Event;

    $provider = new CalendarProcessingMetadataProvider();
    $catalog = $provider->catalog();
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'adcalendar' || $descriptor->displayName() !== 'AD Kalender' || $descriptor->contractVersion() !== '1.0') {
        throw new RuntimeException('Der Processing-Metadata-Provider beschreibt AD Kalender nicht korrekt.');
    }
    if ($catalog->appId() !== 'adcalendar') {
        throw new RuntimeException('Processing-Metadata-Provider und Katalog verwenden nicht die kanonische App-ID.');
    }
    if ($catalog->processingIds() !== [
        'calendar_entry_management',
        'personal_calendar_preferences',
        'external_calendar_connections',
        'derived_calendar_publication',
        'temporary_admin_full_access',
    ]) {
        throw new RuntimeException('Der app-lokale Processing-Katalog ist unvollständig.');
    }
    if (array_key_exists('personal_runtime_data', $catalog->toArray())) {
        throw new RuntimeException('Der Processing-Katalog enthält personenbezogene Laufzeitdaten.');
    }

    $registration = new RegisterProcessingMetadataProvidersEvent();
    $listener = new CalendarProcessingMetadataProviderListener($provider);
    $listener->handle(new Event());
    if ($registration->providers() !== []) {
        throw new RuntimeException('Ein fremdes Event registriert den Processing-Metadata-Provider.');
    }
    $listener->handle($registration);
    if (($registration->providers()['adcalendar'] ?? null) !== $provider) {
        throw new RuntimeException('Der Processing-Metadata-Provider wird nicht lazy registriert.');
    }

    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterProcessingMetadataProvidersEvent::class, CalendarProcessingMetadataProviderListener::class)')) {
        throw new RuntimeException('Der Bootstrap registriert den Processing-Metadata-Provider nicht am öffentlichen V1-Event.');
    }

    echo "AD Kalender processing metadata provider test passed\n";
}
