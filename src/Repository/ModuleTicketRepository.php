<?php

namespace App\Repository;

use App\Entity\Module;
use App\Entity\ModuleTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModuleTicket>
 *
 * @method ModuleTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleTicket[]    findAll()
 * @method ModuleTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleTicket::class);
    }

    public function save(ModuleTicket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ModuleTicket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getNextNumber(Module $module): int
    {
        $result = 1;

        $ticket = $this->createQueryBuilder('t')
            ->where('t.module = :module')
            ->setParameter('module', $module->getId())
            ->orderBy('t.ticketNom', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $ticket) {
            $result = $ticket->getTicketNom() + 1;
        }

        return $result;
    }

    public function getTickets(Module $module): array
    {
        return $this->createQueryBuilder('t')
        ->where('t.module = :module')
        ->setParameter('module', $module->getId())
        ->orderBy('t.ticketNom')
        ->getQuery()
        ->getResult();
    }

    public function deleteOldTickets(Module $module): void
    {
        $sql = "DELETE from module_ticket WHERE module_id = {$module->getId()}";
        $this->getEntityManager()->getConnection()->executeQuery($sql);        
    }
}
