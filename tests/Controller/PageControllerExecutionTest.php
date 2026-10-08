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
namespace OCA\FlzCalendar\AppInfo { final class Application { public const APP_ID = 'flzcalendar'; } }
namespace OCA\FlzCalendar\Service {
    final class CalendarTargetConfig { public function adminStatus(): array { return ['kopanoUrl' => 'https://default.example.test/', 'calendarName' => 'Team & Dienst']; } }
    final class TemporaryAdminAccessService {
        public function __construct(public bool $canManage, public bool $needsGrant) {}
        public function canManageGrants(): bool { return $this->canManage; }
        public function currentAdminNeedsGrant(): bool { return $this->needsGrant; }
    }
}

namespace {

    use OCA\FlzCalendar\Controller\PageController;
    use OCA\FlzCalendar\Service\CalendarTargetConfig;
    use OCA\FlzCalendar\Service\TemporaryAdminAccessService;
    use OCP\IRequest;

    $response = (new PageController(new class implements IRequest {}, new CalendarTargetConfig(), new TemporaryAdminAccessService(true, true)))->index();
    if ($response->app !== 'flzcalendar' || $response->template !== 'index'
        || $response->params !== [
            'calendarDefaults' => ['kopanoUrl' => 'https://default.example.test/', 'calendarName' => 'Team & Dienst'],
            'canManageAdminAccess' => true,
            'showMissingAdminGrant' => true,
            'showAdminAccessLink' => true,
        ]) {
        throw new RuntimeException('Persönliche Oberfläche erhält nicht die nicht geheimen Kalenderdefaults und sichere DPO-Rollenmatrix.');
    }
    $nativeAdminOnly = (new PageController(new class implements IRequest {}, new CalendarTargetConfig(), new TemporaryAdminAccessService(false, true)))->index();
    if (($nativeAdminOnly->params['showAdminAccessLink'] ?? true) !== false) throw new RuntimeException('Nativer Admin ohne Datenschutzrolle erhält einen Freigabelink.');
    echo "PageControllerExecutionTest: OK\n";
}
