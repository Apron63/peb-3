<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Profile;
use App\Entity\Survey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Survey>
 */
class SurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Survey::class);
    }

    public function save(Survey $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Survey $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLastSurvey(User $user, Course $course): Survey
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $survey = $queryBuilder
            ->where('s.user = :user AND s.course = :course')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('course', $course)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $survey) {
            $survey = new Survey();
        }

        return $survey;
    }

    /**
     * @return Survey[]
     */
    public function getDataForReport(array $data): array
    {
        $queryBuilder = $this->createQueryBuilder('s');

        if (null !== $data['startPeriod']) {
            $queryBuilder
                ->andWhere('s.createdAt >= :startPeriod')
                ->setParameter('startPeriod', $data['startPeriod']);
        }

        if (null !== $data['endPeriod']) {
            $queryBuilder
                ->andWhere('s.createdAt <= :endPeriod')
                ->setParameter('endPeriod', $data['endPeriod']->setTime(23, 59, 59));
        }

        if (! empty($data['profilesArr'])) {
            $profileIds = array_map(
                static fn(Profile $profile) => $profile->getId(),
                $data['profilesArr'],
            );

            $queryBuilder
                ->join(Course::class, 'c', Join::WITH, 'c.id = s.course')
                ->join(Profile::class, 'p', Join::WITH, 'c.profile = p.id')
                ->andWhere('p.id IN (:profileIds)')
                ->setParameter('profileIds', $profileIds);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
