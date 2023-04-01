<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseInfo;
use App\Entity\CourseTheme;
use App\Entity\Profile;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 *
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function save(Course $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Course $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string|null $type
     * @param string|null $profile
     * @param bool $demoOnly
     * @return AbstractQuery
     */
    public function getAllCoursesQuery(
        ?string $type,
        ?string $profile,
        bool $demoOnly = false
    ): AbstractQuery {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select(
                'c.id',
                'c.name',
                'c.shortName',
                'p.name AS profileName',
                '(SELECT count(t) FROM App\Entity\Ticket t WHERE t.course = c.id) AS ticketCnt',
                'IDENTITY (c.profile) AS profileId',
                'c.type',
            )
            ->leftJoin(Profile::class, 'p', Join::WITH, 'p.id = c.profile');


        if (null !== $type) {
            $queryBuilder->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        if (null !== $profile) {
            $queryBuilder->andWhere('c.profile = :profile')
                ->setParameter('profile', $profile);
        }

        $queryBuilder
            ->orderBy('c.name')
            ->setCacheable(true);

        if ($demoOnly) {
            $queryBuilder->andWhere('c.type = :courseDemoValue')
                ->setParameter('courseDemoValue', true);
        }

        return $queryBuilder->getQuery();
    }

    public function getAllCourses(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'IDENTITY (c.profile) AS profileId')
            ->where('c.forDemo = 0')
            ->orderBy('c.name')
            ->setCacheable(true)
            ->getQuery()
            ->enableResultCache()
            ->getArrayResult();
    }

    public function deleteOldTickets(Course $course): void
    {
        $sql = "DELETE FROM ticket WHERE course_id = {$course->getId()}";
        $this->getEntityManager()->getConnection()->executeQuery($sql);        
    }

    /**
     * @param Course $course
     */
    public function prepareCourseClear(Course $course): void
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->delete(CourseInfo::class, 'i')
            ->where('i.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
        unset($queryBuilder);

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->delete(CourseTheme::class, 't')
            ->where('t.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
        unset($queryBuilder);

        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->delete(Ticket::class, 't')
            ->where('t.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
    }
}
