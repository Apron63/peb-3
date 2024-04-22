<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Module;
use App\Entity\ModuleSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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

    /**
     * @return ModuleSection[]
     */
    public function getModuleSectionsListByCourse(Course $course): array
    {
        return $this->createQueryBuilder('section')
            ->join(Module::class, 'module', Join::WITH, 'module.id = section.module')
            ->where('module.course = :course')
            ->setParameter('course', $course)
            ->orderBy('module.sortOrder, section.id')
            ->getQuery()
            ->getResult();
    }

    public function removeModuleSection(Module $module): void
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT ms.id FROM App\Entity\ModuleSection ms WHERE ms.module = :moduleId")
            ->setParameter('moduleId', $module->getId());
        $moduleSectionIds = $query->execute();

        $moduleSectionIds = array_map(function($e) {
            return $e['id'];
        }, $moduleSectionIds);

        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\ModuleSectionPage msp WHERE msp.section IN (:moduleSectionIds)")
            ->setParameter('moduleSectionIds', $moduleSectionIds);
        $moduleSectionIds = $query->execute();

        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\ModuleSection ms WHERE ms.module = :moduleId')
            ->setParameter('moduleId', $module->getId());
        $query->execute();
    }
}
