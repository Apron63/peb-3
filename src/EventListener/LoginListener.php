<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\ActionService;
use App\Service\LoggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener implements EventSubscriberInterface
{
    private LoggerService $loggerService;
    private ActionService $actionService;

    public function __construct(LoggerService $loggerService, ActionService $actionService)
    {
        $this->loggerService = $loggerService;
        $this->actionService = $actionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onInterActiveLogin',
        ];
    }

    public function onInterActiveLogin(InteractiveLoginEvent $event): ?Response
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        if ($user && $user->getIsOrdinaryUser()) {
            //$this->actionService->addToActionLog($user, 'Вход в программу');
            $loggerExists = $this->loggerService->checkIfUserHasNotCompletedLogger($user);
            if ($loggerExists) {
                return new RedirectResponse("/user/exam/{$loggerExists->getCourse()->getId()}/");
            }
        }
        return null;
    }
}
