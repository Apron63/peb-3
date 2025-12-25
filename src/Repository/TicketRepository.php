<?php

declare (strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function save(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getTicketCount(Course $course, array $courseThemes): array
    {
        $array = [];
        foreach ($courseThemes as $theme) {
            $query = $this->getEntityManager()
                ->createQuery(
                    "SELECT count(q.id)
                    FROM App\Entity\Questions q
                    WHERE q.course = :course
                    AND q.parentId = :parentId
                ")
                ->setParameter('course', $course->getId())
                ->setParameter('parentId', $theme->getId());

            $result = $query->execute(null, AbstractQuery::HYDRATE_SINGLE_SCALAR);
            $array[$theme->getId()] = $result;
        }

        return $array;
    }

    public function getCourseTickets(Course $course): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.course = :course')
            ->orderBy('t.nom')
            ->setParameter('course', $course)
            ->getQuery()
            ->getArrayResult();
    }

    public function deleteOldTickets(Course $course): void
    {
        // Выберем незавершенные экзамены, у которых билет будет удален.
        $sql = "SELECT id from ticket WHERE course_id = {$course->getId()}";
        $ticketIds = $this->getEntityManager()->getConnection()->executeQuery($sql)->fetchFirstColumn();

        if (count($ticketIds) > 0) {
            $ticketIdsArray = implode(',', $ticketIds);

            // И удалим их.
            $sql = "DELETE from logger WHERE ticket_id IN ({$ticketIdsArray}) AND end_at IS NULL";
            $this->getEntityManager()->getConnection()->executeQuery($sql);
        }

        // Удаляем старые билеты.
        $sql = "DELETE from ticket WHERE course_id = {$course->getId()}";
        $this->getEntityManager()->getConnection()->executeQuery($sql);
    }
}
