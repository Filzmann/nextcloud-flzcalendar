<?php

declare(strict_types=1);

namespace OCP {
    interface IRequest {}
    interface IL10N { public function t(string $text, array $parameters = []): string; }
    interface IUser { public function getUID(): string; }
}
namespace OCP\AppFramework {
    class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }
    class Http {
        public const STATUS_BAD_REQUEST = 400;
        public const STATUS_CREATED = 201;
        public const STATUS_FORBIDDEN = 403;
        public const STATUS_NOT_FOUND = 404;
        public const STATUS_CONFLICT = 409;
    }
}
namespace OCP\AppFramework\Http {
    class JSONResponse {
        public function __construct(private array $data = [], private int $status = 200) {}
        public function getData(): array { return $this->data; }
        public function getStatus(): int { return $this->status; }
    }
}
namespace Psr\Log { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\AdCalendar\Service {
    use OCP\IUser;
    final class CalendarAccessService {
        public bool $view = false;
        public array $manageable = [];
        public array $visible = [['uid' => 'a'], ['uid' => 'b'], ['uid' => 'c']];
        public ?IUser $user = null;
        public function canView(): bool { return $this->view; }
        public function canManage(string $uid): bool { return in_array($uid, $this->manageable, true); }
        public function visibleEmployees(): array { return $this->visible; }
        public function currentUser(): ?IUser { return $this->user; }
    }
    final class MeetingService {
        public array $calls = [];
        public array $meetingEntries = [];
        public ?\Throwable $failure = null;
        private function fail(): void { if ($this->failure !== null) throw $this->failure; }
        public function gaps(\DateTimeImmutable $start, array $uids, int $duration): array { $this->fail(); $this->calls[] = ['gaps', $start, $uids, $duration]; return [['start' => $start->format(DATE_ATOM)]]; }
        public function block(\DateTimeImmutable $start, \DateTimeImmutable $end, array $uids, string $title, string $actor): array { $this->fail(); $this->calls[] = ['block', $uids, $title, $actor]; return [11, 12]; }
        public function entries(string $uid): array { $this->fail(); $this->calls[] = ['entries', $uid]; return $this->meetingEntries; }
        public function update(string $uid, \DateTimeImmutable $start, \DateTimeImmutable $end, string $title, string $actor): array { $this->fail(); $this->calls[] = ['update', $uid, $title, $actor]; return [21, 22]; }
        public function delete(string $uid): void { $this->fail(); $this->calls[] = ['delete', $uid]; }
    }
}

namespace {

    use OCA\AdCalendar\Controller\MeetingController;
    use OCA\AdCalendar\Exception\MeetingSlotUnavailableException;
    use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
    use OCA\AdCalendar\Service\CalendarAccessService;
    use OCA\AdCalendar\Service\MeetingService;
    use OCP\IL10N;
    use OCP\IRequest;
    use OCP\IUser;
    use Psr\Log\LoggerInterface;

    $access = new CalendarAccessService();
    $meetings = new MeetingService();
    $logger = new class implements LoggerInterface {
        public array $entries = [];
        public function error(string|\Stringable $message, array $context = []): void { $this->entries[] = [(string)$message, $context]; }
    };
    $l10n = new class implements IL10N { public function t(string $text, array $parameters = []): string { return strtr($text, $parameters); } };
    $controller = new MeetingController(new class implements IRequest {}, $access, $meetings, $logger, new LocalizedErrorResponseFactory($l10n));

    if ($controller->gaps('2026-07-06', ['a', 'b'])->getStatus() !== 403
        || $controller->block('2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', ['a', 'b'], 'Planning')->getStatus() !== 403
        || $controller->update('meeting-a', '2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', 'Planning')->getStatus() !== 403
        || $controller->delete('meeting-a')->getStatus() !== 403) {
        throw new RuntimeException('Anonyme Meetingzugriffe werden nicht einheitlich verweigert.');
    }

    $access->view = true;
    $access->user = new class implements IUser { public function getUID(): string { return 'planner'; } };
    foreach ([
        [['a'], 60],
        [['a', 'b'], 10],
        [['a', 'unknown'], 60],
        [array_map(static fn(int $number): string => "u{$number}", range(1, 21)), 60],
    ] as [$uids, $duration]) {
        $response = $controller->gaps('2026-07-06', $uids, $duration);
        if ($response->getStatus() !== 400 || ($response->getData()['code'] ?? '') !== 'invalid_meeting_search') {
            throw new RuntimeException('Ungültige Meeting-Suche wird nicht stabil abgewiesen.');
        }
    }
    $access->manageable = ['a'];
    $gaps = $controller->gaps('2026-07-06', ['a', 'a', 'b'], 60);
    if ($gaps->getStatus() !== 200 || ($gaps->getData()['canBlockAll'] ?? true) !== false
        || count($gaps->getData()['gaps'] ?? []) !== 1 || $meetings->calls[0][2] !== ['a', 'b']) {
        throw new RuntimeException('Meeting-Suche dedupliziert Personen oder gemeinsames Bearbeitungsrecht nicht.');
    }
    $access->manageable = ['a', 'b'];
    if (($controller->gaps('2026-07-06', ['a', 'b'], 60)->getData()['canBlockAll'] ?? false) !== true) {
        throw new RuntimeException('Vollständig bearbeitbare Meeting-Suche bleibt gesperrt.');
    }

