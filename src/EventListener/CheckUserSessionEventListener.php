<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: RequestEvent::class)]
class CheckUserSessionEventListener
{
    public function __construct(
        private readonly Security $security,
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        if ('/login/' !== $request->getRequestUri()) {
            $user = $this->security->getUser();

            if ($user instanceof User) {
                $sessionId = $request->getSession()->getId();

                if ($sessionId !== $user->getSessionId()) {
                    $this->security->logout(false);
                }
            }
        }
    }
}
