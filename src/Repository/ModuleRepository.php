<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 *
 * @method Module|null find($id, $lockMode = null, $lockVersion = null)
 * @method Module|null findOneBy(array $criteria, array $orderBy = null)
 * @method Module[]    findAll()
 * @method Module[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    public function save(Module $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Module $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getModules(Course $course): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.course = :course')
            ->setParameter('course', $course->getId())
            ->getQuery()
            ->getArrayResult();
    }

    public function removeModuleFromCourse(Course $course): void
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT m.id FROM App\Entity\Module m WHERE m.course = :courseId")
            ->setParameter('courseId', $course->getId());
        $mIds = $query->execute();

        $mIds = array_map(function($e) {
                return $e['id'];
            }, $mIds);
        
        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\ModuleSection ms WHERE ms.module IN (:mIds)')
            ->setParameter('mIds', $mIds);
        $query->execute();
        
        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\Module m WHERE m.id IN (:mIds)')
            ->setParameter('mIds', $mIds);
        $query->execute();
    }
}
