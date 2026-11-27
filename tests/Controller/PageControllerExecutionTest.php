<?php

declare(strict_types=1);

namespace OCP { interface IRequest {} }
namespace OCP\AppFramework { class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} } final class Http { public const STATUS_FORBIDDEN = 403; } }
namespace OCP\AppFramework\Http {
    final class TemplateResponse { public function __construct(public string $app, public string $template, public array $params = [], public string $renderAs = '', public int $status = 200) {} }
}
namespace OCP\AppFramework\Http\Attribute {
    #[\Attribute] final class NoAdminRequired {}
    #[\Attribute] final class NoCSRFRequired {}
}
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }
namespace OCA\FlzCalendar\Service {
    final class CalendarTargetConfig { public function adminStatus(): array { return ['kopanoUrl' => 'https://default.example.test/', 'calendarName' => 'Team & Dienst']; } }
    final class CalendarAccessService { public function __construct(public bool $allowed) {} public function canView(): bool { return $this->allowed; } }
}

namespace {

    use OCA\FlzCalendar\Controller\PageController;
    use OCA\FlzCalendar\Service\CalendarAccessService;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCP\IRequest;

    $response = (new PageController(new class implements IRequest {}, new CalendarTargetConfig(), new CalendarAccessService(true)))->index();
    if ($response->app !== 'flzcalendar' || $response->template !== 'index'
        || $response->params !== [
            'calendarDefaults' => ['kopanoUrl' => 'https://default.example.test/', 'calendarName' => 'Team & Dienst'],
        ]) {
        throw new RuntimeException('Persönliche Oberfläche erhält nicht ausschließlich die nicht geheimen Kalenderdefaults.');
    }
    $denied = (new PageController(new class implements IRequest {}, new CalendarTargetConfig(), new CalendarAccessService(false)))->index();
    if ($denied->app !== 'core' || $denied->template !== '403' || $denied->status !== 403) throw new RuntimeException('Organisationsfremde Konten erhalten die Kalenderseite statt eines serverseitigen 403.');
    echo "PageControllerExecutionTest: OK\n";
}
