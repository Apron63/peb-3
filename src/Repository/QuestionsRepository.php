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

    public function getNextNumber(Course $course, ?int $parentId): int
    {
        $queryBuilder = $this->createQueryBuilder('q')
            ->where('q.course = :course')
            ->setParameter('course', $course);

        if (null !== $parentId) {
            $queryBuilder->andWhere('q.parentId = :parentId')
                ->setParameter('parentId', $parentId);
        }
        $question = $queryBuilder->orderBy('q.nom', 'desc')
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

    public function getQuestionQuery(Course $course, ?int $parentId = null): AbstractQuery
    {
        $queryBuilder = $this->createQueryBuilder('q')
            ->where('q.course = :course')
            ->setParameter('course', $course);
        
        if (null !== $parentId) {
            $queryBuilder->andWhere('q.parentId = :parentId')
                ->setParameter('parentId', $parentId);
        }

        return $queryBuilder
            ->orderBy('q.nom')
            ->getQuery();
    }

    public function getQuestionIds(Course $course, int $parentId): array
    {
        $sql = "SELECT id FROM questions WHERE course_id = {$course->getId()} AND parent_id = {$parentId} ORDER BY nom";

        return $this->getEntityManager()->getConnection()->fetchFirstColumn($sql);
    }

    public function removeQuestionsForCourse(Course $course)
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT q.id FROM App\Entity\Questions q WHERE q.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $qIds = $query->execute();
        $qIds = array_map(function($e) {
                return $e['id'];
            }, $qIds);
        
        $query = $this->getEntityManager()
            ->createQuery('DELETE FROM App\Entity\Answer a WHERE a.question IN (:qIds)')
            ->setParameter('qIds', $qIds);

        $qIds = $query->execute();
        
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Questions q WHERE q.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $query->execute();
    }
}
