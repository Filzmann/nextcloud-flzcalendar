<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\CalendarSync\ExternalCalendarConnectionException;
use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
use OCA\AdCalendar\Service\ExternalCalendarService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IL10N;
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
        private LocalizedErrorResponseFactory $errors,
        private IL10N $l10n,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function testCalDav(string $serverUrl, string $username, string $password): JSONResponse {
        if (!$this->isAdmin()) return $this->denied();
        try {
            $status = $this->calendars->testCalDavConnection('kopano', $serverUrl, $username, $password);
            return new JSONResponse(['message' => $this->l10n->t('Kopano CalDAV connection successfully tested (HTTP {status}).', ['{status}' => (string)$status])]);
        } catch (\InvalidArgumentException) {
            return $this->errors->create('invalid_caldav_connection', 'The CalDAV connection details are invalid.', Http::STATUS_BAD_REQUEST);
        } catch (ExternalCalendarConnectionException $error) {
            $this->logger->error('Administrativer Kopano-CalDAV-Test wurde vom Anbieter abgewiesen.', ['provider' => 'kopano', 'status' => $error->getCode()]);
            return $this->errors->create('caldav_provider_rejected', 'The calendar provider rejected the CalDAV connection (HTTP {status}). Please contact its administration.', Http::STATUS_BAD_REQUEST, ['{status}' => (string)$error->getCode()]);
        } catch (\Throwable $error) {
            $this->logger->error('Administrativer Kopano-CalDAV-Test ist fehlgeschlagen.', ['provider' => 'kopano', 'exceptionClass' => $error::class]);
            return $this->errors->create('caldav_test_failed', 'The Kopano CalDAV connection could not be tested. Please check the address and server configuration.', Http::STATUS_BAD_REQUEST);
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
