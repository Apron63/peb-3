<?php

namespace App\EventListener;

use App\Entity\ActionLog;
use App\Event\ActionLogEvent;
use App\Repository\ActionLogRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ActionLogEvent::class)]
class ActionLogEventListener
{
    public function __construct(
        private readonly ActionLogRepository $actionLogRepository,
    ) {}

    public function __invoke(ActionLogEvent $event): void
    {
        $actionLog = new ActionLog();

        $actionLog
            ->setCreatedBy($event->createdBy)
            ->setDescription($event->description);

        $this->actionLogRepository->save($actionLog, true);
    }
}
