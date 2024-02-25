<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserState>
 *
 * @method UserState|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserState|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserState[]    findAll()
 * @method UserState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserState::class);
    }

    public function save(UserState $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserState $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getStateQuery(User $user): AbstractQuery
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery();
    }
}
