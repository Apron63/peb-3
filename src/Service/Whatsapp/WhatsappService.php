<?php

declare (strict_types=1);

namespace App\Service\Whatsapp;

use App\Entity\Permission;
use App\Entity\User;
use App\Entity\WhatsappQueue;
use App\Repository\UserRepository;
use App\Repository\WhatsappQueueRepository;
use App\Service\ConfigService;
use App\Service\DashboardService;
use GreenApi\RestApi\GreenApiClient;
use Throwable;

readonly class WhatsappService
{
    private const PHONE_NUMBER_LENGTH = 11;
    private const SENDED_STATUS_OK = 200;
    private const NEW_PRMISSION_ADDED_MESSAGE = 'Вам назначен новый курс ';
    private const USER_HAS_ACTIVATED_PERMISSION_MESSAGE = 'Активирован учебный курс ';
    private const PERMISSION_WILL_END_SOON_MESSAGE = 'Доступ скоро истекает ';

    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
        private readonly WhatsappQueueRepository $whatsappQueueRepository,
        private readonly UserRepository $userRepository,
        private string $greenApiIdInstance,
        private string $greenApiTokenInstance,
    ) {}

    public function addNewPermissionToWhatsappQueue(Permission $permission): void
    {
        $user = $permission->getUser();

        if (
            null === $permission->getId()
            && null !== $user->getContact()
            && $user->isWhatsappConfirmed()
        ) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('userHasNewPermissionWhatsapp'),
                [
                    '{LOGIN}',
                    '{PASSWORD}',
                    '{DURATION}',
                    '{COURSE}',
                ],
                [
                    $permission->getUser()->getLogin(),
                    $permission->getUser()->getPlainPassword(),
                    $permission->getDuration(),
                    $permission->getCourse()->getName(),
                ],
                $permission->getCreatedBy(),
            );

            $whatsappMessage = (new WhatsappQueue())
                ->setUser($user)
                ->setSubject(self::NEW_PRMISSION_ADDED_MESSAGE . $permission->getCourse()->getShortName())
                ->setPhone($user->getContact())
                ->setCreatedBy($permission->getCreatedBy())
                ->setSendedAt(new \DateTime())
                ->setAttempts(1)
                ->setContent($content);

            try {
                $this->send($user, $content);

                $whatsappMessage->setStatus('Успешно');
            } catch (Throwable $e) {
                $whatsappMessage->setStatus($e->getMessage());
            }

            $this->whatsappQueueRepository->save($whatsappMessage, true);
        }
    }

    public function userHasActivatedPermission(Permission $permission): void
    {
        $user = $permission->getUser();

        if ($user->isWhatsappConfirmed()) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('userHasActivatedPermissionWhatsapp'),
                [
                    '{COURSE}',
                    '{LASTDATE}',
                ],
                [
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                ],
                $permission->getCreatedBy()
            );

            $whatsappMessage = (new WhatsappQueue())
                ->setUser($user)
                ->setSubject(self::USER_HAS_ACTIVATED_PERMISSION_MESSAGE . $permission->getCourse()->getShortName())
                ->setPhone($user->getContact())
                ->setCreatedBy($permission->getCreatedBy())
                ->setSendedAt(new \DateTime())
                ->setAttempts(1)
                ->setContent($content);

            try {
                $this->send($user, $content);

                $whatsappMessage->setStatus('Успешно');
            } catch (Throwable $e) {
                $whatsappMessage->setStatus($e->getMessage());
            }

            $this->whatsappQueueRepository->save($whatsappMessage, true);
        }
    }

    public function permissionWillEndSoon(Permission $permission): void
    {
        $user = $permission->getUser();

        if ($user->isWhatsappConfirmed()) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('permissionWillEndSoonWhatsapp'),
                [
                    '{COURSE}',
                    '{LASTDATE}',
                ],
                [
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                ],
                $permission->getCreatedBy()
            );

            $whatsappMessage = (new WhatsappQueue())
                ->setUser($user)
                ->setSubject(self::PERMISSION_WILL_END_SOON_MESSAGE . $permission->getCourse()->getShortName())
                ->setPhone($user->getContact())
                ->setCreatedBy($permission->getCreatedBy())
                ->setSendedAt(new \DateTime())
                ->setAttempts(1)
                ->setContent($content);

            try {
                $this->send($user, $content);

                $whatsappMessage->setStatus('Успешно');
            } catch (Throwable $e) {
                $whatsappMessage->setStatus($e->getMessage());
            }

            $this->whatsappQueueRepository->save($whatsappMessage, true);
        }
    }

    public function send(User $user, string $message): void
    {
        if (empty($user->getContact())) {
            throw new Throwable('Не задан номер телефона');
        }

        if (! $user->isWhatsappConfirmed()) {
            throw new Throwable('Отсутствует согласие на получение рассылки');
        }

        $phone = (string) preg_replace('/[^\d]/', '', $user->getContact());

        if (self::PHONE_NUMBER_LENGTH !== strlen($phone)) {
            throw new Throwable('В номере телефона долно быть ровно ' . self::PHONE_NUMBER_LENGTH . ' цифр');
        }

        $greenApi = new GreenApiClient($this->greenApiIdInstance, $this->greenApiTokenInstance);
        $chatId = $phone . '@c.us';

        if (! $user->isWhatsappExiists()) {
            $result = $greenApi->serviceMethods->checkWhatsapp((int) $phone);

            if (! $result->data->existsWhatsapp) {
                throw new Throwable('Слушатель не зарегистрирован в WhatsApp');
            } else {
                $user->setWhatsappExists(true);

                $this->userRepository->save($user, true);
            }
        }

        $result = $greenApi->sending->sendMessage($chatId, $message);

        if (self::SENDED_STATUS_OK !== $result->code) {
            throw new Throwable('Ошибка отправки: ' . $result->error);
        }
    }
}