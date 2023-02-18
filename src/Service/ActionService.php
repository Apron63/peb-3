<?php

namespace App\Service;

use App\Entity\Action;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\ActionRepository;
use DateTime;

class ActionService
{
    public function __construct(readonly ActionRepository $actionRepository)
    { }

    public function addToActionLog(User $user, Course $course, string $note): int
    {
        $action = new Action();
        $action->setStartAt(new DateTime())
            ->setEndAt(null)
            ->setCourse($course)
            ->setUser($user)
            ->setNote($note);
        $this->actionRepository->add($action, true);
        return $action->getId();
    }

    public function saveActionLog(int $actionId, string $note)
    {
        $action = $this->actionRepository->find($actionId);
        if ($action) {
            $action->setEndAt(new DateTime())->setNote($action->getNote() . ' ' . $note);
            $this->actionRepository->add($action, true);
        }
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @return array
     */
    public function generateReportData(DateTime $start, DateTime $end): array
    {
        $result = [];
        $firstBreak = true;
        $courseName = '';
        $courseId = 0;
        $startInterval = new DateTime('00:00:00');
        $endInterval = clone $startInterval;
        /** @var Action $row */
        foreach($this->actionRepository->getActionsForUser($start, $end) as $row) {
            if ($firstBreak) {
                $firstBreak = false;
                $courseId = $row->getCourse()->getId();
                $courseName = $row->getCourse()->getShortName();
            }
            if ($row->getCourse()->getId() !== $courseId) {
                $result[] = [
                    'courseName' => $courseName,
                    'totalTime' => ($endInterval->diff($startInterval))->format('%H часов %I минут %S секунд'),
                ];
                $courseId = $row->getCourse()->getId();
                $startInterval = new DateTime('00:00:00');
                $endInterval = clone $startInterval;
                $courseName = $row->getCourse()->getShortName();
            }

            $startInterval->add($row->getStartAt()->diff($row->getEndAt()));

        }
        if (!$firstBreak) {
            $result[] = [
                'courseName' => $courseName,
                'totalTime' => ($endInterval->diff($startInterval))->format('%H часов %I минут %S секунд'),
            ];
        }

        return $result;
    }
}