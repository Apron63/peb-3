<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Loader;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Loader>
 *
 * @method Loader|null find($id, $lockMode = null, $lockVersion = null)
 * @method Loader|null findOneBy(array $criteria, array $orderBy = null)
 * @method Loader[]    findAll()
 * @method Loader[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoaderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loader::class);
    }

    public function save(Loader $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Loader $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLoaderForUser(User $user): array
    {
        $queryBuilder = 
            $this->createQueryBuilder('l')
            ->where('l.createdBy = :user')
            ->setParameter('user', $user);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
    
    /**
     * @return Loader[]
     */
    public function getLoaderforCheckedUser(User $user): array
    {
        $queryBuilder = 
            $this->createQueryBuilder('l')
            ->where('l.createdBy = :user')
            ->andWhere('l.checked = 1')
            ->setParameter('user', $user);

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function setAllCheckBoxValue(User $user, string $action): void
    {
        $value = false;

        if ($action === 'select') {
            $value = true;
        }

        $query = $this
            ->getEntityManager()
            ->createQuery('UPDATE App\Entity\Loader l set l.checked = :value where l.createdBy = :user')
            ->setParameters([
                'value' => $value,
                'user' => $user,
            ]);

        $query->execute();
    }

    public function clearLoaderForUser(User $user): void
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('DELETE FROM  App\Entity\Loader l where l.createdBy = :user')
            ->setParameter('user', $user);

        $query->execute();
    }

    public function checkIfLoaderIsEmpty(User $user): bool
    {
        $query = $this
            ->getEntityManager()
            ->createQuery('SELECT COUNT(l.id) FROM App\Entity\Loader l where l.createdBy = :user AND l.checked = 1')
            ->setParameter('user', $user);
        
        return $query->getSingleScalarResult() > 0;
    }
}
