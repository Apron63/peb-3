<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Logger;
use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
            ->setParameters([
                'permission' => $permission,
                'user' => $user,
            ])
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
            ->setParameters([
                'permission' => $permission,
                'user' => $user,
            ])
            ->orderBy('l.beginAt', 'asc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function removeLoggerForCourse(Course $course)
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Logger l WHERE l.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $query->execute();
    }

    public function removeLoggerForPermission(Permission $permission)
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Logger l WHERE l.permission = :permissionId")
            ->setParameter('permissionId', $permission->getId());

        $query->execute();
    }
}
