<?php

declare(strict_types=1);

namespace Psr\Clock { interface ClockInterface { public function now(): \DateTimeImmutable; } }
namespace Psr\Log { interface LoggerInterface { public function emergency(string|\Stringable $message,array $context=[]):void;public function alert(string|\Stringable $message,array $context=[]):void;public function critical(string|\Stringable $message,array $context=[]):void;public function error(string|\Stringable $message,array $context=[]):void;public function warning(string|\Stringable $message,array $context=[]):void;public function notice(string|\Stringable $message,array $context=[]):void;public function info(string|\Stringable $message,array $context=[]):void;public function debug(string|\Stringable $message,array $context=[]):void;public function log($level,string|\Stringable $message,array $context=[]):void; } }
namespace OCP {
    interface IUser { public function getUID(): string; }
    interface IUserSession { public function getUser(): ?IUser; }
    interface IGroupManager { public function isAdmin(string $uid): bool; public function isInGroup(string $uid, string $gid): bool; }
}
namespace OCP\AppFramework\Utility {
    interface ITimeFactory extends \Psr\Clock\ClockInterface { public function getTime(): int; public function getDateTime(string $time='now',?\DateTimeZone $timezone=null):\DateTime; public function withTimeZone(\DateTimeZone $timezone):static; public function getTimeZone(?string $timezone=null):\DateTimeZone; }
}
namespace OCA\FlzCalendar\Repository {
    interface TemporaryAdminAccessRepositoryInterface {
        public function replaceActive(string $targetUid,string $grantedBy,\DateTimeImmutable $startsAt,\DateTimeImmutable $endsAt):array;
        public function revokeActive(string $targetUid,string $revokedBy,\DateTimeImmutable $revokedAt):bool;
        public function activeFor(string $targetUid,\DateTimeImmutable $at):?array;
        public function history():array;
    }
}
namespace {
    use OCA\FlzCalendar\Repository\TemporaryAdminAccessRepositoryInterface;
    use OCA\FlzCalendar\Service\TemporaryAdminAccessService;

    $actor = new class implements OCP\IUser { public function getUID(): string { return 'admin-operator'; } };
    $session = new class($actor) implements OCP\IUserSession { public function __construct(public ?OCP\IUser $user) {} public function getUser(): ?OCP\IUser { return $this->user; } };
    $groups = new class implements OCP\IGroupManager {
        public array $admins=['admin-operator','admin-target'];
        public array $memberships=['privacy-officer'=>['Datenschutzbeauftragte']];
        public function isAdmin(string $uid): bool { return in_array($uid,$this->admins,true); }
        public function isInGroup(string $uid,string $gid): bool { return in_array($gid,$this->memberships[$uid]??[],true); }
    };
    $clock = new class implements OCP\AppFramework\Utility\ITimeFactory {
        public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-08-25T10:00:00+00:00'); }
        public function getTime(): int { return $this->now()->getTimestamp(); }
        public function getDateTime(string $time='now',?DateTimeZone $timezone=null):DateTime { return new DateTime($time,$timezone); }
        public function withTimeZone(DateTimeZone $timezone):static { return $this; }
        public function getTimeZone(?string $timezone=null):DateTimeZone { return new DateTimeZone($timezone??'UTC'); }
    };
    $repository = new class implements TemporaryAdminAccessRepositoryInterface {
        public array $rows=[];
        public int $mutations=0;
        public bool $failReads=false;
        public function replaceActive(string $targetUid,string $grantedBy,DateTimeImmutable $startsAt,DateTimeImmutable $endsAt):array {
            $this->mutations++;
            foreach ($this->rows as &$row) if ($row['targetUid']===$targetUid && $row['revokedAt']===null) $row['revokedAt']=$startsAt;
            $row=['id'=>count($this->rows)+1,'targetUid'=>$targetUid,'grantedBy'=>$grantedBy,'startsAt'=>$startsAt,'endsAt'=>$endsAt,'revokedAt'=>null,'revokedBy'=>null];
            $this->rows[]=$row;
            return $row;
        }
        public function revokeActive(string $targetUid,string $revokedBy,DateTimeImmutable $revokedAt):bool { foreach($this->rows as &$row)if($row['targetUid']===$targetUid&&$row['revokedAt']===null){$row['revokedAt']=$revokedAt;$row['revokedBy']=$revokedBy;$this->mutations++;return true;}return false; }
        public function activeFor(string $targetUid,DateTimeImmutable $at):?array { if($this->failReads)throw new RuntimeException('storage unavailable');foreach(array_reverse($this->rows) as $row)if($row['targetUid']===$targetUid&&$row['revokedAt']===null&&$row['startsAt']<=$at&&$row['endsAt']>$at)return $row;return null; }
        public function history():array { return array_reverse($this->rows); }
    };
    $logger = new class implements Psr\Log\LoggerInterface { public array $messages=[];public function emergency(string|Stringable $m,array $c=[]):void{}public function alert(string|Stringable $m,array $c=[]):void{}public function critical(string|Stringable $m,array $c=[]):void{}public function error(string|Stringable $m,array $c=[]):void{$this->messages[]=['error',(string)$m,$c];}public function warning(string|Stringable $m,array $c=[]):void{}public function notice(string|Stringable $m,array $c=[]):void{}public function info(string|Stringable $m,array $c=[]):void{$this->messages[]=['info',(string)$m,$c];}public function debug(string|Stringable $m,array $c=[]):void{}public function log($l,string|Stringable $m,array $c=[]):void{} };
    $service = new TemporaryAdminAccessService($session,$groups,$repository,$clock,$logger);

