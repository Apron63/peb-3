<?php

declare (strict_types=1);

namespace App\Repository;

use App\Entity\PermissionHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PermissionHistory>
 */
class PermissionHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PermissionHistory::class);
    }

    public function save(PermissionHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PermissionHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPermissionsHistories(int ...$permissionsIds): array
    {
        $result = [];

        $queryBuilder = $this->createQueryBuilder('ph');

        $queryResults = $queryBuilder
            ->select([
                'ph.permissionId',
                'ph.createdAt',
                'ph.duration',
                'u.fullName',
            ])
            ->join('ph.createdBy', 'u', Join::WITH)
            ->where('ph.permissionId IN (:permissionsIds)')
            ->setParameter('permissionsIds', $permissionsIds)
            ->orderBy('ph.permissionId')
            ->addOrderBy('ph.initial')
            ->addOrderBy('ph.createdAt')
            ->getQuery()
            ->getArrayResult();

        foreach ($queryResults as $queryResult) {
            $result[$queryResult['permissionId']][] = $queryResult;
        }

        return $result;
    }

    /**
     * @return PermissionHistory[]
     */
    public function getOnePermissionHistories(int $permissionId): array
    {
        $queryBuilder = $this->createQueryBuilder('ph');

        return $queryBuilder
            ->where('ph.permissionId = :permissionId')
            ->setParameter('permissionId', $permissionId)
            ->orderBy('ph.initial')
            ->addOrderBy('ph.createdAt')
            ->getQuery()
            ->getResult();
    }
}
