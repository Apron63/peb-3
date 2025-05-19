<?php

declare (strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\WhatsappQueue;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WhatsappQueue>
 *
 * @method WhatsappQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method WhatsappQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method WhatsappQueue[]    findAll()
 * @method WhatsappQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WhatsappQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WhatsappQueue::class);
    }

    public function save(WhatsappQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WhatsappQueue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getWhatsappCreatedToday(): int
    {
        $queryBuilder = $this->createQueryBuilder('w');

         $now = new DateTime();
        return $queryBuilder
            ->select('count(w)')
            ->where('w.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', clone $now->setTime(0, 0, 0))
            ->setParameter('endDate', clone $now->setTime(23, 59, 59))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getWhatsappNotSendedCount(): int
    {
        $queryBuilder = $this->createQueryBuilder('w');

        return $queryBuilder
            ->select('count(w)')
            ->where('w.status != :success')
            ->setParameter('success', 'Успешно')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return WhatsappQueue[]
     */
    public function getWhatsappNotSended(): array
    {
        $queryBuilder = $this->createQueryBuilder('w');

        return $queryBuilder
            ->where('w.status != :success')
            ->setParameter('success', 'Успешно')
            ->getQuery()
            ->getResult();
    }

    public function getWhatsappQuery(?User $user = null, ?string $userName = null, ?string $phone = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('wq');

        if (null !== $user) {
            $queryBuilder
                ->where('wq.createdBy = :user')
                ->setParameter('user', $user);
        }

        if (null !== $userName) {
            $queryBuilder
                ->join('wq.user', 'u')
                ->andWhere('u.fullName LIKE :userName')
                ->setParameter('userName', '%' . $userName . '%');
        }

        if (null !== $phone) {
            $queryBuilder
                ->andWhere('wq.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }

        return $queryBuilder
            ->orderBy('wq.sendedAt', 'desc')
            ->getQuery();
    }
}
