<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\ModuleSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleSection>
 *
 * @method ModuleSection|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleSection|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleSection[]    findAll()
 * @method ModuleSection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleSection::class);
    }

    public function save(ModuleSection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ModuleSection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
