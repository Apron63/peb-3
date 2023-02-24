<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Questions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Questions>
 *
 * @method Questions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Questions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Questions[]    findAll()
 * @method Questions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questions::class);
    }

    public function save(Questions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Questions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getNextNumber(Course $course, int $parentId): int
    {
        $question = $this->createQueryBuilder('q')
            ->where('q.course = :course')
            ->andWhere('q.parentId = :parentId')
            ->setParameters([
                'course' => $course->getId(),
                'parentId' => $parentId,
            ])
            ->orderBy('q.nom', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $question) {
            $result = 1;
        } else {
            $result = $question->getNom() + 1;
        }

        return $result;
    }

    public function getQuestionQuery(Course $course, int $parentId): AbstractQuery
    {
        return $this->createQueryBuilder('q')
            ->where('q.course = :course')
            ->andWhere('q.parentId = :parentId')
            ->setParameters([
                'course' => $course->getId(),
                'parentId' => $parentId,
            ])
            ->orderBy('q.nom')
            ->getQuery();
    }
}
