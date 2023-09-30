<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseInfo>
 *
 * @method CourseInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method CourseInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method CourseInfo[]    findAll()
 * @method CourseInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseInfo::class);
    }

    public function save(CourseInfo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CourseInfo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return CourseInfo[]
     */
    public function getCourseInfos(Course $course): array
    {
        return $this->createQueryBuilder('ci')
            ->where('ci.course = :course')
            ->setParameter('course', $course->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CourseInfo[]
     */
    public function getCourseInfoWhereNotEmpty(Course $course): array
    {
        return $this->createQueryBuilder('ci')
            ->where('ci.course = :course')
            ->andWhere('ci.name IS NOT NULL')
            ->setParameter('course', $course->getId())
            ->getQuery()
            ->getResult();
    }

    public function removeCourseInfoForCourse(Course $course): void
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\CourseInfo i WHERE i.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $query->execute();
    }
}
