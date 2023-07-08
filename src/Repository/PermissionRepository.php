<?php

namespace App\Repository;

use DateTime;
use DateInterval;
use App\Entity\User;
use App\Entity\Course;
use App\Entity\Permission;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Permission>
 *
 * @method Permission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Permission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Permission[]    findAll()
 * @method Permission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    public function save(Permission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Permission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPermissionQuery(User $user): AbstractQuery
    {
        return $this->getEntityManager()->createQuery(
            "SELECT 
                p.id,
                p.createdAt,
                p.activatedAt,
                p.lastAccess,
                IDENTITY(p.user),
                p.duration,
                p.stage,
                p.orderNom,
                p.timeSpent,
                c.shortName AS shortName,
                c.id as courseId,
                CASE WHEN p.activatedAt IS NULL OR (DateDiff(Now(), p.activatedAt) <= p.duration)
                    THEN 1
                    ELSE 0
                    END AS isActive
                FROM App\Entity\Permission p
                JOIN App\Entity\Course c WITH c.id = p.course
                WHERE p.user = :user
                ORDER BY isActive DESC, p.createdAt DESC")
            ->setParameter('user', $user->getId());
    }

    public function getLastActivePermission(Course $course, UserInterface $user): ?Permission
    {
        $query = $this->getEntityManager()->createQuery('
                SELECT p FROM App\Entity\Permission p
                WHERE p.user = :user
                AND p.course = :course
                AND (p.activatedAt IS NULL OR DateDiff(Now(), p.activatedAt) <= p.duration)
                ORDER BY p.createdAt DESC
            ')
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->setParameter('course', $course);

        return $query->getOneOrNullResult();
    }

    public function getPermissionLeftMenu(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.activatedAt IS NULL OR DateDiff(Now(), p.activatedAt) <= p.duration')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Permission[]
     */
    public function getExpiredPermissionsList(): array
    {
        return $this
            ->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->where('u.email IS NOT NULL')
            ->andwhere('p.activatedAt IS NOT NULL')
            ->andWhere('DateDiff(Now(), p.activatedAt) = p.duration - 5')
            ->getQuery()
            ->getResult();
    }

    public function removePermissionForCourse(Course $course)
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT p.id FROM App\Entity\Permission p WHERE p.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $qIds = $query->execute();
        $qIds = array_map(function($e) {
                return $e['id'];
            }, $qIds);
        
        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\Logger l WHERE l.permission IN (:qIds)')
            ->setParameter('qIds', $qIds);

        $qIds = $query->execute();

        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Permission p WHERE p.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $query->execute();
    }

    public function getUserSearchQuery(?array $criteria, bool $forReport = false): AbstractQuery
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->select('p.id AS permissionId, u.id AS userId, u.login, u.fullName, p.lastAccess,
                c.shortName, p.duration, p.createdAt, u.organization, u.active, p.activatedAt, p.stage,
                u.position, u.plainPassword
            ')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.course', 'c')
            ->orderBy('u.login');

        if (isset($criteria['login']) && $criteria['login']) {
            $queryBuilder->andWhere('u.login LIKE :login')
                ->setParameter('login', "{$criteria['login']}%");
        }
        if (isset($criteria['name']) && $criteria['name']) {
            $queryBuilder->andWhere('u.fullName LIKE :name')
                ->setParameter('name', "{$criteria['name']}%");
        }
        if (isset($criteria['organization']) && $criteria['organization']) {
            $queryBuilder->andWhere('u.organization LIKE :organization')
                ->setParameter('organization', "%{$criteria['organization']}%");
        }
        if (isset($criteria['position']) && $criteria['position']) {
            $queryBuilder->andWhere('u.position LIKE :position')
                ->setParameter('position', "%{$criteria['position']}%");
        }
        if (isset($criteria['orderNumber']) && $criteria['orderNumber']) {
            $queryBuilder->andWhere('p.orderNom = :orderNumber')
                ->setParameter('orderNumber', $criteria['orderNumber']);
        }

        if (isset($criteria['startPeriod']) && $criteria['startPeriod']) {
            $queryBuilder->andWhere('p.createdAt >= :startPeriod')
                ->setParameter('startPeriod', (new DateTime($criteria['startPeriod']))->modify('today'));
        }

        if (isset($criteria['endPeriod']) && $criteria['endPeriod']) {
            $queryBuilder->andWhere('p.createdAt <= :endPeriod')
                ->setParameter(
                    'endPeriod',
                    (new DateTime($criteria['endPeriod']))->modify('tomorrow')->sub(new DateInterval('PT1S'))
                );
        }

        if (isset($criteria['course']) && $criteria['course']) {
            $queryBuilder->andWhere('p.course IN (:course)')
                ->setParameter('course', $criteria['course']);
        }

        if ($forReport) {
            $queryBuilder->orderBy('p.course');
        }

        return $queryBuilder->getQuery();
    }
}
