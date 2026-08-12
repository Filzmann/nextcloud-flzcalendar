<?php

declare(strict_types=1);

namespace OCA\AdCalendar\Controller;

use InvalidArgumentException;
use OCA\AdCalendar\AppInfo\Application;
use OCA\AdCalendar\Http\LocalizedErrorResponseFactory;
use OCA\AdCalendar\Service\CalendarTargetConfig;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** Speichert nicht geheime Kalenderdefaults ausschließlich für bestätigte Nextcloud-Admins. */
final class CalendarDefaultsAdminController extends Controller {
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private IGroupManager $groups,
        private CalendarTargetConfig $targets,
        private LoggerInterface $logger,
        private LocalizedErrorResponseFactory $errors,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function save(string $kopanoUrl, string $calendarName): JSONResponse {
        if (!$this->isAdmin()) {
            return $this->errors->create('forbidden', 'You are not allowed to perform this action.', Http::STATUS_FORBIDDEN);
        }
        try {
            return new JSONResponse(['calendarDefaults' => $this->targets->save($kopanoUrl, $calendarName)]);
        } catch (InvalidArgumentException) {
            return $this->errors->create('invalid_calendar_defaults', 'The calendar defaults are invalid.', Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $error) {
            $this->logger->error('Kalenderdefaults konnten nicht gespeichert werden.', ['exception' => $error]);
            return $this->errors->create('calendar_defaults_save_failed', 'The calendar defaults could not be saved.', Http::STATUS_BAD_REQUEST);
        }
    }

    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null && $this->groups->isAdmin($user->getUID());
    }
}
