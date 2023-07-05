<?php

namespace App\Service;

use App\Entity\MailingQueue;
use App\Entity\Permission;
use App\Repository\MailingQueueRepository;
use Twig\Environment;

class MailingService
{
    public function __construct(
        private readonly MailingQueueRepository $mailingQueueRepository,
        private Environment $twig,
    ) { }

    public function addNewPermissionToMailQueue(Permission $permission): void
    {
        if (
            null === $permission->getId()
            && null !== $permission->getUser()->getEmail()
        ) {
            $mail = (new MailingQueue())
                ->setUser($permission->getUser())
                ->setSubject('Вам назначен курс : ' . $permission->getCourse()->getName())
                ->setContent($this->twig->render('mail\new-permission-created.html.twig', [
                    'permission' => $permission,
                ]));

            $this->mailingQueueRepository->save($mail, true);
        }
    }
}
