<?php

declare (strict_types=1);

namespace App\Repository;

use DateTime;
use App\Entity\Logger;
use App\Entity\Permission;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Logger>
 *
 * @method Logger|null find($id, $lockMode = null, $lockVersion = null)
 * @method Logger|null findOneBy(array $criteria, array $orderBy = null)
 * @method Logger[]    findAll()
 * @method Logger[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoggerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Logger::class);
    }

    public function save(Logger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Logger $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLastLogger(Permission $permission, UserInterface $user): ?Logger
    {
        return $this->createQueryBuilder('l')
            ->where('l.permission = :permission')
            ->andWhere('l.user = :user')
            ->andWhere('l.result = 0')
            ->andWhere('l.endAt IS NULL')
            ->andWhere('l.endAt IS NULL')
            ->setParameter('permission', $permission)
            ->setParameter('user', $user)
            ->orderBy('l.beginAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFirstSuccessfullyLogger(Permission $permission, UserInterface $user): ?Logger
    {
        return $this->createQueryBuilder('l')
            ->where('l.permission = :permission')
            ->andWhere('l.user = :user')
            ->andWhere('l.result = 1')
            ->setParameter('permission', $permission)
            ->setParameter('user', $user)
            ->orderBy('l.beginAt', 'asc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function removeLoggerForPermission(Permission $permission): void
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Logger l WHERE l.permission = :permissionId")
            ->setParameter('permissionId', $permission->getId());

        $query->execute();
    }

    public function getFinalTestingDate(Permission $permission, UserInterface $user): ?DateTime
    {
        $result = null;

        $logger = $this->findFirstSuccessfullyLogger($permission, $user);
        if (! $logger instanceof Logger) {
            $logger = $this->findLastLogger($permission, $user);
        }

        if ($logger instanceof Logger) {
            $result = $logger->getBeginAt();
        }

        return $result;
    }

    public function getLoggersByUsers(int ...$userIds): array
    {
        $queryBuilder = $this->createQueryBuilder('l');

        return $queryBuilder
            ->select('l.id, identity(l.user) as user_id, identity(l.permission) as permission_id, l.beginAt, l.result')
            ->where('l.user IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->getQuery()
            ->getArrayResult();
    }
}
