<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    if (!class_exists(Event::class)) { class Event { public function __construct() {} } }
    if (!interface_exists(IEventDispatcher::class)) { interface IEventDispatcher { public function dispatchTyped(Event $event): Event; } }
}
namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) { interface LoggerInterface { public function error(string|\Stringable $message, array $context = []): void; } }
}

namespace {
    use OCA\FlzCalendar\Service\PlanningConflictService;
    use OCA\LocalBase\Calendar\ScheduleConflict;
    use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
    use OCP\EventDispatcher\Event;
    use OCP\EventDispatcher\IEventDispatcher;
    use Psr\Log\LoggerInterface;

    $events = new class implements IEventDispatcher {
        public bool $fail = false;
        public bool $provide = true;
        public array $requesters = [];
        public function dispatchTyped(Event $event): Event {
            if (!$event instanceof ScheduleConflictQueryEvent) return $event;
            $this->requesters[] = $event->requesterAppId();
            if ($this->fail) throw new RuntimeException('Providerfehler');
            if ($this->provide) {
                $event->add(new ScheduleConflict(
                    'shift',
                    new DateTimeImmutable('2026-08-10T08:00:00Z'),
                    new DateTimeImmutable('2026-08-10T16:00:00Z'),
                    'Assistenz',
                    'flzplaner',
                ));
            }
            return $event;
        }
    };
    $logger = new class implements LoggerInterface {
        public array $errors = [];
        public function error(string|Stringable $message, array $context = []): void { $this->errors[] = [$message, $context]; }
    };
    $service = new PlanningConflictService($events, $logger);
    $start = new DateTimeImmutable('2026-08-10T09:00:00Z');
    $end = new DateTimeImmutable('2026-08-10T17:00:00Z');

    $result = $service->queryRange($start, $end, ['person-a']);
    if (($result['status'] ?? null) !== 'available'
        || ($result['conflicts'][0]['employeeUid'] ?? null) !== 'person-a'
        || ($result['conflicts'][0]['label'] ?? null) !== 'Assistenz'
        || ($result['conflicts'][0]['sourceAppId'] ?? null) !== 'flzplaner') {
        throw new RuntimeException('Assistenzkonflikte werden nicht als datensparsamer Kalendervertrag projiziert.');
    }
    if ($events->requesters !== ['flzcalendar']) throw new RuntimeException('Der Consumer grenzt Eigenmeldungen nicht über die App-ID aus.');

    try {
        $service->assertShiftWritable('person-a', $start, $end);
        throw new RuntimeException('Ein überlappender Assistenzdienst wurde akzeptiert.');
    } catch (InvalidArgumentException $error) {
        if (!str_contains($error->getMessage(), 'Assistenz')) throw $error;
    }
    if ($service->checkShift('person-a', $start, $end) !== ['status' => 'available', 'blocked' => true]) {
        throw new RuntimeException('Standarddienst wird trotz Assistenzkonflikt materialisiert.');
    }
    if ($service->checkShift(
        'person-a',
        new DateTimeImmutable('2026-08-10T16:00:00Z'),
        new DateTimeImmutable('2026-08-10T17:00:00Z'),
    ) !== ['status' => 'available', 'blocked' => false]) {
        throw new RuntimeException('Eine direkte Übergabe ohne echte Überlappung wird fälschlich blockiert.');
    }
    if ($service->checkShift(
        'person-a',
        new DateTimeImmutable('2026-08-10T15:50:00Z'),
        new DateTimeImmutable('2026-08-10T16:10:00Z'),
    ) !== ['status' => 'available', 'blocked' => true]) {
        throw new RuntimeException('Eine zehnminütige echte Überlappung wird nicht als Konflikt erkannt.');
    }

    $events->provide = false;
    if ($service->checkShift('person-a', $start, $end) !== ['status' => 'available', 'blocked' => false]) {
        throw new RuntimeException('Ein fehlender optionaler Provider blockiert den Standalone-Kalender.');
    }

    $events->fail = true;
    if ($service->checkShift('person-a', $start, $end) !== ['status' => 'unavailable', 'blocked' => false]) {
        throw new RuntimeException('Providerfehler wird für automatische Dienste fälschlich als konfliktfrei gewertet.');
    }
    $failed = $service->queryRange($start, $end, ['person-a']);
    if (($failed['status'] ?? null) !== 'unavailable' || ($failed['conflicts'] ?? null) !== [] || $logger->errors === []) {
        throw new RuntimeException('Ein Providerfehler wird lesend nicht sichtbar und datensparsam isoliert.');
    }
    try {
        $service->assertShiftWritable('person-a', $start, $end);
        throw new RuntimeException('Manueller Dienst wurde trotz unbekannter Konfliktlage gespeichert.');
    } catch (InvalidArgumentException $error) {
        if (!str_contains($error->getMessage(), 'geprüft')) throw $error;
    }

    echo "PlanningConflictServiceTest: OK\n";
}
