<?php

declare(strict_types=1);

namespace OCP { interface IRequest {} }
namespace OCP\AppFramework { class Controller { public function __construct(string $appId,\OCP\IRequest $request){} } final class Http { public const STATUS_BAD_REQUEST=400;public const STATUS_FORBIDDEN=403;public const STATUS_INTERNAL_SERVER_ERROR=500; } }
namespace OCP\AppFramework\Http { final class JSONResponse { public function __construct(private array $data=[],private int $status=200){}public function getData():array{return $this->data;}public function getStatus():int{return $this->status;} } }
namespace OCP\AppFramework\Http\Attribute { #[\Attribute(\Attribute::TARGET_METHOD)] final class NoCSRFRequired {} #[\Attribute(\Attribute::TARGET_METHOD)] final class NoAdminRequired {} }
namespace Psr\Log { interface LoggerInterface { public function error(string $message,array $context=[]):void; } }
namespace OCA\AdCalendar\Service {
    final class TemporaryAdminAccessService {
        public string $mode='allowed'; public int $mutations=0;
        public function state():array { if($this->mode==='denied')throw new TemporaryAdminAccessDeniedException();return ['maxDurationMinutes'=>1440,'history'=>[]]; }
        public function activate(string $uid,int $minutes):array { if($this->mode==='invalid')throw new \InvalidArgumentException();if($this->mode==='failed')throw new \LogicException();$this->mutations++;return ['id'=>1,'targetUid'=>$uid,'grantedBy'=>'operator','startsAt'=>new \DateTimeImmutable('2026-08-25T10:00:00Z'),'endsAt'=>new \DateTimeImmutable('2026-08-25T11:00:00Z'),'revokedAt'=>null,'revokedBy'=>null]; }
        public function revoke(string $uid):bool { $this->mutations++;return true; }
    }
    final class TemporaryAdminAccessDeniedException extends \RuntimeException {}
}
namespace {
    use OCA\AdCalendar\Controller\TemporaryAdminAccessController;
    use OCA\AdCalendar\Service\TemporaryAdminAccessService;
    use OCP\AppFramework\Http;

    $service=new TemporaryAdminAccessService();
    $logger=new class implements Psr\Log\LoggerInterface { public array $errors=[];public function error(string $message,array $context=[]):void{$this->errors[]=$message;} };
    $controller=new TemporaryAdminAccessController(new class implements OCP\IRequest{},$service,$logger);
    $statusMethod=new ReflectionMethod(TemporaryAdminAccessController::class,'status');
    if($statusMethod->getAttributes(OCP\AppFramework\Http\Attribute\NoAdminRequired::class)===[]||$statusMethod->getAttributes(OCP\AppFramework\Http\Attribute\NoCSRFRequired::class)===[])throw new RuntimeException('DPO-Nichtadmins erreichen den read-only Statuspfad nicht.');
    foreach(['activate','revoke'] as $methodName){$method=new ReflectionMethod(TemporaryAdminAccessController::class,$methodName);if($method->getAttributes(OCP\AppFramework\Http\Attribute\NoAdminRequired::class)===[]||$method->getAttributes(OCP\AppFramework\Http\Attribute\NoCSRFRequired::class)!==[])throw new RuntimeException($methodName.' hat falschen DPO-/CSRF-Routenvertrag.');}
    if($controller->status()->getData()['maxDurationMinutes']!==1440)throw new RuntimeException('Status liefert die 24-Stunden-Grenze nicht.');
    $response=$controller->activate('admin-target',60);
    if($response->getStatus()!==200||$response->getData()['grant']['targetUid']!=='admin-target'||$response->getData()['grant']['endsAt']!=='2026-08-25T11:00:00+00:00')throw new RuntimeException('Gültige Freigabe wird nicht sicher serialisiert.');
    $service->mode='invalid';$before=$service->mutations;
    if($controller->activate('admin-target',1441)->getStatus()!==Http::STATUS_BAD_REQUEST||$service->mutations!==$before)throw new RuntimeException('Ungültige Freigabe wird nicht mutationsfrei abgewiesen.');
    $service->mode='denied';
    if($controller->status()->getStatus()!==Http::STATUS_FORBIDDEN)throw new RuntimeException('Nicht-Admin kann den Freigabestatus lesen.');
    $service->mode='failed';
    if($controller->activate('admin-target',60)->getStatus()!==Http::STATUS_INTERNAL_SERVER_ERROR||$logger->errors===[])throw new RuntimeException('Persistenzfehler wird nicht sicher diagnostiziert.');
    echo "AD Kalender temporary admin access controller tests passed\n";
}
