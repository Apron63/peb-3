<?php

namespace App\Repository;

use App\Entity\Permission;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * @param string $identifier
     * @return User|null
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\User u
                WHERE u.login = :query
                AND u.active = 1
                '
        )
            ->setParameter('query', $identifier)
            ->getOneOrNullResult();
    }

    /**
     * Заглушка
     * @param string $username
     * @return UserInterface|void|null
     */
    public function loadUserByUsername(string $username)
    {
    }

    public function getUserExistsByLoginAndOrganization(string $login, string $organization): bool
    {
        $result = $this->getEntityManager()->createQuery('
                SELECT u.id FROM App\Entity\User u
                WHERE u.login = :login
                OR (u.login = :login AND u.organization = :organization)
            ')
            ->setParameter('login', $login)
            ->setParameter('organization', $organization)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $result !== null;
    }

    public function getAdmins(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :roleAdmin) > 0')
            ->orWhere('JSON_CONTAINS(u.roles, :roleSuperAdmin) > 0')
            ->setParameter('roleAdmin', '"ROLE_ADMIN"')
            ->setParameter('roleSuperAdmin', '"ROLE_SUPER_ADMIN"')
            ->orderBy('u.fullName');
    }

    public function getUserSearchQuery(?array $criteria, bool $forReport = false): AbstractQuery
    {
        $queryBuilder = $this->createQueryBuilder('u');

        $queryBuilder
            ->select('p.id AS permissionId, u.id AS userId, u.login, u.fullName, p.lastAccess,
                c.shortName, c.name, p.duration, p.createdAt, u.organization, u.active, p.activatedAt, p.stage,
                u.position, u.plainPassword, p.timeSpent
            ')
            ->leftJoin(Permission::class, 'p', Join::WITH, 'p.user = u.id')
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
            $orders = explode(',', $criteria['orderNumber']);

            if (false !== $orders) {
                $orders = array_map(fn($order) => trim($order), $orders);

                $queryBuilder->andWhere('p.orderNom IN (:orders)')
                    ->setParameter('orders', $orders);
            }
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

    public function getUserPortion(int $limit, int $offset): array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();
    }
}
