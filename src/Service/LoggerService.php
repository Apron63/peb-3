<?php

namespace App\Service;

use App\Entity\Answer;
use App\Entity\Course;
use App\Entity\Logger;
use App\Entity\Permission;
use App\Entity\Questions;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\LoggerRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonException;
use Symfony\Component\Security\Core\User\UserInterface;

class LoggerService
{
    private EntityManagerInterface $em;

    /**
     * LoggerService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(
        readonly LoggerRepository $loggerRepository,
        EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Course $course
     * @param UserInterface $user
     * @param bool $enableCreate
     * @return Logger|null
     * @throws Exception
     */
    public function checkIfOpenLoggerExists(Course $course, UserInterface $user, bool $enableCreate = false): ?Logger
    {
        $logger = $this->em->getRepository(Logger::class)
            ->getExistingLogger($course, $user);

        // Создаем новый логгер если его не существует или если к логгеру не привязан тикет.
        /** @var User $user */
        if (
            (!$logger && $enableCreate)
            || ($logger && null === $logger->getTicket())
        ) {
            // Select a ticket count.
            $ticketCount = $this->em->getRepository(Ticket::class)
                ->getTotalTicketCount($course);
            if ($ticketCount > 0) {
                $ticketNom = random_int(1, $ticketCount);
                $ticket = $this->em->getRepository(Ticket::class)
                    ->findOneBy(['course' => $course->getId(), 'nom' => $ticketNom]);
                if ($ticket) {
                    $logger = (new Logger())
                        ->setUser($user)
                        ->setCourse($course)
                        ->setBeginAt(new DateTime())
                        ->setTicket($ticket)
                        ->setErrorAllowed((int)$ticket->getErrCnt())
                        ->setErrorActually(0);

                    $logger = $this->fillLoggerProtocol($logger);
                    $this->loggerRepository->save($logger, true);
                }
            }
        }

        return $logger;
    }

    /**
     * @param Course $course
     * @param User $user
     * @return Logger|null
     */
    public function getLastLoggerForUser(Course $course, User $user): ?Logger
    {
        return $this->em->getRepository(Logger::class)
            ->getExistingLogger($course, $user);
    }

    /**
     * @param Logger $logger
     * @param int $nom
     * @return array
     * @throws JsonException
     */
    public function getLoggerInfoByNom(Logger $logger, int $nom): array
    {
        $data = [];
        $ticketInfo = $logger->getTicket()->getText();
        $questionArray = json_decode($ticketInfo[0], true, 512, JSON_THROW_ON_ERROR);
        if (count($questionArray) > 0) {
            $cnt = 1;
            foreach ($questionArray as $theme) {
                foreach ($theme as $questionId) {
                    if ($cnt === $nom) {
                        break 2;
                    }
                    $cnt++;
                }
            }

            $question = $this->em->getRepository(Questions::class)
                ->findOneBy(['id' => $questionId]);
            if ($question) {
                $answerArray = [];
                foreach ($question->getAnswers() as $answer) {
                    $answerArray[] = [
                        'text' => $answer->getDescription(),
                        'id' => $answer->getId(),
                        'nom' => $answer->getNom(),
                    ];
                }

                $data[$nom] = [
                    'questionId' => $questionId,
                    'questionText' => $question->getDescription(),
                    'result' => null,
                    'answers' => $answerArray,
                ];
            }
        }

        return $data;
    }

    /**
     * @param Logger $logger
     * @param int $answerId
     * @param int $questionNom
     * @return Logger
     * @throws JsonException
     */
    public function checkForAnswer(Logger $logger, int $answerId, int $questionNom): Logger
    {
        /** @var Answer $answer */
        $answer = $this->em->getRepository(Answer::class)->findOneBy(['id' => $answerId]);
        $result = $answer !== null && $answer->isCorrect();
        if (!$result) {
            $logger->setErrorActually($logger->getErrorActually() + 1);
        }
        // Сохранили результат ответа
        $row = $logger->getProtokol()[$questionNom];
        //$key = array_key_last($protocol);
        $row['result'] = $result;
        $row['aText'] = $answer->getDescription();
        // Проверяем не последний вопрос
        $questionCnt = $this->em->getRepository(Ticket::class)
            ->getQuestionCount($logger->getTicket());
        if ($questionNom >= $questionCnt) {
            $logger->setEndAt(new \DateTime());
            $logger->setResult($logger->getErrorActually() <= $logger->getErrorAllowed());
        }
        // Сохранили логгер
        $protocol = $logger->getProtokol();
        $protocol[$questionNom] = $row;
        $logger->setProtokol($protocol);
        $this->em->persist($logger);
        $this->em->flush();
        return $logger;
    }

    /**
     * @param Logger $logger
     * @return Logger
     * @throws JsonException
     */
    private function fillLoggerProtocol(Logger $logger): Logger
    {
        $questionInfo = [];
        if ($logger->getTicket()) {
            $questionArray = json_decode($logger->getTicket()->getText()[0], true, 512, JSON_THROW_ON_ERROR);
        } else {
            $questionArray = $logger->getProtokol();
        }

        $qNom = 1;
        foreach (array_values($questionArray) as $questions) {
            foreach ($questions as $row) {
                $tmp = [];
                $question = $this->em->getRepository(Questions::class)
                    ->findOneBy(['id' => $row]);
                if (!$question) {
                    continue;
                }
                $tmp['qId'] = $question->getId();
                $tmp['qText'] = $question->getDescription();
                $tmp['aText'] = null;
                $tmp['result'] = null;
                $questionInfo[$qNom++] = $tmp;
            }
        }
        $logger->setProtokol($questionInfo);
        return $logger;
    }

    /**
     * @param Logger $logger
     * @return int|null
     */
    public function getLastQuestionFromLogger(Logger $logger): ?int
    {
        $protocol = $logger->getProtokol();
        $cnt = 1;
        foreach ($protocol as $row) {
            if (null === $row['result']) {
                return $cnt;
            }
            $cnt++;
        }
        return null;
    }

    /**
     * @param User $user
     * @return Logger|null
     */
    public function checkIfUserHasNotCompletedLogger(UserInterface $user): ?Logger
    {
        $courseQuery = $this->em->getRepository(Permission::class)->getCourseUserQuery($user)->getResult();
        if (count($courseQuery) > 0) {
            foreach ($courseQuery as $courseRow) {
                $course = $this->em->getRepository(Course::class)->find($courseRow['id']);
                if ($course) {
                    $lastLogger = $this->getLastLoggerForUser($course, $user);
                    if ($lastLogger && null === $lastLogger->getEndAt()) {
                        return $lastLogger;
                    }
                }
            }
        }
        return null;
    }
}
