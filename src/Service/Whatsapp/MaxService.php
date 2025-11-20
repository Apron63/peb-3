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
use DateTime;
use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

readonly class MaxService
{
    private const int PHONE_NUMBER_LENGTH = 11;
    private const int SENDED_STATUS_OK = 200;
    private const string NEW_PRMISSION_ADDED_MESSAGE = 'Вам назначен новый курс ';
    private const string USER_HAS_ACTIVATED_PERMISSION_MESSAGE = 'Активирован учебный курс ';
    private const string PERMISSION_WILL_END_SOON_MESSAGE = 'Доступ скоро истекает ';
    private const string API_URL = 'https://3100.api.green-api.com';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly DashboardService $dashboardService,
        private readonly ConfigService $configService,
        private readonly WhatsappQueueRepository $whatsappQueueRepository,
        private readonly UserRepository $userRepository,
        private string $greenApiIdInstanceMax,
        private string $greenApiTokenInstanceMax,
    ) {}

    public function addNewPermissionToWhatsappQueue(Permission $permission): array
    {
        $result = [
            'status' => false,
            'message' => '',
        ];

        $user = $permission->getUser();

        if (null !== $user->getMobilePhone() && $user->isMaxConfirmed()) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('userHasNewPermissionMax'),
                [
                    '{LOGIN}',
                    '{PASSWORD}',
                    '{DURATION}',
                    '{COURSE}',
                    '{LASTDATE}',
                    '{NAME}',
                ],
                [
                    $permission->getUser()->getLogin(),
                    $permission->getUser()->getPlainPassword(),
                    $permission->getDuration(),
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                    $permission->getUser()->getFirstName(),
                ],
                $permission->getCreatedBy(),
            );

            $whatsappMessage = new WhatsappQueue()
                ->setUser($user)
                ->setSubject(self::NEW_PRMISSION_ADDED_MESSAGE . $permission->getCourse()->getShortName())
                ->setPhone($user->getMobilePhone())
                ->setCreatedBy($permission->getCreatedBy())
                ->setSendedAt(new DateTime())
                ->setAttempts(1)
                ->setContent($content)
                ->setMessengerType(WhatsappQueue::MESSENGER_TYPE_MAX);

            try {
                $this->send($user, $content);

                $whatsappMessage->setStatus('Успешно');

                $result['status'] = true;
                $result['message'] = 'Успешно';
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();

                $whatsappMessage->setStatus($errorMessage);

                $result['status'] = false;
                $result['message'] = $errorMessage;
            }

            $this->whatsappQueueRepository->save($whatsappMessage, true);
        }

        return $result;
    }

    public function userHasActivatedPermission(Permission $permission): void
    {
        $user = $permission->getUser();

        if (null !== $user->getMobilePhone() && $user->isMaxConfirmed()) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('userHasActivatedPermissionMax'),
                [
                    '{COURSE}',
                    '{LASTDATE}',
                    '{NAME}',
                ],
                [
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                    $permission->getUser()->getFirstName(),
                ],
                $permission->getCreatedBy()
            );

            $whatsappMessage = new WhatsappQueue()
                ->setUser($user)
                ->setSubject(self::USER_HAS_ACTIVATED_PERMISSION_MESSAGE . $permission->getCourse()->getShortName())
                ->setPhone($user->getMobilePhone())
                ->setCreatedBy($permission->getCreatedBy())
                ->setSendedAt(new DateTime())
                ->setAttempts(1)
                ->setContent($content)
                ->setMessengerType(WhatsappQueue::MESSENGER_TYPE_MAX);

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

        if (null !== $user->getMobilePhone() && $user->isMaxConfirmed()) {
            $content = $this->dashboardService->replaceValue(
                $this->configService->getConfigValue('permissionWillEndSoonMax'),
                [
                    '{COURSE}',
                    '{LASTDATE}',
                    '{NAME}',
                ],
                [
                    $permission->getCourse()->getName(),
                    $permission->getEndDate()->format('d.m.Y'),
                    $permission->getUser()->getFirstName(),
                ],
                $permission->getCreatedBy()
            );

            $whatsappMessage = new WhatsappQueue()
                ->setUser($user)
                ->setSubject(self::PERMISSION_WILL_END_SOON_MESSAGE . $permission->getCourse()->getShortName())
                ->setPhone($user->getMobilePhone())
                ->setCreatedBy($permission->getCreatedBy())
                ->setSendedAt(new DateTime())
                ->setAttempts(1)
                ->setContent($content)
                ->setMessengerType(WhatsappQueue::MESSENGER_TYPE_MAX);

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
        if (empty($user->getMobilePhone())) {
            throw new Exception('Не задан номер телефона');
        }

        if (! $user->isMaxConfirmed()) {
            throw new Exception('Отсутствует согласие на получение рассылки');
        }

        $phone = (string) preg_replace('/[^\d]/', '', $user->getMobilePhone());

        if (self::PHONE_NUMBER_LENGTH !== strlen($phone)) {
            throw new Exception('В номере телефона долно быть ровно ' . self::PHONE_NUMBER_LENGTH . ' цифр');
        }

        $chatId = $user->getMaxChatId();

        if (! $user->isMaxExists()) {
            $response = $this->httpClient->request(
                'POST',
                self::API_URL . '/v3/waInstance' . $this->greenApiIdInstanceMax . '/checkAccount/' . $this->greenApiTokenInstanceMax,
                [
                    'json' => [
                        'phoneNumber' => $phone,
                    ]
                ],
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

            $statusCode = $response->getStatusCode();
            if (self::SENDED_STATUS_OK !== $statusCode) {
                throw new Exception('Ошибка отправки данных: Ответ сервера: ' . $statusCode);
            }
            $content = $response->toArray();

            if (! isset($content['exist']) || ! $content['exist']) {
                if (isset($content['reason'])) {
                    throw new Exception('Ошибка отправки данных: ' . $content['reason']);
                }
                elseif (empty($content['chatId'])) {
                    throw new Exception('У номера отсутствует MAX аккаунт');
                }
            }

            $chatId = $content['chatId'];
            $user->setMaxExists(true)->setMaxChatId($chatId);
            $this->userRepository->save($user, true);
        }

        $response = $this->httpClient->request(
            'POST',
            self::API_URL . '/v3/waInstance' . $this->greenApiIdInstanceMax . '/sendMessage/' . $this->greenApiTokenInstanceMax,
            [
                'json' => [
                    'chatId' => $chatId,
                    'message' => $message,
                ]
            ],
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        $statusCode = $response->getStatusCode();
        if (self::SENDED_STATUS_OK !== $statusCode) {
            throw new Exception('Ошибка отправки данных: Ответ сервера: ' . $statusCode);
        }
    }
}