    $access->manageable = ['a'];
    if ($controller->block('2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', ['a', 'b'], 'Planning')->getStatus() !== 403) {
        throw new RuntimeException('Teilweise bearbeitbares Meeting wird angelegt.');
    }
    $access->manageable = ['a', 'b'];
    $created = $controller->block('2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', ['a', 'b'], 'Planning');
    if ($created->getStatus() !== 201 || $created->getData()['ids'] !== [11, 12] || $meetings->calls[2][3] !== 'planner') {
        throw new RuntimeException('Berechtigtes Meeting wird nicht atomar mit Akteur angelegt.');
    }
    $blockFailures = [
        [new MeetingSlotUnavailableException('occupied'), 'meeting_slot_unavailable', 409],
        [new InvalidArgumentException('invalid'), 'invalid_meeting', 400],
        [new RuntimeException('provider'), 'meeting_block_failed', 400],
    ];
    foreach ($blockFailures as [$failure, $code, $status]) {
        $meetings->failure = $failure;
        $response = $controller->block('2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', ['a', 'b'], 'Planning');
        if ($response->getStatus() !== $status || ($response->getData()['code'] ?? '') !== $code) throw new RuntimeException("Blockfehler {$code} ist instabil.");
    }

    $meetings->failure = null;
    if ($controller->update('missing', '2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', 'Updated')->getStatus() !== 404
        || $controller->delete('missing')->getStatus() !== 404) {
        throw new RuntimeException('Fehlendes Meeting wird nicht als nicht gefunden gemeldet.');
    }
    $entry = static fn(string $uid): object => new class($uid) { public function __construct(private string $uid) {} public function employeeUid(): string { return $this->uid; } };
    $meetings->meetingEntries = [$entry('a'), $entry('b')];
    $access->manageable = ['a'];
    if ($controller->update('meeting-a', '2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', 'Updated')->getStatus() !== 403
        || $controller->delete('meeting-a')->getStatus() !== 403) {
        throw new RuntimeException('Meetingänderung mit Teilrecht wird nicht verweigert.');
    }
    $access->manageable = ['a', 'b'];
    $updated = $controller->update('meeting-a', '2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', 'Updated');
    $deleted = $controller->delete('meeting-a');
    if ($updated->getData()['ids'] !== [21, 22] || $deleted->getData() !== ['deleted' => true]) {
        throw new RuntimeException('Berechtigtes Meeting wird nicht gemeinsam geändert und gelöscht.');
    }
    foreach ([
        [new MeetingSlotUnavailableException('occupied'), 'meeting_slot_unavailable', 409],
        [new InvalidArgumentException('invalid'), 'invalid_meeting', 400],
        [new RuntimeException('provider'), 'meeting_update_failed', 400],
    ] as [$failure, $code, $status]) {
        $meetings->failure = $failure;
        $response = $controller->update('meeting-a', '2026-07-06T10:00:00Z', '2026-07-06T11:00:00Z', 'Updated');
        if ($response->getStatus() !== $status || ($response->getData()['code'] ?? '') !== $code) throw new RuntimeException("Updatefehler {$code} ist instabil.");
    }
    foreach ([
        [new InvalidArgumentException('invalid'), 'invalid_meeting'],
        [new RuntimeException('provider'), 'meeting_delete_failed'],
    ] as [$failure, $code]) {
        $meetings->failure = $failure;
        $response = $controller->delete('meeting-a');
        if ($response->getStatus() !== 400 || ($response->getData()['code'] ?? '') !== $code) throw new RuntimeException("Löschfehler {$code} ist instabil.");
    }
    if (count($logger->entries) !== 3 || array_filter($logger->entries, static fn(array $entry): bool => !isset($entry[1]['exception'])) !== []) {
        throw new RuntimeException('Unerwartete Meetingfehler werden nicht sicher und strukturiert protokolliert.');
    }

    echo "MeetingControllerExecutionTest: OK\n";
}
