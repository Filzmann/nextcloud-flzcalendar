<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Model;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Zweck: Validiert eine begrenzte Terminserie und erzeugt ihre lokalen Startzeitpunkte.
 * Vertrag: Täglich, wöchentlich oder monatlich; mindestens zwei und höchstens 500 Vorkommen.
 */
final class RecurrenceRule {
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    private const MAX_OCCURRENCES = 500;

    private function __construct(
        private readonly string $frequency,
        private readonly int $interval,
        private readonly string $until,
        private readonly array $weekdays,
        private readonly DateTimeZone $timezone,
    ) {}

    public static function get(array $payload, DateTimeImmutable $start): self {
        $frequency = trim((string)($payload['frequency'] ?? ''));
        if (!in_array($frequency, [self::FREQUENCY_DAILY, self::FREQUENCY_WEEKLY, self::FREQUENCY_MONTHLY], true)) {
            throw new InvalidArgumentException('Unbekannte Wiederholungsart.');
        }

        $interval = filter_var($payload['interval'] ?? null, FILTER_VALIDATE_INT);
        if ($interval === false || $interval < 1 || $interval > 365) {
            throw new InvalidArgumentException('Das Wiederholungsintervall muss zwischen 1 und 365 liegen.');
        }

        $timezoneName = trim((string)($payload['timezone'] ?? ''));
        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Die Zeitzone der Terminserie ist ungültig.');
        }

        $until = trim((string)($payload['until'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $until) !== 1) {
            throw new InvalidArgumentException('Die Terminserie benötigt ein gültiges Enddatum.');
        }
        $limit = new DateTimeImmutable($until . ' 23:59:59', $timezone);
        if ($limit->format('Y-m-d') !== $until || $limit < $start->setTimezone($timezone)) {
            throw new InvalidArgumentException('Das Serienende darf nicht vor dem ersten Termin liegen.');
        }

        $weekdays = [];
        foreach ((array)($payload['weekdays'] ?? []) as $weekday) {
            $value = filter_var($weekday, FILTER_VALIDATE_INT);
            if ($value === false || $value < 1 || $value > 7) {
                throw new InvalidArgumentException('Wochentage müssen zwischen Montag und Sonntag liegen.');
            }
            $weekdays[] = $value;
        }
        $weekdays = array_values(array_unique($weekdays));
        sort($weekdays);
        if ($frequency === self::FREQUENCY_WEEKLY && $weekdays === []) {
            throw new InvalidArgumentException('Eine wöchentliche Serie benötigt mindestens einen Wochentag.');
        }

        return new self($frequency, $interval, $until, $weekdays, $timezone);
    }

    /** @return list<DateTimeImmutable> */
    public function starts(DateTimeImmutable $start): array {
        $localStart = $start->setTimezone($this->timezone);
        $limit = new DateTimeImmutable($this->until . ' 23:59:59', $this->timezone);
        $starts = match ($this->frequency) {
            self::FREQUENCY_DAILY => $this->daily($localStart, $limit),
            self::FREQUENCY_WEEKLY => $this->weekly($localStart, $limit),
            self::FREQUENCY_MONTHLY => $this->monthly($localStart, $limit),
        };
        if (count($starts) < 2) {
            throw new InvalidArgumentException('Eine Terminserie muss mindestens zwei Vorkommen enthalten.');
        }
        return $starts;
    }

    public function timezone(): DateTimeZone { return $this->timezone; }
    public function timezoneName(): string { return $this->timezone->getName(); }

    /** @return list<DateTimeImmutable> */
    private function daily(DateTimeImmutable $start, DateTimeImmutable $limit): array {
        $result = [];
        for ($candidate = $start; $candidate <= $limit; $candidate = $candidate->modify('+' . $this->interval . ' days')) {
            $this->append($result, $candidate);
        }
        return $result;
    }

    /** @return list<DateTimeImmutable> */
    private function weekly(DateTimeImmutable $start, DateTimeImmutable $limit): array {
        $result = [];
        $week = $start->modify('monday this week')->setTime(
            (int)$start->format('H'), (int)$start->format('i'), (int)$start->format('s'),
        );
        while ($week <= $limit) {
            foreach ($this->weekdays as $weekday) {
                $candidate = $week->modify('+' . ($weekday - 1) . ' days');
                if ($candidate >= $start && $candidate <= $limit) $this->append($result, $candidate);
            }
            $week = $week->modify('+' . $this->interval . ' weeks');
        }
        return $result;
    }

    /** @return list<DateTimeImmutable> */
    private function monthly(DateTimeImmutable $start, DateTimeImmutable $limit): array {
        $result = [];
        $day = (int)$start->format('d');
        $month = $start->modify('first day of this month')->setTime(
            (int)$start->format('H'), (int)$start->format('i'), (int)$start->format('s'),
        );
        while ($month <= $limit) {
            $year = (int)$month->format('Y');
            $number = (int)$month->format('m');
            if (checkdate($number, $day, $year)) {
                $candidate = $month->setDate($year, $number, $day);
                if ($candidate >= $start && $candidate <= $limit) $this->append($result, $candidate);
            }
            $month = $month->modify('+' . $this->interval . ' months');
        }
        return $result;
    }

    private function append(array &$result, DateTimeImmutable $candidate): void {
        $result[] = $candidate;
        if (count($result) > self::MAX_OCCURRENCES) {
            throw new InvalidArgumentException('Eine Terminserie darf höchstens 500 Vorkommen enthalten.');
        }
    }
}
