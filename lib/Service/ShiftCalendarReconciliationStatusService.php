<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Service;

use OCA\FlzCalendar\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;

/**
 * Zweck: Persistiert ausschließlich den aggregierten Zustand des letzten DAV-Abgleichs für die Admin-Diagnose.
 * Vertrag: Keine Konto-, Kalender- oder Fehlerdetails speichern; die Richtung bleibt für eine spätere bidirektionale Erweiterung explizit.
 */
final class ShiftCalendarReconciliationStatusService {
    private const KEY = 'shift_calendar_reconciliation_status';

    public function __construct(private IAppConfig $config, private ITimeFactory $time) {}

    public function record(array $result): void {
        $payload = [
            'schemaVersion' => 1,
            'direction' => 'outbound',
            'lastRunAt' => $this->time->getTime(),
            'attempted' => max(0, (int)($result['attempted'] ?? 0)),
            'succeeded' => max(0, (int)($result['succeeded'] ?? 0)),
            'failed' => max(0, (int)($result['failed'] ?? 0)),
        ];
        $this->config->setValueString(
            Application::APP_ID,
            self::KEY,
            json_encode($payload, JSON_THROW_ON_ERROR),
            true,
        );
    }

    /** @return array{hasRun: bool, lastRunAt: int, attempted: int, succeeded: int, failed: int, state: string} */
    public function status(): array {
        try {
            $raw = $this->config->getValueString(Application::APP_ID, self::KEY, '', true);
            if ($raw === '') return $this->pending();
            $payload = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($payload) || ($payload['schemaVersion'] ?? null) !== 1 || ($payload['direction'] ?? null) !== 'outbound') return $this->pending();
            $lastRunAt = max(0, (int)($payload['lastRunAt'] ?? 0));
            if ($lastRunAt === 0) return $this->pending();
            $failed = max(0, (int)($payload['failed'] ?? 0));
            return [
                'hasRun' => true,
                'lastRunAt' => $lastRunAt,
                'attempted' => max(0, (int)($payload['attempted'] ?? 0)),
                'succeeded' => max(0, (int)($payload['succeeded'] ?? 0)),
                'failed' => $failed,
                'state' => $failed > 0 ? 'warning' : 'success',
            ];
        } catch (\Throwable) {
            return $this->pending();
        }
    }

    /** @return array{hasRun: bool, lastRunAt: int, attempted: int, succeeded: int, failed: int, state: string} */
    private function pending(): array {
        return ['hasRun' => false, 'lastRunAt' => 0, 'attempted' => 0, 'succeeded' => 0, 'failed' => 0, 'state' => 'pending'];
    }
}
