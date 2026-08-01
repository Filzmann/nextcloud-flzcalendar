<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Settings;

use OCA\AdCalendar\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/** Zweck: Registriert den nur für Nextcloud-Admins sichtbaren Kalenderabschnitt. */
final class AdminSection implements IIconSection {
    public function __construct(private IURLGenerator $url, private IL10N $l10n) {}
    public function getIcon(): string { return $this->url->imagePath(Application::APP_ID, 'app.svg'); }
    public function getID(): string { return Application::APP_ID; }
    public function getName(): string { return $this->l10n->t('AD Calendar'); }
    public function getPriority(): int { return 62; }
}
