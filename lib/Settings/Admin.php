<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Settings;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
use OCA\AdCalendar\Service\ShiftCalendarReconciliationStatusService;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\Settings\ISettings;

/** Zweck: Bindet app-spezifische Kalenderadministration in den Nextcloud-Adminbereich ein. */
final class Admin implements ISettings {
    public function __construct(
        private ShiftCalendarReconciliationStatusService $calendarSyncStatus,
        private IDateTimeFormatter $dateTimeFormatter,
        private GoogleOAuthService $googleOAuth,
        private CalendarTargetConfig $calendarTargets,
        private IL10N $l10n,
    ) {}

    public function getForm(): TemplateResponse {
        $status = $this->calendarSyncStatus->status();
        $status['lastRunLabel'] = $status['hasRun'] ? $this->dateTimeFormatter->formatDateTime($status['lastRunAt']) : $this->l10n->t('No background run recorded');
        return new TemplateResponse(Application::APP_ID, 'admin', [
            'calendarSyncStatus' => $status,
            'googleOAuth' => $this->googleOAuth->adminStatus(),
            'calendarDefaults' => $this->calendarTargets->adminStatus(),
        ]);
    }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 30; }
}
