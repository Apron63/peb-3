<?php

namespace App\Repository;

use App\Entity\MailingQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailingQueue>
 *
 * @method MailingQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailingQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailingQueue[]    findAll()
 * @method MailingQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailingQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailingQueue::class);
    }

    public function save(MailingQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MailingQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getEmailPortion(int $limit = 1000): array
    {
        $queryBuilder = $this->createQueryBuilder('m');

        return $queryBuilder
            ->where('m.sendedAt IS NULL')
            ->orderBy('m.createdAt')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}