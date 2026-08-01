<?php

declare(strict_types=1);

namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\AdCalendar\CalendarSync {
    final class ExternalCalendarConnectionStore { public array $saved = []; public function connections(string $uid): array { return ['kopano' => ['serverUrl' => 'fail', 'calendarName' => 'AD Dienste'], 'manual' => ['serverUrl' => 'ok', 'calendarName' => 'AD Dienste']]; } public function connection(string $uid, string $provider): ?array { return $this->connections($uid)[$provider] ?? null; } public function save(string $uid, string $provider, array $connection): void { $this->saved[] = [$uid, $provider, $connection]; } }
    final class CalDavClient { public array $renamed = []; public array $published = []; public bool $failRename = false; public bool $failPublish = true; public function renameCalendar(array $connection): array { $this->renamed[] = $connection['serverUrl']; if ($this->failRename && $connection['serverUrl'] === 'fail') throw new \RuntimeException('Umbenennungsfehler an https://secret.example.test/calendar/internal-id'); $connection['calendarName'] = 'Team & Dienst'; return $connection; } public function publish(array $connection, \OCA\AdCalendar\Model\CalendarEntry $shift): void { $this->published[] = [$connection['serverUrl'], $connection['calendarName']]; if ($this->failPublish && $connection['serverUrl'] === 'fail') throw new \RuntimeException('Providerfehler an https://secret.example.test/calendar/internal-id'); } public function replaceAll(array $connection, array $shifts): void {} public function remove(array $connection, int $id): void {} public function removeCalendar(array $connection): void {} }
    final class GoogleCalendarClient { public function renameCalendar(string $uid, array $connection): array { return $connection; } public function publish(string $uid, array $connection, \OCA\AdCalendar\Model\CalendarEntry $shift): void {} public function replaceAll(string $uid, array $connection, array $shifts): void {} public function remove(string $uid, array $connection, int $id): void {} public function removeCalendar(string $uid, array $connection): void {} }
}

namespace {
    require_once __DIR__ . '/../../lib/Model/CalendarEntry.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ShiftCalendarPublisher.php';
    require_once __DIR__ . '/../../lib/CalendarSync/ExternalShiftCalendarPublisher.php';

    use OCA\AdCalendar\CalendarSync\CalDavClient;
    use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionStore;
    use OCA\AdCalendar\CalendarSync\ExternalShiftCalendarPublisher;
    use OCA\AdCalendar\CalendarSync\GoogleCalendarClient;
    use OCA\AdCalendar\Model\CalendarEntry;
    use Psr\Log\LoggerInterface;

    $dav = new CalDavClient();
    $logger = new class implements LoggerInterface { public array $errors = []; public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; } };
    $store = new ExternalCalendarConnectionStore();
    $publisher = new ExternalShiftCalendarPublisher($store, $dav, new GoogleCalendarClient(), $logger);
    $shift = CalendarEntry::get(['id' => 61, 'employeeUid' => 'person-a', 'start' => '2026-07-22T08:00:00+02:00', 'end' => '2026-07-22T16:00:00+02:00', 'type' => CalendarEntry::TYPE_SHIFT, 'title' => '']);
    try { $publisher->publish($shift); throw new RuntimeException('Providerfehler wurde verschluckt.'); }
    catch (RuntimeException $error) { if ($error->getMessage() === 'Providerfehler wurde verschluckt.') throw $error; }
    if ($dav->renamed !== ['fail', 'ok'] || $dav->published !== [['fail', 'Team & Dienst'], ['ok', 'Team & Dienst']]) throw new RuntimeException('Umbenennung oder ein Providerfehler blockiert nachfolgende Verbindungen.');
    if (count($store->saved) !== 2 || ($store->saved[1][2]['calendarName'] ?? '') !== 'Team & Dienst') throw new RuntimeException('Erfolgreiche Providerumbenennung wird nicht für idempotente Wiederholungen gespeichert.');
    $encodedErrors = json_encode($logger->errors, JSON_THROW_ON_ERROR);
    if (count($logger->errors) !== 1
        || str_contains($encodedErrors, 'person-a')
        || str_contains($encodedErrors, 'kopano')
        || str_contains($encodedErrors, 'secret.example.test')
        || isset($logger->errors[0][1]['exception'])) {
        throw new RuntimeException('Providerfehler wird nicht datensparsam protokolliert.');
    }

    $retryDav = new CalDavClient();
    $retryDav->failRename = true;
    $retryDav->failPublish = false;
    $retryStore = new ExternalCalendarConnectionStore();
    $retryLogger = new class implements LoggerInterface { public array $errors = []; public function error(string|\Stringable $message, array $context = []): void { $this->errors[] = [(string)$message, $context]; } };
    $retryPublisher = new ExternalShiftCalendarPublisher($retryStore, $retryDav, new GoogleCalendarClient(), $retryLogger);
    try { $retryPublisher->publish($shift); throw new RuntimeException('Fehlgeschlagene Umbenennung wurde verschluckt.'); }
    catch (RuntimeException $error) { if ($error->getMessage() === 'Fehlgeschlagene Umbenennung wurde verschluckt.') throw $error; }
    if ($retryDav->published !== [['ok', 'Team & Dienst']] || count($retryStore->saved) !== 1) {
        throw new RuntimeException('Fehlgeschlagene Umbenennung blockiert einen anderen Provider oder wird fälschlich als erfolgreich gespeichert.');
    }
    $retryDav->failRename = false;
    $retryPublisher->publish($shift);
    if ($retryDav->published !== [['ok', 'Team & Dienst'], ['fail', 'Team & Dienst'], ['ok', 'Team & Dienst']]
        || count($retryStore->saved) !== 3) {
        throw new RuntimeException('Ausstehende Providerumbenennung wird beim nächsten Abgleich nicht erfolgreich wiederholt.');
    }

    echo "ExternalShiftCalendarPublisherTest: OK\n";
}
