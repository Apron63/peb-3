<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\CourseTheme;
use App\Entity\Module;
use App\Entity\ModuleTicket;
use App\Entity\Questions;
use App\Entity\Ticket;
use App\Repository\AnswerRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleTicketRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;

class TicketService
{
    public function __construct(
        readonly TicketRepository $ticketRepository,
        readonly ModuleTicketRepository $moduleTicketRepository,
        readonly QuestionsRepository $questionsRepository,
        readonly AnswerRepository $answerRepository,
        readonly CourseRepository $courseRepository,
        readonly CourseThemeRepository $courseThemeRepository
    ) {}

    public function createTickets(int $courseId, int $ticketsCnt, int $errorsCnt, array $themes): void
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

            $this->ticketRepository->save($ticket, true);
        }
    }

    public function createModuleTickets(
        Module $module, 
        int $ticketCount, 
        int $questionCount, 
        int $errorsCount
    ): void {
        $this->moduleTicketRepository->deleteOldTickets($module);

        $questionArray = $this->questionsRepository->getQuestionIds($module->getCourse(), $module->getId());

        $arr = [];
        for ($i = 1; $i <= $ticketCount; $i++) {
            $tmp = $questionArray;
            shuffle($tmp);
            $arr = array_slice($tmp, 0, $i);

            $moduleTicket = new ModuleTicket();
            $moduleTicket->setModule($module)
                ->setErrorCount($errorsCount)
                ->setData((array)json_encode($arr, JSON_NUMERIC_CHECK))
                ->setTicketNom($i);

            $this->moduleTicketRepository->save($moduleTicket, true);
        }
    }

    public function renderTickets(Course $course): array
    {
        $result = [];

        $tickets = $this->ticketRepository->getCourseTickets($course);

        foreach ($tickets as $ticket) {
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

                            $trueAnswers = $this->answerRepository->findBy([
                                'question' => $question,
                                'isCorrect' => true,
                            ]);

                            if (!empty($trueAnswers)) {
                                foreach($trueAnswers as $answer) {
                                    $answers[] = $answer->getDescription();
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

            $result[] = [
                'ticketNom' => $ticket['nom'],
                'data' => $data,
            ];
        }

        return $result;
    }
}
