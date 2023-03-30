<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Module;
use App\Entity\ModuleTicket;
use App\Entity\Questions;
use App\Repository\AnswerRepository;
use App\Repository\CourseRepository;
use App\Repository\CourseThemeRepository;
use App\Repository\ModuleTicketRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;

class ModuleTicketService
{
    public function __construct(
        readonly TicketRepository $ticketRepository,
        readonly ModuleTicketRepository $moduleTicketRepository,
        readonly QuestionsRepository $questionsRepository,
        readonly AnswerRepository $answerRepository,
        readonly CourseRepository $courseRepository,
        readonly CourseThemeRepository $courseThemeRepository
    ) {}

    public function renderTickets(Course $course): array
    {
        $result = [];

        $tickets = $this->moduleTicketRepository->getTickets($course);

        foreach ($tickets as $ticket) {
            $result[] = $this->renderTicket($ticket);
        }

        return $result;
    }

    public function renderTicket(ModuleTicket $ticket, bool $allAnswers = false): array
    {
        $questionsArray = [];
        $items = json_decode($ticket->getData()[0], JSON_FORCE_OBJECT);

        if (!empty($items)) {
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
        }

        $result = [
            'id' => $ticket->getId(),
            'nom' => $ticket->getTicketNom(),
            'questions' => $questionsArray,
        ];

        return $result;
    }
}
