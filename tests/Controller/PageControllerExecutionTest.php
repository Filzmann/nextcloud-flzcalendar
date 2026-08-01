<?php

declare(strict_types=1);

namespace OCP { interface IRequest {} }
namespace OCP\AppFramework { class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} } }
namespace OCP\AppFramework\Http {
    final class TemplateResponse { public function __construct(public string $app, public string $template, public array $params = []) {} }
}
namespace OCP\AppFramework\Http\Attribute {
    #[\Attribute] final class NoAdminRequired {}
    #[\Attribute] final class NoCSRFRequired {}
}
namespace OCA\AdCalendar\AppInfo { final class Application { public const APP_ID = 'adcalendar'; } }
namespace OCA\AdCalendar\Service {
    final class CalendarTargetConfig { public function adminStatus(): array { return ['kopanoUrl' => 'https://default.example.test/', 'calendarName' => 'Team & Dienst']; } }
}

namespace {
    require_once __DIR__ . '/../../lib/Controller/PageController.php';

    use OCA\AdCalendar\Controller\PageController;
    use OCA\AdCalendar\Service\CalendarTargetConfig;
    use OCP\IRequest;

    $response = (new PageController(new class implements IRequest {}, new CalendarTargetConfig()))->index();
    if ($response->app !== 'adcalendar' || $response->template !== 'index'
        || $response->params !== ['calendarDefaults' => ['kopanoUrl' => 'https://default.example.test/', 'calendarName' => 'Team & Dienst']]) {
        throw new RuntimeException('Persönliche Oberfläche erhält nicht ausschließlich die nicht geheimen Kalenderdefaults.');
    }
    echo "PageControllerExecutionTest: OK\n";
}
