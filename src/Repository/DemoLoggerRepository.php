<?php

namespace App\Repository;

use App\Entity\DemoLogger;
use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<DemoLogger>
 *
 * @method DemoLogger|null find($id, $lockMode = null, $lockVersion = null)
 * @method DemoLogger|null findOneBy(array $criteria, array $orderBy = null)
 * @method DemoLogger[]    findAll()
 * @method DemoLogger[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DemoLoggerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemoLogger::class);
    }

    public function save(DemoLogger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DemoLogger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLastLogger(Permission $permission, UserInterface $user): ?DemoLogger
    {
        return $this->createQueryBuilder('l')
            ->where('l.permission = :permission')
            ->andWhere('l.user = :user')
            ->andWhere('l.result = 0')
            ->andWhere('l.endAt IS NULL')
            ->setParameters([
                'permission' => $permission,
                'user' => $user,
            ])
            ->orderBy('l.beginAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function findFirstSuccessfullyLogger(Permission $permission, UserInterface $user): ?DemoLogger
    {
        return $this->createQueryBuilder('l')
            ->where('l.permission = :permission')
            ->andWhere('l.user = :user')
            ->andWhere('l.result = 1')
            ->setParameters([
                'permission' => $permission,
                'user' => $user,
            ])
            ->orderBy('l.beginAt', 'asc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function removeLoggerForPermission(Permission $permission)
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\DemoLogger l WHERE l.permission = :permissionId")
            ->setParameter('permissionId', $permission->getId());

        $query->execute();
    }
}
