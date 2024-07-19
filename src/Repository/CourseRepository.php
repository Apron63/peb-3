<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Course;
use App\Entity\CourseInfo;
use App\Entity\CourseTheme;
use App\Entity\Profile;
use App\Entity\Questions;
use App\Entity\Ticket;
use App\Service\XmlCourseDownload\CourseThemeDTO;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\NullLogger;
use Symfony\Component\String\UnicodeString;

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

    public function getAllCoursesQuery(
        ?string $type,
        ?string $profile,
        ?string $name,
        bool $demoOnly = false
    ): AbstractQuery {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select(
                'c.id',
                'c.name',
                'c.shortName',
                'c.image',
                'c.forDemo',
                'p.name AS profileName',
                '(SELECT count(t) FROM App\Entity\Ticket t WHERE t.course = c.id) AS ticketCnt',
                'IDENTITY (c.profile) AS profileId',
                'c.type',
                'c.autonumerationCompleted',
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

        if (null !== $name) {
            $queryBuilder->andWhere('c.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }

        $queryBuilder
            ->orderBy('c.name')
            ->setCacheable(true);

        if ($demoOnly) {
            $queryBuilder->andWhere('c.forDemo = :courseDemoValue')
                ->setParameter('courseDemoValue', true);
        }

        return $queryBuilder->getQuery();
    }

    public function getAllCourses(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.shortName', 'IDENTITY (c.profile) AS profileId')
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

    public function prepareCourseClear(Course $course): void
    {
        $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->delete(CourseInfo::class, 'ci')
            ->where('ci.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();

        $themes = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('ct')
            ->from(CourseTheme::class, 'ct')
            ->where('ct.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();

        foreach ($themes as $theme) {
            $questions = $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->select('q.id')
                ->from(Questions::class, 'q')
                ->where('q.course = :course')
                ->andWhere('q.parentId = :parentId')
                ->setParameter('course', $course)
                ->setParameter('parentId', $theme->getId())
                ->getQuery()
                ->getScalarResult();

            $questionsIds = array_map(
                fn(array $question) => $question['id'],
                $questions,
            );

            $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->delete(Answer::class, 'a')
                ->where('a.question in (:questionsIds)')
                ->setParameter('questionsIds', $questionsIds)
                ->getQuery()
                ->getResult();

            $this
                ->getEntityManager()
                ->createQueryBuilder()
                ->delete(Questions::class, 'q')
                ->where('q.id in (:questionsIds)')
                ->setParameter('questionsIds', $questionsIds)
                ->getQuery()
                ->getResult();
        }

        $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->delete(CourseTheme::class, 'theme')
            ->where('theme.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();

        $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->delete(Ticket::class, 'ticket')
            ->where('ticket.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param CourseThemeDTO[] $themes
     * @throws Exception
     */
    public function saveCourseToDb(int $courseId, array $themes): void
    {
        $this->getEntityManager()->getConnection()->getConfiguration()->setMiddlewares([
            new Middleware(new NullLogger())
        ]);

        $this->getEntityManager()->beginTransaction();

        try {
            foreach ($themes as $theme) {
                $this->getEntityManager()->getConnection()->executeQuery("
                    INSERT INTO course_theme (id, course_id, name, description)
                    VALUES (NULL, '{$courseId}', '{$theme->name}', '{$theme->description}')
                ");

                $themeId = $this->getEntityManager()->getConnection()->lastInsertId();

                // Вопросы
                foreach ($theme->questions as $question) {
                    $this->getEntityManager()->getConnection()->executeQuery("
                        INSERT INTO questions (id, course_id, parent_id , description, type, help, nom)
                        VALUES (NULL, {$courseId}, {$themeId}, '{$question->description}', {$question->type}, '{$question->help}', {$question->nom})
                    ");

                    $questionId = $this->getEntityManager()->getConnection()->lastInsertId();

                    // Ответы
                    foreach ($question->answers as $answer) {
                        $status = (int) $answer->isCorrect;

                        $this->getEntityManager()->getConnection()->executeQuery("
                            INSERT INTO answer (id, question_id , description, is_correct, nom)
                            VALUES (NULL, {$questionId}, '{$answer->description}', {$status}, {$answer->nom})
                        ");
                    }
                }
            }

            // Материалы
            $firstTheme = current($themes);
            foreach ($firstTheme->materials as $material) {
                $shortMaterialName = $this->shrinkMaterialName($material->name);

                $this->getEntityManager()->getConnection()->executeQuery("
                    INSERT INTO course_info (id, course_id, name, file_name)
                    VALUES (NULL, {$courseId}, '{$shortMaterialName}', '{$material->filename}')
                ");
            }

            $this->getEntityManager()->commit();
        } catch(Exception $e) {
            $this->getEntityManager()->rollback();

            throw($e);
        }
    }

    /**
     * @param CourseThemeDTO[] $themes
     */
    public function saveQuestionsToDb(int $courseId, array $themes): void
    {
        $this->getEntityManager()->getConnection()->getConfiguration()->setMiddlewares([
            new Middleware(new NullLogger())
        ]);

        $theme = current($themes);

        foreach ($theme->questions as $question) {
            $this->getEntityManager()->getConnection()->executeQuery("
                INSERT INTO questions (id, course_id, parent_id , description, type, help, nom)
                VALUES (NULL, {$courseId}, NULL, '{$question->description}', {$question->type}, '{$question->help}', {$question->nom})
            ");
            $questionId = $this->getEntityManager()->getConnection()->lastInsertId();

            // Ответы
            foreach ($question->answers as $answer) {
                $status = (int) $answer->isCorrect;

                $this->getEntityManager()->getConnection()->executeQuery("
                    INSERT INTO answer (id, question_id , description, is_correct, nom)
                    VALUES (NULL, {$questionId}, '{$answer->description}', {$status}, {$answer->nom})
                ");
            }
        }
    }

    // TODO Вынести в сервис запись данных
    public function shrinkMaterialName(string $longName): string
    {
        $result = $longName;

        if (mb_strlen($longName) > 1000) {

            $shortName = (new UnicodeString($longName))->slice(0, 1000);
            $lastSpacePosition = $shortName->indexOfLast(' ');

            $result = $shortName->slice(0, $lastSpacePosition)->toString();
        }

        return $result;
    }
}
