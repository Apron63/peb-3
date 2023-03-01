<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseTheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseTheme>
 *
 * @method CourseTheme|null find($id, $lockMode = null, $lockVersion = null)
 * @method CourseTheme|null findOneBy(array $criteria, array $orderBy = null)
 * @method CourseTheme[]    findAll()
 * @method CourseTheme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseTheme::class);
    }

    public function save(CourseTheme $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CourseTheme $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getCourseThemes(Course $course)
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.course = :course')
            ->setParameter('course', $course->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Course $course
     */
    public function removeCourseThemeForCourse(Course $course)
    {
        $query = $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\CourseTheme t WHERE t.course = :courseId")
            ->setParameter('courseId', $course->getId());

        $query->execute();
    }
}
