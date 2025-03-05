<?php

declare (strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserState;
use App\Repository\UserStateRepository;
use App\Service\Whatsapp\UserSenderService;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postUpdate)]
class UserStateEventListener
{
    private const EXCLUDED_FIELDS = [
        'id',
        'createdBy',
        'password',
        'createdAt',
        'updatedAt',
        'sessionId',
        'fullName',
        'whatsappExists',
    ];

    private bool $hasMobilePhoneChanged = false;

    public function __construct(
        private readonly Security $security,
        private readonly UserStateRepository $userStateRepository,
        private readonly UserSenderService $userSenderService,
    ) {}

    public function postUpdate (PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (! $entity instanceof User) {
            return;
        }

        $user = $this->security->getUser();

        if (null === $user) {
            $user = $entity->getCreatedBy();
        }

        $entityManager = $args->getObjectManager();
         /** @disregard Undefined method 'getUnitOfWork'.intelephense(P1013) */
        $unitOfWork = $entityManager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($entity);
        $changes = $this->mapChanges($changeSet);

        if (! empty($changes)) {
            $userState = new UserState();

            $userState
                ->setUser($entity)
                ->setCreatedAt(new DateTime())
                ->setCreatedBy($user)
                ->setChanges($changes);

            $this->userStateRepository->save($userState, true);

            if ($this->hasMobilePhoneChanged) {
                $this->userSenderService->resendToUser($entity);
            }
        }
    }

    private function mapChanges(array $changeSet): array
    {
        $result = [];

        foreach($changeSet as $fieldName => $change) {
            if (! in_array($fieldName, self::EXCLUDED_FIELDS)) {
                $oldValue = $this->formatChangedData($change[0]);
                $newValue = $this->formatChangedData($change[1]);

                $result[] = [
                    'field' => $fieldName,
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                ];
            }

            if (
                $fieldName === 'mobilePhone'
                && $newValue !== 'не задано'
                && $newValue !== $oldValue
            ) {
                $this->hasMobilePhoneChanged = true;
            }
        }

        return $result;
    }

    private function formatChangedData(mixed $data): string
    {
        $result = $data;

        if ($data instanceof DateTime) {
            $result = $data->format('d.m.Y H:i:s');
        } elseif ($data instanceof int) {
            $result = (string) $data;
        } elseif (is_bool($data)) {
            $result = $data ? 'Да' : 'Нет';
        } elseif (null === $data) {
            $result = 'не задано';
        } elseif (is_array($data)) {
            $result = implode(',', $data);
        }

        return $result;
    }
}
