<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: LoginSuccessEvent::class)]
class CheckUserLoginSuccessfullyEventListener
{
    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $user->setSessionId($event->getRequest()->getSession()->getId());

            $this->userRepository->save($user, true);
        }
    }
}
