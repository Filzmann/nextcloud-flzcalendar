<?php

declare(strict_types=1);

namespace OCA\FlzCalendar\Repository;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/** Read-only-Zugriff auf vor der Adminausnahme entstandene historische Freigaben. */
final class TemporaryAdminAccessRepository {
    public function __construct(private IDBConnection $db) {}

    public function historyForUid(string $uid, int $limit): array {
        $qb = $this->db->getQueryBuilder();
        $rows = $qb->select('id','target_uid','granted_by','starts_at','ends_at','revoked_at','revoked_by')
            ->from('flz_calendar_admin_access')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('target_uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR)),
                $qb->expr()->eq('granted_by', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR)),
                $qb->expr()->eq('revoked_by', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR)),
            ))
            ->orderBy('starts_at','DESC')->setMaxResults($limit)->executeQuery()->fetchAllAssociative();
        return array_map([$this,'mapRow'],$rows);
    }

    private function mapRow(array $row): array {
        return ['id'=>(int)$row['id'],'targetUid'=>(string)$row['target_uid'],'grantedBy'=>(string)$row['granted_by'],'startsAt'=>$this->date($row['starts_at']),'endsAt'=>$this->date($row['ends_at']),'revokedAt'=>$row['revoked_at']===null?null:$this->date($row['revoked_at']),'revokedBy'=>$row['revoked_by']===null?null:(string)$row['revoked_by']];
    }

    private function date(mixed $value): DateTimeImmutable {
        return $value instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($value) : new DateTimeImmutable((string)$value,new DateTimeZone('UTC'));
    }
}

