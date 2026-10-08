<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Model;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Zweck: Repraesentiert einen Dienst oder Termin unabhaengig von Persistenz und UI.
 * Vertrag: Ende liegt nach Beginn; Termine benoetigen einen Titel.
 */
final class CalendarEntry {
    public const TYPE_SHIFT = 'shift';
    public const TYPE_APPOINTMENT = 'appointment';

    private function __construct(
        private readonly ?int $id,
        private readonly string $employeeUid,
        private readonly DateTimeImmutable $start,
        private readonly DateTimeImmutable $end,
        private readonly string $type,
        private readonly string $title,
        private readonly ?int $parentEntryId,
        private readonly ?string $meetingUid,
        private readonly ?string $seriesUid,
        private readonly ?string $seriesTimezone,
        private readonly ?string $defaultDate,
        private readonly bool $defaultModified,
        private readonly bool $defaultDeleted,
    ) {}

    public static function get(array $payload): self {
        $type = (string)($payload['type'] ?? '');
        $title = trim((string)($payload['title'] ?? ''));
        $employeeUid = trim((string)($payload['employeeUid'] ?? ''));
        $start = self::date($payload['start'] ?? null, 'start');
        $end = self::date($payload['end'] ?? null, 'end');

        if (!in_array($type, [self::TYPE_SHIFT, self::TYPE_APPOINTMENT], true)) {
            throw new InvalidArgumentException('Unbekannter Kalendereintragstyp.');
        }
        if ($employeeUid === '') {
            throw new InvalidArgumentException('Eine Mitarbeiter*innen-ID ist erforderlich.');
        }
        if ($end <= $start) {
            throw new InvalidArgumentException('Das Ende muss nach dem Beginn liegen.');
        }
        if ($type === self::TYPE_APPOINTMENT && $title === '') {
            throw new InvalidArgumentException('Ein Termin benötigt einen Titel.');
        }

        $parentEntryId = isset($payload['parentEntryId']) && $payload['parentEntryId'] !== null ? (int)$payload['parentEntryId'] : null;
        if ($type === self::TYPE_SHIFT && $parentEntryId !== null) {
            throw new InvalidArgumentException('Ein Dienst darf keinen übergeordneten Eintrag haben.');
        }
        $meetingUid = isset($payload['meetingUid']) && $payload['meetingUid'] !== null ? trim((string)$payload['meetingUid']) : null;
        if ($meetingUid === '') $meetingUid = null;
        if ($meetingUid !== null && ($type !== self::TYPE_APPOINTMENT || preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $meetingUid) !== 1)) {
            throw new InvalidArgumentException('Eine Meeting-Referenz benötigt einen Termin und eine gültige Kennung.');
        }
        $seriesUid = isset($payload['seriesUid']) && $payload['seriesUid'] !== null ? trim((string)$payload['seriesUid']) : null;
        if ($seriesUid === '') $seriesUid = null;
        $seriesTimezone = isset($payload['seriesTimezone']) && $payload['seriesTimezone'] !== null ? trim((string)$payload['seriesTimezone']) : null;
        if ($seriesTimezone === '') $seriesTimezone = null;
        if (($seriesUid === null) !== ($seriesTimezone === null)
            || ($seriesUid !== null && ($type !== self::TYPE_APPOINTMENT || $meetingUid !== null || preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $seriesUid) !== 1))) {
            throw new InvalidArgumentException('Eine Serienreferenz benötigt einen einzelnen Termin, eine gültige Kennung und eine Zeitzone.');
        }
        if ($seriesTimezone !== null) {
            try {
                new DateTimeZone($seriesTimezone);
            } catch (\Throwable) {
                throw new InvalidArgumentException('Die Zeitzone der Terminserie ist ungültig.');
            }
        }
        $defaultDate = isset($payload['defaultDate']) && $payload['defaultDate'] !== null ? (string)$payload['defaultDate'] : null;
        if ($defaultDate !== null && ($type !== self::TYPE_SHIFT || preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultDate) !== 1)) {
            throw new InvalidArgumentException('Eine Standarddienst-Referenz benötigt einen Dienst und ein gültiges Datum.');
        }
        return new self(
            isset($payload['id']) ? (int)$payload['id'] : null,
            $employeeUid,
            $start,
            $end,
            $type,
            $title,
            $parentEntryId,
            $meetingUid,
            $seriesUid,
            $seriesTimezone,
            $defaultDate,
            filter_var($payload['defaultModified'] ?? false, FILTER_VALIDATE_BOOL),
            filter_var($payload['defaultDeleted'] ?? false, FILTER_VALIDATE_BOOL),
        );
    }

    /** @return list<self> */
    public static function get_all(array $payloads): array {
        return array_map(static fn(array $payload): self => self::get($payload), $payloads);
    }

    public function isWithin(self $shift): bool {
        return $shift->type === self::TYPE_SHIFT
            && $this->employeeUid === $shift->employeeUid
            && $this->start >= $shift->start
            && $this->end <= $shift->end;
    }

    public function overlaps(self $other): bool {
        return $this->employeeUid === $other->employeeUid
            && $this->start < $other->end
            && $this->end > $other->start;
    }

    public function durationMinutes(): int {
        return (int)(($this->end->getTimestamp() - $this->start->getTimestamp()) / 60);
    }

    public function durationWithin(DateTimeImmutable $rangeStart, DateTimeImmutable $rangeEnd): int {
        $start = max($this->start->getTimestamp(), $rangeStart->getTimestamp());
        $end = min($this->end->getTimestamp(), $rangeEnd->getTimestamp());
        return max(0, (int)(($end - $start) / 60));
    }

    public function id(): ?int { return $this->id; }
    public function employeeUid(): string { return $this->employeeUid; }
    public function start(): DateTimeImmutable { return $this->start; }
    public function end(): DateTimeImmutable { return $this->end; }
    public function type(): string { return $this->type; }
    public function title(): string { return $this->title; }
    public function parentEntryId(): ?int { return $this->parentEntryId; }
    public function meetingUid(): ?string { return $this->meetingUid; }
    public function seriesUid(): ?string { return $this->seriesUid; }
    public function seriesTimezone(): ?string { return $this->seriesTimezone; }
    public function defaultDate(): ?string { return $this->defaultDate; }
    public function defaultModified(): bool { return $this->defaultModified; }
    public function defaultDeleted(): bool { return $this->defaultDeleted; }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'employeeUid' => $this->employeeUid,
            'start' => $this->start->format(DATE_ATOM),
            'end' => $this->end->format(DATE_ATOM),
            'type' => $this->type,
            'title' => $this->title,
            'parentEntryId' => $this->parentEntryId,
            'meetingUid' => $this->meetingUid,
            'seriesUid' => $this->seriesUid,
            'seriesTimezone' => $this->seriesTimezone,
            'defaultDate' => $this->defaultDate,
            'defaultModified' => $this->defaultModified,
            'defaultDeleted' => $this->defaultDeleted,
        ];
    }

    private static function date(mixed $value, string $field): DateTimeImmutable {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Das Feld {$field} ist erforderlich.");
        }
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new InvalidArgumentException("Das Feld {$field} enthält kein gültiges Datum.");
        }
    }
}
