<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
    public function __construct(IRequest $request, private CalendarTargetConfig $calendarTargets) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'index', ['calendarDefaults' => $this->calendarTargets->adminStatus()]);
    }
}
