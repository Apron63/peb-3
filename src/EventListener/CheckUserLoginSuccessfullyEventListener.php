<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserHistory;
use App\Repository\UserHistoryRepository;
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
        private readonly UserHistoryRepository $userHistoryRepository,
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            $user->setSessionId($event->getRequest()->getSession()->getId());

            $this->userRepository->save($user, true);

            $request = $event->getRequest();
            $headers = $request->server->getHeaders();

            $userAgent = '';

            if (isset($headers['SEC_CH_UA_PLATFORM'])) {
                $userAgent .= $headers['SEC_CH_UA_PLATFORM'] . ',  ';
            }

            if (isset($headers['SEC-CH-UA-MODEL'])) {
                $userAgent .= $headers['SEC-CH-UA-MODEL'] . ',  ';
            }

            if (isset($headers['USER_AGENT'])) {
                $userAgent .= $headers['USER_AGENT'];
            }

            $userHistory = new UserHistory();
            $userHistory
                ->setUser($user)
                ->setIp($request->getClientIp())
                ->setUserAgent($userAgent);

            $this->userHistoryRepository->save($userHistory, true);
        }
    }
}
