<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Controller;

use OCA\FlzCalendar\AppInfo\Application;
use OCA\FlzCalendar\CalendarSync\GoogleOAuthService;
use OCA\FlzCalendar\Http\LocalizedErrorResponseFactory;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** Speichert globale Google-OAuth-Daten ausschließlich für bestätigte Nextcloud-Admins. */
final class GoogleOAuthAdminController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private IGroupManager $groups,
        private GoogleOAuthService $oauth,
        private LoggerInterface $logger,
        private LocalizedErrorResponseFactory $errors,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function save(string $clientId, string $clientSecret = ''): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        try {
            return new JSONResponse(['googleOAuth' => $this->oauth->saveConfiguration($clientId, $clientSecret)]);
        } catch (\InvalidArgumentException) {
            return $this->errors->create('invalid_google_oauth_configuration', 'The Google OAuth configuration is invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Google-OAuth-Konfiguration konnte nicht gespeichert werden.', ['exception' => $error]);
            return $this->errors->create('google_oauth_save_failed', 'The Google OAuth configuration could not be saved.', Http::STATUS_BAD_REQUEST);
        }
    }

    public function remove(): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        try {
            return new JSONResponse(['googleOAuth' => $this->oauth->removeConfiguration()]);
        } catch (\Throwable $error) {
            $this->logger->error('Google-OAuth-Konfiguration konnte nicht entfernt werden.', ['exception' => $error]);
            return $this->errors->create('google_oauth_remove_failed', 'The Google OAuth configuration could not be removed.', Http::STATUS_BAD_REQUEST);
        }
    }

    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null && $this->groups->isAdmin($user->getUID());
    }

    private function denied(): JSONResponse {
        return $this->errors->create('forbidden', 'You are not allowed to perform this action.', Http::STATUS_FORBIDDEN);
    }
}
