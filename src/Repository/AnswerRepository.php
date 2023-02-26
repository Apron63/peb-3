<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Questions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Answer>
 *
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[]    findAll()
 * @method Answer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    public function save(Answer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Answer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAnswers(Questions $question): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.question = :question')
            ->setParameter('question', $question->getId())
            ->getQuery()
            ->getArrayResult();
    }

    public function getNextNom(Questions $question): int
    {
        $answer = $this->createQueryBuilder('a')
            ->where('a.question = :question')
            ->setParameter('question', $question->getId())
            ->orderBy('a.nom', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $answer) {
            $result = 1;
        } else {
            $result = $answer->getNom() + 1;
        }

        return $result;
    }
}
