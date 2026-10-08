<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Controller;

use OCA\FlzCalendar\AppInfo\Application;
use OCA\FlzCalendar\Service\CalendarTargetConfig;
use OCA\FlzCalendar\Service\TemporaryAdminAccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {
    public function __construct(IRequest $request, private CalendarTargetConfig $calendarTargets, private TemporaryAdminAccessService $adminAccess) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse {
        $canManageAdminAccess = $this->adminAccess->canManageGrants();
        $showMissingAdminGrant = $this->adminAccess->currentAdminNeedsGrant();
        return new TemplateResponse(Application::APP_ID, 'index', [
            'calendarDefaults' => $this->calendarTargets->adminStatus(),
            'canManageAdminAccess' => $canManageAdminAccess,
            'showMissingAdminGrant' => $showMissingAdminGrant,
            'showAdminAccessLink' => $canManageAdminAccess && $showMissingAdminGrant,
        ]);
    }
}
