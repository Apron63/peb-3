<?php

namespace App\Service;

use DateTime;
use App\Entity\Course;
use App\Entity\DemoLogger;
use App\Entity\Logger;
use App\Entity\Ticket;
use App\Entity\Questions;
use App\Entity\Permission;
use App\Repository\AnswerRepository;
use App\Repository\DemoLoggerRepository;
use App\Repository\LoggerRepository;
use App\Repository\TicketRepository;
use App\Repository\QuestionsRepository;
use App\Repository\PermissionRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DemoService
{
    public function __construct(
        private readonly LoggerRepository $loggerRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly UrlGeneratorInterface $router,
        private readonly DemoLoggerRepository $demoLoggerRepository,
    ) {}

    public function startTesting(Course $course): int
    {
        $tickets = $this->ticketRepository->findBy(['course' => $course]);

        if (empty($tickets)) {
            throw new NotFoundHttpException();
        }

        $ticketNom = rand(1, count($tickets));
        $ticket = $this->ticketRepository->findOneBy([
            'course' => $course,
            'nom' => $ticketNom,
        ]);

        if (!$ticket instanceof Ticket) {
            throw new NotFoundHttpException();
        }

        return $ticket->getId();
    }

    public function getData(DemoLogger $logger): array
    {
        $ticket = $this->ticketRepository->find($logger->getTicket()->getId());

        if (!$ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket not Found');
        }

        if ($ticket->getCourse()->getType() === Course::INTERACTIVE) {
            $data = $this->getDataInteractive($logger);
        } else {
            $data = $this->getDataClassic($logger);
        }

        return $data;
    }

    public function ticketProcessing(DemoLogger $logger, array $data): string 
    {
        $ticket = $this->ticketRepository->find($logger->getTicket()->getId());
        if (! $ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket not found');
        }

        $ticketsArray = json_decode($ticket->getText()[0]);

        if ($logger->getCourse()->getType() === Course::CLASSC) {
            $ticketsArray = $this->transformTicketArray($ticketsArray);
        }

        $questionId = $ticketsArray[$logger->getQuestionNom() - 1];

        $question = $this->questionsRepository->find($questionId);
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

        if (isset($ticketsArray[$logger->getQuestionNom()])) {
            $logger->setQuestionNom($logger->getQuestionNom() + 1);

            $url = $this->router->generate('app_demo_final_testing', ['id' => $data['permissionId']]);
        } else {
            $url = $this->router->generate('app_demo_final_testing_end', ['id' => $data['permissionId']]);
        }

        $this->demoLoggerRepository->save($logger, true);

        return $url;
    }

    public function closeLogger(DemoLogger $logger): DemoLogger
    {
        $logger->setEndAt(new DateTime());

        if (
            $logger->getErrorActually() <= $logger->getErrorAllowed()
            && 0 === $this->getSkippedQuestion($logger)
        ) {
            $logger->setResult(true);
        }

        $this->demoLoggerRepository->save($logger, true);

        return $logger;
    }

    public function getSkippedQuestion(DemoLogger $logger): int
    {
        $skipped = 0;

        foreach($logger->getProtocol() as $question) {
            if ($question['qText'] === 'Пропущено'){
                $skipped++;
            }
        }

        return $skipped;
    }

    public function getFirstSuccesfullyLogger(Permission $permission, UserInterface $user): ?Logger
    {
        $logger = $this->loggerRepository->findFirstSuccessfullyLogger($permission, $user);

        return $logger;
    }

    public function createNewLogger(Course $course): DemoLogger
    {
        $ticketsCount = count($this->ticketRepository->getCourseTickets($course));

        $ticketNom = rand(1, $ticketsCount);
        $ticket = $this->ticketRepository->findOneBy(['course' => $course, 'nom' => $ticketNom]);

        if (! $ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket Not Found for course Id: ' . $course->getShortName());
        }

        $questionArray = json_decode($ticket->getText()[0]);

        if ($course->getType() === Course::CLASSC) {
            $questionArray = $this->transformTicketArray($questionArray);
        }

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

        $questionNom = 1;
        $timeLeft = null === $ticket->getTimeLeft() ? Logger::DEFAULT_TIME_LEFT_IN_SECONDS : $ticket->getTimeLeft() * 60;

        $logger = new DemoLogger();

        $logger
            ->setCourse($course)
            ->setLoggerId(uniqid(more_entropy: true))
            ->setBeginAt(new DateTime())
            ->setTicket($ticket)
            ->setErrorAllowed($ticket->getErrCnt())
            ->setProtocol($protocol)
            ->setQuestionNom($questionNom)
            ->setTimeLeftInSeconds($timeLeft)
            ->setTimeLastQuestion(new DateTime());

        $this->demoLoggerRepository->save($logger, true);

        return $logger;
    }

    private function getDataInteractive(DemoLogger $logger): array
    {
        $ticketId = json_decode($logger->getTicket()->getText()[0])[$logger->getQuestionNom() - 1];

        $question = $this->questionsRepository->find($ticketId);
        if (! $question instanceof Questions) {
            throw new NotFoundHttpException('Question not found');
        }
        
        $timeSpentNow = (new DateTime)->getTimestamp() - $logger->getTimeLastQuestion()->getTimestamp();

        $timeShift = $logger->getTimeLeftInSeconds() - $timeSpentNow;
        if ($timeShift < 0 ) {
            $timeShift = 0;
        }

        $timeLeft = $timeShift;

        $logger
            ->setTimeLeftInSeconds($timeLeft)
            ->setTimeLastQuestion(new DateTime());

        $this->demoLoggerRepository->save($logger, true);

        $leftSeconds = $timeLeft % 60;
        $leftMinutes = ($timeLeft - $leftSeconds) / 60;

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
            'url' => $this->router->generate('app_demo_testing_next_step', ['id' => $logger->getCourse()->getId()]),
            'finalUrl' => $this->router->generate('app_demo_final_testing_end', ['id' => $logger->getCourse()->getId()]),
            'loggerId' => $logger->getId(),
            'loggerId' => '',
            'nom' => $logger->getQuestionNom(),
            'text' => $question->getDescription(),
            'timeLeftTotal' => (new DateTime())->getTimestamp() + $timeLeft,
            'timeLastQuestion' => $logger->getTimeLastQuestion(),
            'timeLeftMinutes' => $leftMinutes,
            'timeLeftSeconds' => $leftSeconds,
            'type' => $question->getType(),
            'answers' => $dataAnswers,
        ];
    }
    
    private function getDataClassic(DemoLogger $logger): array
    {
        $questionId = $this->getTicketId(
            json_decode($logger->getTicket()->getText()[0], JSON_OBJECT_AS_ARRAY),
            $logger->getQuestionNom() - 1
        );

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

        return [
            'url' =>  $this->router->generate('app_demo_testing_next_step', ['id' => $logger->getCourse()->getId()]),
            'finalUrl' =>  $this->router->generate('app_demo_final_testing_end', ['id' => $logger->getCourse()->getId()]),
            'loggerId' => $logger->getId(),
            'nom' => $logger->getQuestionNom(),
            'text' => $question->getDescription(),
            'timeLeftTotal' => (new Datetime)->getTimestamp() + $logger->getTimeLeftInSeconds(),
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
