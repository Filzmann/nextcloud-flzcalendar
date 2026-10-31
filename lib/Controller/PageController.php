<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Controller;

use OCA\FlzCalendar\AppInfo\Application;
use OCA\FlzCalendar\Service\CalendarAccessService;
use OCA\FlzCalendar\Service\CalendarTargetConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
    public function __construct(IRequest $request, private CalendarTargetConfig $calendarTargets, private CalendarAccessService $access) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse {
        if (!$this->access->canView()) return new TemplateResponse('core', '403', [], 'guest', Http::STATUS_FORBIDDEN);
        return new TemplateResponse(Application::APP_ID, 'index', [
            'calendarDefaults' => $this->calendarTargets->adminStatus(),
        ]);
    }
}
