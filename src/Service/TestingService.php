<?php

namespace App\Service;

use App\Entity\Logger;
use App\Entity\Permission;
use App\Repository\LoggerRepository;
use App\Repository\TicketRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class TestingService
{
    public function __construct(
        readonly LoggerRepository $loggerRepository,
        readonly TicketRepository $ticketRepository,
    ) {}
    public function checkTestingScenario(Permission $permission, UserInterface $user)
    {
        //$ticketCount = $this->ticketRepository->getTicketCount($permission->getCourse());
        $logger = new Logger;
        $logger->setCourse($permission->getCourse())
            ->setUser($user);

        $this->loggerRepository->save($logger, true);
    }
}
