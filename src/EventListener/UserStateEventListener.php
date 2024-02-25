<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserState;
use App\Repository\UserStateRepository;
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
    ];

    public function __construct(
        private readonly Security $security,
        private readonly UserStateRepository $userStateRepository,
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
        $unitOfWork = $entityManager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($entity);
        $changes = $this->mapChanges($changeSet);

        if (! empty($changes)) {
            $userState = new UserState();

            $userState
                ->setUser($entity)
                ->setCreatedAt(new DateTime())
                ->setCreatedBy($user)
                ->setChanges($this->mapChanges($changeSet));

            $this->userStateRepository->save($userState, true);
        }
    }

    private function mapChanges(array $changeSet): array
    {
        $result = [];

        foreach($changeSet as $fieldName => $change) {
            if (! in_array($fieldName, self::EXCLUDED_FIELDS)) {
                $result[] = [
                    'field' => $fieldName,
                    'oldValue' => $this->formatChangedData($change[0]),
                    'newValue' => $this->formatChangedData($change[1]),
                ];
            }
        }

        return $result;
    }

    private function formatChangedData(mixed $data): string
    {
        $result = $data;

        if ($data instanceof DateTime) {
            $result = $data->format('d.m.Y H:i:s');
        } else if ($data instanceof int) {
            $result = (string) $data;
        } else if (null === $data) {
            $result = 'не задано';
        } else if (is_array($data)) {
            $result = implode(',', $data);
        }

        return $result;
    }
}
