<?php

namespace App\Service;

use App\Entity\Module;
use App\Entity\ModuleTicket;
use App\Entity\Ticket;
use App\Repository\CourseRepository;
use App\Repository\ModuleTicketRepository;
use App\Repository\QuestionsRepository;
use App\Repository\TicketRepository;

class TicketService
{
    public function __construct(
        readonly TicketRepository $ticketRepository,
        readonly ModuleTicketRepository $moduleTicketRepository,
        readonly QuestionsRepository $questionsRepository,
        readonly CourseRepository $courseRepository
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
}
