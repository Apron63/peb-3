<?php

namespace App\Repository;

use App\Entity\QueryJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QueryJob>
 *
 * @method QueryJob|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueryJob|null findOneBy(array $criteria, array $orderBy = null)
 * @method QueryJob[]    findAll()
 * @method QueryJob[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueryJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueryJob::class);
    }

    public function save(QueryJob $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QueryJob $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPaginatedQuery(): Query
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.startAt')
            ->getQuery();
    }

//    /**
//     * @return QueryJob[] Returns an array of QueryJob objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?QueryJob
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
