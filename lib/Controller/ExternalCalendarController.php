<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionException;
use OCA\AdCalendar\CalendarSync\GoogleOAuthService;
use OCA\AdCalendar\Service\CalendarAccessService;
use OCA\AdCalendar\Service\ExternalCalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

final class ExternalCalendarController extends Controller {
    public function __construct(
        IRequest $request,
        private CalendarAccessService $access,
        private ExternalCalendarService $calendars,
        private GoogleOAuthService $googleOAuth,
        private IURLGenerator $urls,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function status(): JSONResponse {
        $uid = $this->uid();
        return $uid === null ? $this->denied() : new JSONResponse(['externalCalendars' => $this->calendars->status($uid)]);
    }

    #[NoAdminRequired]
    public function connectCalDav(string $provider, string $serverUrl, string $username, string $password): JSONResponse {
        $uid = $this->uid();
        if ($uid === null) return $this->denied();
        try {
            return new JSONResponse(['externalCalendars' => $this->calendars->connectCalDav($uid, $provider, $serverUrl, $username, $password)]);
        } catch (\InvalidArgumentException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (ExternalCalendarConnectionException $error) {
            $this->logger->error('Der externe Kalenderanbieter erlaubt keine CalDAV-Verbindung.', ['provider' => $provider, 'status' => $error->getCode()]);
            return new JSONResponse(['error' => $error->userMessage($provider)], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Externe CalDAV-Verbindung konnte nicht hergestellt werden.', ['provider' => $provider, 'exception' => $error]);
            return new JSONResponse(['error' => 'Die Kalenderverbindung konnte nicht hergestellt werden. Bitte Adresse und Zugangsdaten prüfen.'], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function disconnect(string $provider): JSONResponse {
        $uid = $this->uid();
        if ($uid === null) return $this->denied();
        try {
            return new JSONResponse(['externalCalendars' => $this->calendars->disconnect($uid, $provider)]);
        } catch (\Throwable $error) {
            $this->logger->error('Externe Kalenderverbindung konnte nicht getrennt werden.', ['provider' => $provider, 'exception' => $error]);
            return new JSONResponse(['error' => 'Die Verbindung konnte nicht sicher getrennt werden. Bitte später erneut versuchen.'], Http::STATUS_BAD_REQUEST);
        }
    }

    #[NoAdminRequired]
    public function googleStart(): JSONResponse {
        $uid = $this->uid();
        if ($uid === null) return $this->denied();
        try {
            return new JSONResponse(['authorizationUrl' => $this->googleOAuth->authorizationUrl($uid)]);
        } catch (\Throwable $error) {
            $this->logger->error('Google-OAuth konnte nicht gestartet werden.', ['exception' => $error]);
            return new JSONResponse(['error' => 'Google ist noch nicht durch die Administration konfiguriert.'], Http::STATUS_BAD_REQUEST);
        }
    }

    /** Externe OAuth-Rückkehr: CSRF-Ausnahme ist auf den einmaligen, nutzergebundenen Statuswert begrenzt. */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function googleCallback(string $state = '', string $code = '', string $error = ''): RedirectResponse|JSONResponse {
        $uid = $this->uid();
        if ($uid === null) return $this->denied();
        $result = 'google-error';
        try {
            if ($error !== '') {
                $this->googleOAuth->cancel($uid, $state);
                throw new \RuntimeException('Google-Autorisierung wurde abgebrochen.');
            }
            $this->calendars->connectGoogle($uid, $this->googleOAuth->exchange($uid, $state, $code));
            $result = 'google-connected';
        } catch (\Throwable $exception) {
            $this->logger->error('Google-Kalenderverbindung konnte nicht abgeschlossen werden.', ['exception' => $exception]);
        }
        return new RedirectResponse($this->urls->linkToRoute('adcalendar.page.index') . '?calendarConnection=' . $result);
    }

    private function uid(): ?string {
        return $this->access->currentUser()?->getUID();
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
    }
}