    $before=$repository->mutations;
    try { $service->activate('admin-target',60); throw new RuntimeException('Nativer Admin ohne Datenschutzrolle durfte freigeben.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    try { $service->revoke('admin-target'); throw new RuntimeException('Nativer Admin ohne Datenschutzrolle durfte widerrufen.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    try { $service->state(); throw new RuntimeException('Nativer Admin ohne Datenschutzrolle durfte Historie lesen.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    if($repository->mutations!==$before)throw new RuntimeException('Abgewiesene Adminanfragen dürfen nichts persistieren.');

    $session->user=new class implements OCP\IUser { public function getUID():string{return 'privacy-officer';} };
    if(!$service->canManageGrants()||$service->state()['history']!==[])throw new RuntimeException('Datenschutzbeauftragte ohne Adminstatus erreichen die Freigabesteuerung nicht.');

    foreach ([['ordinary',60],['admin-target',1441]] as [$target,$duration]) {
        $before=$repository->mutations;
        try { $service->activate($target,$duration); throw new RuntimeException('Manipulierte Freigabe wurde akzeptiert.'); } catch (InvalidArgumentException) {}
        if($repository->mutations!==$before)throw new RuntimeException('Manipulierte Freigabe darf nichts persistieren.');
    }
    $before=$repository->mutations;
    try { $service->revoke('ordinary'); throw new RuntimeException('Manipulierter Widerruf wurde akzeptiert.'); } catch (InvalidArgumentException) {}
    if($repository->mutations!==$before)throw new RuntimeException('Manipulierter Widerruf darf nichts persistieren.');

    $grant=$service->activate('admin-target',1440);
    if ($grant['grantedBy']!=='privacy-officer'||$grant['startsAt']->format(DATE_ATOM)!=='2026-08-25T10:00:00+00:00'||$grant['endsAt']->format(DATE_ATOM)!=='2026-08-26T10:00:00+00:00') throw new RuntimeException('Serverzeit, Auditrolle oder 24-Stunden-Grenze ist fehlerhaft.');
    if (!$service->hasActiveGrant('admin-target')||$service->hasActiveGrant('admin-other')) throw new RuntimeException('Die Freigabe ist nicht UID-genau.');

    $groups->admins=['admin-operator'];
    if ($service->hasActiveGrant('admin-target')) throw new RuntimeException('Entzogener Nextcloud-Adminstatus muss die Freigabe sofort unwirksam machen.');
    $groups->admins=['admin-operator','admin-target'];
    $groups->memberships=[];
    $before=$repository->mutations;
    try { $service->revoke('admin-target'); throw new RuntimeException('Entzogene Datenschutzrolle durfte widerrufen.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    if($repository->mutations!==$before)throw new RuntimeException('Abgewiesener Widerruf darf keine Historie verändern.');
    $groups->memberships=['privacy-officer'=>['Datenschutzbeauftragte']];
    if (!$service->revoke('admin-target')||$service->hasActiveGrant('admin-target')) throw new RuntimeException('Widerruf muss den aktiven Zeitraum beenden.');

    $repository->failReads=true;
    if($service->hasActiveGrant('admin-target'))throw new RuntimeException('Persistenzfehler muss fail-closed bleiben.');
    $repository->failReads=false;

    $session->user=new class implements OCP\IUser { public function getUID():string{return 'ordinary';} };
    $before=$repository->mutations;
    try { $service->activate('admin-target',60); throw new RuntimeException('Konto ohne Datenschutzrolle durfte freigeben.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    try { $service->revoke('admin-target'); throw new RuntimeException('Konto ohne Datenschutzrolle durfte widerrufen.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    try { $service->state(); throw new RuntimeException('Konto ohne Datenschutzrolle durfte Historie lesen.'); } catch (OCA\FlzCalendar\Service\TemporaryAdminAccessDeniedException) {}
    if($repository->mutations!==$before||$service->currentAdminNeedsGrant())throw new RuntimeException('Gewöhnliches Konto erhielt Adminzustand oder mutierte Historie.');

    $session->user=new class implements OCP\IUser { public function getUID():string{return 'admin-operator';} };
    if(!$service->currentAdminNeedsGrant()||$service->canManageGrants())throw new RuntimeException('Nativer Admin ohne Freigabe erhält falschen Eintritts- oder Linkzustand.');
    $groups->memberships['admin-operator']=['Datenschutzbeauftragte'];
    if(!$service->canManageGrants())throw new RuntimeException('Admin mit Datenschutzrolle erhält keinen Direktlinkzustand.');

    echo "Filzmann Kalender temporary admin access service tests passed\n";
}
