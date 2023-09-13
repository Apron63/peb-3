<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\Query;
use App\Entity\QueryUser;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<QueryUser>
 *
 * @method QueryUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueryUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method QueryUser[]    findAll()
 * @method QueryUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueryUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueryUser::class);
    }

    public function save(QueryUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QueryUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getQueryUser(User $user): Query
    {
        return $this->createQueryBuilder('q')
            ->where('q.result = :result')
            ->andWhere('q.createdBy = :user')
            ->setParameter('result', 'new')
            ->setParameter('user', $user)
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery();
    }

    /**
     * @return QueryUser[]
     */
    public function getUserQueryNew(User $user): array
    {
        $qb = $this->createQueryBuilder('uq')
            ->select('uq')
            //->select('uq, IDENTITY(uq.createdBy) AS createdBy')
            ->where('uq.result = \'new\'')
            ->andWhere('uq.createdBy = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function checkUserQueryIsEmpty(User $user): bool
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT COUNT(q.id) FROM App\Entity\QueryUser q where q.createdBy = :user AND q.result = :result')
            ->setParameter('user', $user)
            ->setParameter('result', 'new');
        
        return $query->getSingleScalarResult() === 0;
    }

    public function getQueryJobNewCount(): int
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT COUNT(q.id) FROM App\Entity\QueryUser q where q.result = :result')
            ->setParameter('result', 'new');

        return $query->getSingleScalarResult();
    }

    public function queryUserClear(): void
    {
        $queryBuilder = $this->createQueryBuilder('qu');

        $queryBuilder
            ->delete()
            ->where('qu.result = :result')
            ->setParameter('result', 'new')
            ->getQuery()
            ->execute();
    }
}
