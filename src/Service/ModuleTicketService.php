<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Questions;
use App\Repository\AnswerRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;

class ModuleTicketService
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
    ) {}

    public function renderTickets(Course $course): array
    {
        $result = [];

        $tickets = $this->ticketRepository->getCourseTickets($course);

        foreach ($tickets as $ticket) {
            $result[] = $this->renderTicket($ticket);
        }

        return $result;
    }

    public function renderTicket(array $ticket, bool $allAnswers = false): array
    {
        $questionsArray = [];
        $items = json_decode($ticket['text'][0], JSON_FORCE_OBJECT);

        if (! empty($items)) {
            foreach ($items as $questionNom => $questionId) {
                $question = $this->questionsRepository->find($questionId);

                if ($question instanceof Questions) {
                    $answers = [];

                    $trueAnswers = $this->answerRepository->getAnswers($question, $allAnswers);

                    if (! empty($trueAnswers)) {
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

        return [
            'ticketNom' => $ticket['nom'],
            'data' => $questionsArray,
            'id' => $ticket['id'],
        ];
    }
}
