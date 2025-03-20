<?php

declare (strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Permission;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
                CASE WHEN DateDiff(Now(), p.createdAt) <= p.duration
                    THEN 1
                    ELSE 0
                    END AS isActive
                FROM App\Entity\Permission p
                JOIN App\Entity\Course c WITH c.id = p.course
                WHERE p.user = :user
                ORDER BY isActive DESC, p.createdAt DESC"
            )
            ->setParameter('user', $user->getId());
    }

    public function getLastActivePermission(Course $course, UserInterface $user): ?Permission
    {
        $query = $this->getEntityManager()->createQuery('
                SELECT p FROM App\Entity\Permission p
                WHERE p.user = :user
                AND p.course = :course
                AND DateDiff(Now(), p.createdAt) <= p.duration
                ORDER BY p.createdAt DESC
            ')
            ->setMaxResults(1)
            ->setParameter('user', $user)
            ->setParameter('course', $course);

        return $query->getOneOrNullResult();
    }

    /**
     * @return Permission[]
     */
    public function getPermissionLeftMenu(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.course', 'c')
            ->where('p.user = :user')
            ->andWhere('c.hidden != 1')
            ->andWhere('DateDiff(Now(), p.createdAt) <= p.duration')
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
            ->where('DateDiff(Now(), p.createdAt) = p.duration - 5')
            ->getQuery()
            ->getResult();
    }

    public function getLoaderByCourse(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->select('
                u.fullName,
                l.position,
                l.organization,
                u.login,
                u.plainPassword,
                p.duration,
                c.name,
                c.shortName
            ')
            ->where('l.createdBy = :user')
            ->andWhere('l.checked = 1')
            ->join('p.loader', 'l')
            ->join('l.user', 'u')
            ->join('p.course', 'c')
            ->orderBy('c.id')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Permission[]
     */
    public function getPermissionForHistory(User $user, int $page, int $perPage): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('p.createdAt', 'desc')
            ->getQuery()
            ->getResult();
    }

    public function getTotalPermissionsForUser(User $user): int
    {
        return $this->createQueryBuilder('p')
            ->select('count(p)')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function removePermissionForCourse(Course $course): void
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT p.id FROM App\Entity\Permission p WHERE p.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $permissionsIds = $query->execute();
        $permissionsIds = array_map(
            static fn($e) => $e['id'],
            $permissionsIds
        );

        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\Logger l WHERE l.permission IN (:permissionsIds)')
            ->setParameter('permissionsIds', $permissionsIds);

        $qq = $query->execute();

        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\PreparationHistory ph WHERE ph.permission IN (:permissionsIds)')
            ->setParameter('permissionsIds', $permissionsIds);

        $qq = $query->execute();

        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Permission p WHERE p.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $query->execute();
    }

    public function getPermissionsByIds(int ...$permissionIds): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->select('p.id, identity(p.user) as user_id')
            ->where('p.id IN (:permissionIds)')
            ->setParameter('permissionIds', $permissionIds)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return Permission[]
     */
    public function getPermissionForReport(): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->where('p.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', new DateTime('2024-07-11'))
            ->setParameter('endDate', new DateTime('2024-09-12'))
            ->orderBy('p.createdAt')
            ->getQuery()
            ->getResult();
    }

    /**
     *  @return Permission[]
     */
    public function getPermissonSelectedByUser(User $user): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder
            ->where('p.checkedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.course, p.createdAt')
            ->getQuery()
            ->getResult();
    }
}
