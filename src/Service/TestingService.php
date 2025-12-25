<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Logger;
use App\Entity\Permission;
use App\Entity\Questions;
use App\Entity\Ticket;
use App\Repository\AnswerRepository;
use App\Repository\LoggerRepository;
use App\Repository\PermissionRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;
use DateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TestingService
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly UrlGeneratorInterface $router,
    ) {}

    public function getLogger(Permission $permission, UserInterface $user): Logger
    {
        $logger = $this->loggerRepository->findLastLogger($permission, $user);

        if (null === $logger) {
            $logger = $this->getNewLogger($permission, $user);
        } else {
            $timeSpentNow = new DateTime()->getTimestamp() - $logger->getTimeLastQuestion()->getTimestamp();

            $timeShift = $logger->getTimeLeftInSeconds() - $timeSpentNow;
            if ($timeShift < 0 ) {
                $timeShift = 0;
            }

            $logger
                ->setTimeLeftInSeconds($timeShift)
                ->setTimeLastQuestion(new DateTime());

            $this->loggerRepository->save($logger, true);
        }

        return $logger;
    }

    public function getData(Logger $logger, Permission $permission, ?int $questionId = null): array
    {
        if ($permission->getCourse()->getType() === Course::INTERACTIVE) {
            $data = $this->getDataInteractive($logger, $permission, $questionId);
        } else {
            $data = $this->getDataClassic($logger, $permission, $questionId);
        }

        $data['permissionId'] = $permission->getId();
        $data['permissionLastAccess'] = $permission->getLastAccess()->getTimestamp();
        $data['errorAllowed'] = $logger->getErrorAllowed();
        $data['timeLeft'] = intdiv($logger->getTimeLeftInSeconds(), 60);

        return $data;
    }

    public function ticketProcessing(array $data, int $courseType): string
    {
        $logger = $this->loggerRepository->find($data['loggerId']);
        if (! $logger instanceof Logger) {
            throw new NotFoundHttpException('Logger not found');
        }

        $ticket = $logger->getTicket();
        if (null === $ticket) {
            throw new NotFoundHttpException('Ticket not found for course: ' . $logger->getPermission()->getCourse()->getId());
        }

        $questionNon = $logger->getQuestionNom();
        $ticketsArray = json_decode($ticket->getText()[0]);

        if ($courseType === Course::CLASSIC) {
            $ticketsArray = $this->transformTicketArray($ticketsArray);
        }

        $questionId = $ticketsArray[$questionNon - 1];

        $question = $this->questionsRepository->find($questionId);
        if (! $question instanceof Questions) {
            throw new NotFoundHttpException('Question not found');
        }

        $answers = $this->answerRepository->getAnswers($question);
        $success = true;

        $answersIds = [];
        if (isset($data['answers'])) {
            $answersIds = $data['answers'];
            array_walk($answersIds, function(&$e) {
                $e = (int) $e;
            });

            foreach ($answers as $answer) {
                if (
                    in_array($answer['nom'], $answersIds)
                    && ! $answer['isCorrect']
                ) {
                    $success = false;
                    break;
                }
            }
        }
        else {
            $success = false;
        }

        $hasNotAnswered = false;
        $protocol = $logger->getProtocol();
        $firstNotAnsweredQuestionNom = null;
        $nextNotAnsweredQuestionNom = null;

        foreach ($protocol as $key => $item) {
            if ($item['nom'] === $questionNon && ! empty($answersIds)) {
                $protocol[$key]['qText'] = $question->getDescription();

                if (isset($item['has'])) {
                    $protocol[$key]['has'] = true;
                }

                $protocol[$key]['isCorrect'] = $success;

                foreach ($answersIds as $answerId) {
                    $answerKey = array_search($answerId, array_column($answers, 'nom'));

                    if (false !== $answerKey) {
                        $protocol[$key]['aText'][] = $answers[$answerKey]['description'];
                    }
                }

                foreach ($answers as $answer) {
                    if ($answer['isCorrect']) {
                        $protocol[$key]['aRightText'][] = $answer['description'];
                    }
                }
            }

            if ((isset($protocol[$key]['has'])) && ! $protocol[$key]['has']) {
                if (null === $firstNotAnsweredQuestionNom) {
                    $firstNotAnsweredQuestionNom = $item['nom'];
                }

                if (null === $nextNotAnsweredQuestionNom && $item['nom'] > $questionNon) {
                    $nextNotAnsweredQuestionNom = $item['nom'];
                }
            }
        }

        $hasNotAnswered = false;

        if (null !== $nextNotAnsweredQuestionNom) {
            $logger->setQuestionNom($nextNotAnsweredQuestionNom);
            $hasNotAnswered = true;
        }
        elseif (null !== $firstNotAnsweredQuestionNom) {
            $logger->setQuestionNom($firstNotAnsweredQuestionNom);
            $hasNotAnswered = true;
        }

        $logger->setProtocol($protocol);

        if (! $success) {
            $logger->setErrorActually($logger->getErrorActually() + 1);
        }

        $this->loggerRepository->save($logger, true);

        if ($hasNotAnswered) {
            return  $this->router->generate('app_frontend_testing', ['id' => $data['permissionId']]);
        }
        else {
            return  $this->router->generate('app_frontend_testing_end', ['id' => $data['loggerId']]);
        }
    }

    public function closeLogger(Logger $logger): Logger
    {
        $logger->setEndAt(new DateTime());

        if (
            $logger->getErrorActually() <= $logger->getErrorAllowed()
            && 0 === $this->getSkippedQuestion($logger)
        ) {
            $logger->setResult(true);

            $permission = $logger->getPermission();
            $permission->setStage(Permission::STAGE_FINISHED);

            $this->permissionRepository->save($permission, true);
        }

        $this->loggerRepository->save($logger, true);

        return $logger;
    }

    public function getSkippedQuestion(Logger $logger): int
    {
        $skipped = 0;

        foreach($logger->getProtocol() as $question) {
            if ($question['qText'] === 'Пропущено') {
                $skipped++;
            }
        }

        return $skipped;
    }

    public function getErrorsQuestion(Logger $logger): int
    {
        $errorsActually = 0;

        foreach ($logger->getProtocol() as $item) {
            if (! $item['isCorrect']) {
                $errorsActually++;
            }
        }

        return $errorsActually;
    }

    public function checkPermissionIfFirstTimeTesting(Permission $permission): Permission
    {
        if (! $permission->isSurveyEnabled()) {
            $permission->setSurveyEnabled(true);

            $this->permissionRepository->save($permission, true);
        }

        return $permission;
    }

    public function getFirstSuccessfullyLogger(Permission $permission, UserInterface $user): ?Logger
    {
        return $this->loggerRepository->findFirstSuccessfullyLogger($permission, $user);
    }

    private function getNewLogger(Permission $permission, UserInterface $user): Logger
    {
        $tickets = $this->ticketRepository->getCourseTickets($permission->getCourse());
        $ticketNom = rand(1, count($tickets));

        $ticket = $this->ticketRepository->findOneBy([
            'course' => $permission->getCourse(),
            'nom' => $ticketNom,
        ]);

        if (! $ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket Not Found');
        }

        $questionArray = json_decode($ticket->getText()[0]);

        if ($permission->getCourse()->getType() === Course::CLASSIC) {
            $questionArray = $this->transformTicketArray($questionArray);
        }

        $protocol = [];
        $questionNom = 1;

        foreach($questionArray as $questionId) {
            $protocol[] = [
                'nom' => $questionNom ++,
                'id' => $questionId,
                'has' => false,
                'qText' => 'Пропущено',
                'aText' => [],
                'isCorrect' => false,
            ];
        }

        $logger = new Logger;

        $timeLeft = null === $ticket->getTimeLeft()
            ? Logger::DEFAULT_TIME_LEFT_IN_SECONDS
            : $ticket->getTimeLeft() * 60;

        $logger
            ->setUser($user)
            ->setTicket($ticket)
            ->setErrorActually(0)
            ->setErrorAllowed($ticket->getErrCnt())
            ->setBeginAt(new DateTime())
            ->setQuestionNom(1)
            ->setProtocol($protocol)
            ->setResult(false)
            ->setPermission($permission)
            ->setTimeLastQuestion($logger->getBeginAt())
            ->setTimeLeftInSeconds($timeLeft);

        $this->loggerRepository->save($logger, true);

        return $logger;
    }

    private function getDataInteractive(Logger $logger, Permission $permission, ?int $questionId = null): array
    {
        $questionsArray = json_decode($logger->getTicket()->getText()[0]);

        if (null === $questionId ) {
            $questionNom = $logger->getQuestionNom();
            $questionId = $questionsArray[$questionNom - 1];
        }
        else {
            $questionNom = array_search($questionId, $questionsArray) + 1;
            $logger->setQuestionNom($questionNom);

            $this->loggerRepository->save($logger, true);
        }

        $question = $this->questionsRepository->find($questionId);
        if (! $question instanceof Questions) {
            throw new NotFoundHttpException('Question not found');
        }

        $leftSeconds = $logger->getTimeLeftInSeconds() % 60;
        $leftMinutes = ($logger->getTimeLeftInSeconds() - $leftSeconds) / 60;

        $answers = $this->answerRepository->findBy(['question' => $question], ['nom' => 'asc']);
        $dataAnswers = [];

        foreach($answers as $answer) {
            $dataAnswers[] = [
                'nom' => $answer->getNom(),
                'text' => $answer->getDescription(),
                'isCorrect' => $answer->isCorrect(),
            ];
        }

        $paginator = [];
        $nom = 1;
        $protocol = $logger->getProtocol();

        foreach ($questionsArray as $questionId) {
            $paginator[$questionId] = [
                'id' => $questionId,
                'nom' => $nom,
                'answered' => $protocol[$nom - 1]['has'],
            ];

            $nom++;
        }

        return [
            'url' =>  $this->router->generate('app_frontend_testing_next_step', ['id' => $permission->getId()]),
            'finalUrl' =>  $this->router->generate('app_frontend_testing_end', ['id' => $logger->getId()]),
            'loggerId' => $logger->getId(),
            'nom' => $questionNom,
            'text' => $question->getDescription(),
            'timeLeftTotal' => (new Datetime())->getTimestamp() + $logger->getTimeLeftInSeconds(),
            'timeLeftMinutes' => $leftMinutes,
            'timeLeftSeconds' => $leftSeconds,
            'type' => $question->getType(),
            'answers' => $dataAnswers,
            'paginator' => $paginator,
        ];
    }

    private function getDataClassic(Logger $logger, Permission $permission, ?int $questionId = null): array
    {
        $questionsArray = json_decode($logger->getTicket()->getText()[0], JSON_OBJECT_AS_ARRAY);

        if (null === $questionId ) {
            $questionNom = $logger->getQuestionNom();
            $questionId = $this->getTicketId(
                $questionsArray,
                $logger->getQuestionNom() - 1
            );
        }
        else {
            $questionNom = 1;
            foreach ($questionsArray as $themes) {
                foreach ($themes as $ticketQuestionId) {
                    if ($ticketQuestionId === $questionId) {
                        break;
                    }
                    else {
                        $questionNom++;
                    }
                }
            }

            $logger->setQuestionNom($questionNom);

            $this->loggerRepository->save($logger, true);
        }

        $question = $this->questionsRepository->find($questionId);
        if (! $question instanceof Questions) {
            throw new NotFoundHttpException('Question not found');
        }

        $leftSeconds = $logger->getTimeLeftInSeconds() % 60;
        $leftMinutes = ($logger->getTimeLeftInSeconds() - $leftSeconds) / 60;

        $answers = $this->answerRepository->findBy(['question' => $question], ['nom' => 'asc']);
        $dataAnswers = [];

        foreach($answers as $answer) {
            $dataAnswers[] = [
                'nom' => $answer->getNom(),
                'text' => $answer->getDescription(),
                'isCorrect' => $answer->isCorrect(),
            ];
        }

        $paginator = [];
        $nom = 1;
        $protocol = $logger->getProtocol();

        foreach ($questionsArray as $theme) {
            foreach ($theme as $questionId) {
                $paginator[$questionId] = [
                    'id' => $questionId,
                    'nom' => $nom,
                    'answered' => $protocol[$nom - 1]['has'],
                ];

                $nom++;
            }
        }

        return [
            'url' =>  $this->router->generate('app_frontend_testing_next_step', ['id' => $permission->getId()]),
            'finalUrl' =>  $this->router->generate('app_frontend_testing_end', ['id' => $logger->getId()]),
            'loggerId' => $logger->getId(),
            'nom' => $logger->getQuestionNom(),
            'text' => $question->getDescription(),
            'timeLeftTotal' => (new Datetime())->getTimestamp() + $logger->getTimeLeftInSeconds(),
            'timeLeftMinutes' => $leftMinutes,
            'timeLeftSeconds' => $leftSeconds,
            'type' => $question->getType(),
            'answers' => $dataAnswers,
            'paginator' => $paginator,
        ];
    }

    private function getTicketId(array $ticketData, int $questionNom): ?int
    {
        $tmpNom = 0;

        foreach($ticketData as $theme) {
            foreach($theme as $item) {
                if ($questionNom === $tmpNom ++) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function transformTicketArray($ticketArray): array
    {
        $index = 0;
        $result = [];

        foreach ($ticketArray as $theme) {
            foreach ($theme as $questionId) {
                $result[$index++] = $questionId;
            }
        }

        return $result;
    }
}
