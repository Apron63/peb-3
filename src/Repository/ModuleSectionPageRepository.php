<?php

namespace App\Repository;

use App\Entity\ModuleSection;
use App\Entity\ModuleSectionPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleSectionPage>
 *
 * @method ModuleSectionPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleSectionPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleSectionPage[]    findAll()
 * @method ModuleSectionPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleSectionPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleSectionPage::class);
    }

    public function save(ModuleSectionPage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ModuleSectionPage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /***
     * @return ModuSectionPage[]
     */
    public function getmoduleSectionPages(ModuleSection $moduleSection): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.section = :moduleSection')
            ->setParameter('moduleSection', $moduleSection)
            ->getQuery()
            ->getResult();
    }

    public function removeModuleSectionPage(ModuleSection $moduleSection): void
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\ModuleSectionPage msp WHERE msp.section = :moduleSectionId")
            ->setParameter('moduleSectionId', $moduleSection->getId());
        $query->execute();
    }
}
