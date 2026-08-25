<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
use OCA\AdCalendar\Service\CalendarDemoPackService;
use OCA\AdCalendar\Service\TemporaryAdminAccessChecker;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Zweck: Installiert das Kalender-Demo-Pack ausschließlich nach einer bestätigten Adminaktion.
 * Vertrag: Nextcloud-Admin- und CSRF-Schutz bleiben aktiv; zusätzlich wird die aktive Sitzung serverseitig geprüft.
 */
final class DemoAdminController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private IGroupManager $groups,
        private CalendarDemoPackService $demoPack,
        private LoggerInterface $logger,
        private LocalizedErrorResponseFactory $errors,
        private TemporaryAdminAccessChecker $temporaryAdminAccess,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function install(): JSONResponse {
        if (!$this->isAdmin()) return $this->errors->create('forbidden', 'You are not allowed to perform this action.', Http::STATUS_FORBIDDEN);
        try {
            return new JSONResponse(['result' => $this->demoPack->install()]);
        } catch (\Throwable $error) {
            $this->logger->error('Kalender-Demo-Pack konnte nicht installiert werden.', ['exception' => $error]);
            return $this->errors->create('demo_pack_install_failed', 'The calendar demo pack could not be installed.', Http::STATUS_BAD_REQUEST);
        }
    }

    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null
            && $this->groups->isAdmin($user->getUID())
            && $this->temporaryAdminAccess->hasActiveGrant($user->getUID());
    }
}
