<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\ModuleInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleInfo>
 *
 * @method ModuleInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleInfo[]    findAll()
 * @method ModuleInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleInfo::class);
    }

    public function save(ModuleInfo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ModuleInfo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getModuleInfos(Course $course): array
    {
        return $this->createQueryBuilder('mi')
            ->where('mi.course = :course')
            ->setParameter('course', $course->getId())
            ->getQuery()
            ->getArrayResult();
    }
}
