<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST)]
class SessionRenewListener
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if ($session && $session->isStarted() && $session->has('_security_main')) {
            if ($this->security->isGranted('ROLE_ADMIN')) {
                /** @var User $user */
                $user = $this->security->getToken()->getUser();

                $session->migrate(true);
                $session->set('last_activity', time());
                $user->setSessionId($session->getId());
                $this->userRepository->save($user, true);
            }
        }
    }
}
