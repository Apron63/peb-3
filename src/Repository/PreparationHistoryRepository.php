<?php

declare (strict_types=1);

namespace App\Repository;

use App\Entity\Permission;
use App\Entity\PreparationHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PreparationHistory>
 */
class PreparationHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreparationHistory::class);
    }

    public function getPreparationHistory(Permission $permission): ?PreparationHistory
    {
        return
            $this->createQueryBuilder('ph')
            ->where('ph.permission = :permission')
            ->setParameter('permission', $permission)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(PreparationHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PreparationHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removePreparationHistoryForPermission(Permission $permission): void
    {
        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\PreparationHistory ph WHERE ph.permission = :permissionId')
            ->setParameter('permissionId', $permission->getId());

        $query->execute();
    }
}
