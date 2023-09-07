<?php

namespace App\Repository;

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
                CASE WHEN DateDiff(Now(), p.createdAt) <= p.duration
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
                AND DateDiff(Now(), p.createedAt) <= p.duration
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
            ->where('p.user = :user')
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
            ->where('u.email IS NOT NULL')
            ->andWhere('DateDiff(Now(), p.createdAt) = p.duration - 5')
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

    public function removePermissionForCourse(Course $course): void
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
}
