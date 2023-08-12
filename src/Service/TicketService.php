<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Entity\Questions;
use App\Entity\Ticket;
use App\Repository\AnswerRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;

class TicketService
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly CourseRepository $courseRepository,
        private readonly CourseThemeRepository $courseThemeRepository
    ) {}

    public function createTickets(int $courseId, int $ticketsCnt, int $errorsCnt, int $timeLeft, array $themes): void
    {
        $themesIds = array_map(static function ($e) {
            return $e['id'];
        }, $themes);

        $course = $this->courseRepository->findOneBy(['id' => $courseId]);

        $this->courseRepository->deleteOldTickets($course);

        $questionArray = [];
        foreach ($themesIds as $themeId) {
            $questionArray[$themeId] = $this->questionsRepository->getQuestionIds($course, $themeId);
        }

        $arr = [];
        for ($i = 1; $i <= $ticketsCnt; $i++) {
            foreach ($themesIds as $themeId) {
                $tmp = $questionArray[$themeId];
                shuffle($tmp);
                $index = array_search($themeId, array_column($themes, 'id'));
                $arr[$themeId] = array_slice($tmp, 0, $themes[$index]['inputValue']);
            }

            $ticket = new Ticket();
            $ticket->setNom($i)
                ->setCourse($course)
                ->setText((array)json_encode($arr, JSON_NUMERIC_CHECK))
                ->setErrCnt($errorsCnt);

            if (0 !== $timeLeft) {
                $ticket->setTimeLeft($timeLeft);
            }

            $this->ticketRepository->save($ticket, true);
        }
    }

    public function createModuleTickets(
        Course $course, 
        int $ticketCount, 
        int $questionCount,
        int $timeLeft,
        int $errorsCount
    ): void {
        $this->ticketRepository->deleteOldTickets($course);

        $questionArray = $this->questionsRepository->getQuestionIds($course, null);

        $arr = [];
        for ($i = 1; $i <= $ticketCount; $i++) {
            $tmp = $questionArray;
            shuffle($tmp);
            $arr = array_slice($tmp, 0, $questionCount);

            $ticket = new Ticket;
            $ticket->setCourse($course)
                ->setErrCnt($errorsCount)
                ->setText((array)json_encode($arr, JSON_NUMERIC_CHECK))
                ->setNom($i);

            if (0 !== $timeLeft) {
                $ticket->setTimeLeft($timeLeft);
            }

            $this->ticketRepository->save($ticket, true);
        }
    }

    public function renderTickets(Course $course): array
    {
        $result = [];
        $tickets = $this->ticketRepository->getCourseTickets($course);

        foreach ($tickets as $ticket) {
            $result[] = $this->renderTicket($ticket, false);
        }

        return $result;
    }

    public function renderTicket(array $ticket, bool $allAnswers = false): array
    {
        $items = json_decode($ticket['text'][0], JSON_FORCE_OBJECT);
        $data = [];

        foreach ($items as $key => $questions) {
            $theme = $this->courseThemeRepository->find($key);

            if ($theme instanceof CourseTheme) {
                $questionsArray = [];

                foreach ($questions as $questionNom => $questionId) {
                    $question = $this->questionsRepository->find($questionId);

                    if ($question instanceof Questions) {
                        $answers = [];

                        $trueAnswers = $this->answerRepository->getAnswers($question, $allAnswers);

                        if (!empty($trueAnswers)) {
                            foreach($trueAnswers as $answer) {
                                $answers[] = $answer['description'];
                            }
                        }

                        $questionsArray[] = [
                            'nom' => $questionNom + 1,
                            'description' => $question->getDescription(),
                            'answers' => $answers,
                            'help' => $question->getHelp(),
                        ];
                    }
                }

                $data[$theme->getName()] = [
                    'theme' =>  $theme->getDescription(),
                    'questions' => $questionsArray,
                ];
            }
        }

        return [
            'ticketNom' => $ticket['nom'],
            'data' => $data,
            'id' => $ticket['id'],
        ];
    }
    
    public function renderModuleTicket(array $ticket, bool $allAnswers = false): array
    {
        $items = json_decode($ticket['text'][0], JSON_FORCE_OBJECT);
        // $data = [];

        //foreach ($items as $key => $questions) {
        //    $theme = $this->courseThemeRepository->find($key);

            //if ($theme instanceof CourseTheme) {
                $questionsArray = [];

                foreach ($items as $questionNom => $questionId) {
                    $question = $this->questionsRepository->find($questionId);

                    if ($question instanceof Questions) {
                        $answers = [];

                        $trueAnswers = $this->answerRepository->getAnswers($question, $allAnswers);

                        if (!empty($trueAnswers)) {
                            foreach($trueAnswers as $answer) {
                                $answers[] = $answer['description'];
                            }
                        }

                        $questionsArray[] = [
                            'nom' => $questionNom + 1,
                            'description' => $question->getDescription(),
                            'answers' => $answers,
                            'help' => $question->getHelp(),
                        ];
                    }
                }

                // $data[$theme->getName()] = [
                //     'theme' =>  $theme->getDescription(),
                //     'questions' => $questionsArray,
                // ];
            //}
       // }

        return [
            'ticketNom' => $ticket['nom'],
            'data' => $questionsArray,
            'id' => $ticket['id'],
        ];
    }
}
