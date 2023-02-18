<?php

namespace App\Repository;

use App\Entity\QueryUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

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

    public function getQueryUser(): Query
    {
        return $this->createQueryBuilder('q')
            ->where('q.result = :result')
            ->setParameter('result', 'new')
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery();
    }
}
