<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionException;
use OCA\AdCalendar\Service\ExternalCalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** Rein lesender CalDAV-Test ausschließlich für bestätigte Nextcloud-Admins. */
final class ExternalCalendarAdminController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private IGroupManager $groups,
        private ExternalCalendarService $calendars,
        private LoggerInterface $logger,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function testCalDav(string $serverUrl, string $username, string $password): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        try {
            $status = $this->calendars->testCalDavConnection('kopano', $serverUrl, $username, $password);
            return new JSONResponse(['message' => "Kopano-CalDAV-Verbindung erfolgreich geprüft (HTTP {$status})."]);
        } catch (\InvalidArgumentException $error) {
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (ExternalCalendarConnectionException $error) {
            $this->logger->error('Administrativer Kopano-CalDAV-Test wurde vom Anbieter abgewiesen.', ['provider' => 'kopano', 'status' => $error->getCode()]);
            return new JSONResponse(['error' => $error->userMessage('kopano')], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Administrativer Kopano-CalDAV-Test ist fehlgeschlagen.', ['provider' => 'kopano', 'exceptionClass' => $error::class]);
            return new JSONResponse(['error' => 'Die Kopano-CalDAV-Verbindung konnte nicht geprüft werden. Bitte Adresse und Serverkonfiguration prüfen.'], Http::STATUS_BAD_REQUEST);
        }
    }

    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null && $this->groups->isAdmin($user->getUID());
    }

    private function denied(): JSONResponse {
        return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
    }
}
