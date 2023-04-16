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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TestingService
{
    public function __construct(
        readonly LoggerRepository $loggerRepository,
        readonly TicketRepository $ticketRepository,
        readonly QuestionsRepository $questionsRepository,
        readonly AnswerRepository $answerRepository,
        readonly PermissionRepository $permissionRepository,
        readonly UrlGeneratorInterface $router,
    ) {}

    public function getLogger(Permission $permission, UserInterface $user): Logger
    {
        $logger = $this->loggerRepository->findLastLogger($permission, $user);

        if ( 
            null === $logger
        ) {
            $logger = $this->getNewLogger($permission, $user);
        } else {
            $timeSpentNow = (new \DateTime)->getTimestamp() - $logger->getTimeLastQuestion()->getTimestamp();

            $timeShift = $logger->getTimeLeftInSeconds() - $timeSpentNow;
            if ($timeShift < 0 ) {
                $timeShift = 0;
            }

            $logger
                ->setTimeLeftInSeconds($timeShift)
                ->setTimeLastQuestion(new \DateTime());

            $this->loggerRepository->save($logger, true);
        }

        return $logger;
    }

    public function getData(Logger $logger, Permission $permission): array
    {
        if ($permission->getCourse()->getType() === Course::INTERACTIVE) {
            $data = $this->getDataInteractive($logger, $permission);
        } else {
            $data = $this->getDataClassic($logger, $permission);
        }

        return $data;
    }

    public function ticketProcessing(array $data): string
    {
        $logger = $this->loggerRepository->find($data['loggerId']);
        if (! $logger instanceof Logger) {
            throw new NotFoundHttpException('Logger not found');
        }

        $ticketsArray = json_decode($logger->getTicket()->getText()[0]);
        $ticketId = $ticketsArray[$logger->getQuestionNom() - 1];

        $question = $this->questionsRepository->find($ticketId);
        if (! $question instanceof Questions) {
            throw new NotFoundHttpException('Question not found');
        }

        $answers = $this->answerRepository->getAnswers($question);
        $success = true;
        $answersIds = $data['answers'];
        array_walk($answersIds, function(&$e) {
            $e = (int)$e;
        });

        foreach($answers as $answer) {
            if (
                in_array($answer['nom'], $answersIds)
                && !$answer['isCorrect']
            ) {
                $success = false;
                break;
            }
        }

        $protocol = $logger->getProtocol();

        foreach($protocol as $key => $item) {
            if ($item['nom'] === $logger->getQuestionNom()) {
                $protocol[$key]['qText'] = $question->getDescription();
                $protocol[$key]['isCorrect'] = $success;
                    foreach($answersIds as $answerId) {
                        $answerKey = array_search($answerId, array_column($answers, 'nom'));

                        if (false !== $answerKey) {
                            $protocol[$key]['aText'][] = $answers[$answerKey]['description'];
                        }
                    }
                break;
            }
        }

        $logger->setProtocol($protocol);

        if (! $success) {
            $logger->setErrorActually($logger->getErrorActually() + 1);
        }

        $this->loggerRepository->save($logger, true);

        if (isset($ticketsArray[$logger->getQuestionNom()])) {
            $logger
                ->setQuestionNom($logger->getQuestionNom() + 1);

            $this->loggerRepository->save($logger, true);

            return  $this->router->generate('app_frontend_testing', ['id' => $data['permissionId']]);
        } else {
            return  $this->router->generate('app_frontend_testing_end', ['id' => $data['loggerId']]);
        }
    }

    public function closeLogger(Logger $logger): Logger
    {
        $logger->setEndAt(new \DateTime());

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
            if ($question['qText'] === 'Пропущено'){
                $skipped++;
            }
        }

        return $skipped;
    }

    public function getFirstSuccesfullyLogger(Permission $permission, UserInterface $user): Logger
    {
        $logger = $this->loggerRepository->findFirstSuccessfullyLogger($permission, $user);

        return $logger;
    }

    private function getNewLogger(Permission $permission, UserInterface $user): Logger
    {
        $tickets = $this->ticketRepository->getCourseTickets($permission->getCourse());
        $ticketNom = rand(1, count($tickets));

        $ticket = $this->ticketRepository->findOneBy([
            'course' => $permission->getCourse(),
            'nom' => $ticketNom,
        ]);

        if (!$ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket Not Found');
        }

        $questionArray = json_decode($ticket->getText()[0]);
        $protocol = [];
        $questionNom = 1;

        foreach($questionArray as $questionId) {
            $protocol[] = [
                'nom' => $questionNom ++,
                'qText' => 'Пропущено',
                'aText' => [],
                'isCorrect' => false,
            ];
        }

        $logger = new Logger;
        
        $logger
            ->setUser($user)
            ->setTicket($ticket)
            ->setErrorActually(0)
            ->setErrorAllowed($ticket->getErrCnt())
            ->setBeginAt(new \DateTime())
            ->setQuestionNom(1)
            ->setProtocol($protocol)
            ->setResult(false)
            ->setPermission($permission)
            ->setTimeLastQuestion($logger->getBeginAt())
            ->setTimeLeftInSeconds(Logger::DEFAULT_TIME_LEFT_IN_SECONDS);

        $this->loggerRepository->save($logger, true);

        return $logger;
    }

    private function getDataInteractive(Logger $logger, Permission $permission): array
    {
        $ticketId = json_decode($logger->getTicket()->getText()[0])[$logger->getQuestionNom() - 1];

        $question = $this->questionsRepository->find($ticketId);
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

        return [
            'url' =>  $this->router->generate('app_frontend_testing_next_step', ['id' => $permission->getId()]),
            'finalUrl' =>  $this->router->generate('app_frontend_testing_end', ['id' => $logger->getId()]),
            'loggerId' => $logger->getId(),
            'nom' => $logger->getQuestionNom(),
            'text' => $question->getDescription(),
            'timeLeftTotal' => (new \Datetime)->getTimestamp() + $logger->getTimeLeftInSeconds(),
            'timeLeftMinutes' => $leftMinutes,
            'timeLeftSeconds' => $leftSeconds,
            'type' => $question->getType(),
            'answers' => $dataAnswers,
        ];
    }
    
    private function getDataClassic(Logger $logger, Permission $permission): array
    {
        $ticketId = $this->getTicketId(
            json_decode($logger->getTicket()->getText()[0], JSON_OBJECT_AS_ARRAY),
            $logger->getQuestionNom() - 1
        );

        $question = $this->questionsRepository->find($ticketId);
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

        return [
            'url' =>  $this->router->generate('app_frontend_testing_next_step', ['id' => $permission->getId()]),
            'finalUrl' =>  $this->router->generate('app_frontend_testing_end', ['id' => $logger->getId()]),
            'loggerId' => $logger->getId(),
            'nom' => $logger->getQuestionNom(),
            'text' => $question->getDescription(),
            'timeLeftTotal' => (new \Datetime)->getTimestamp() + $logger->getTimeLeftInSeconds(),
            'timeLeftMinutes' => $leftMinutes,
            'timeLeftSeconds' => $leftSeconds,
            'type' => $question->getType(),
            'answers' => $dataAnswers,
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
}
