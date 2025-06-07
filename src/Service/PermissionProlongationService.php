<?php

declare (strict_types=1);

namespace App\Service;

use App\Entity\Permission;
use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Repository\UserRepository;

class PermissionProlongationService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly UserRepository $userRepository,
    ) {}

    public function checkPermission(int $permissionId, User $user): string
    {
        $permission = $this->permissionRepository->find($permissionId);

        if (! $permission instanceof Permission) {
           return 'Этому слушателю не назначен курс';
        } else {
            $permissionCheckedBy = $permission->getCheckedBy();

            if (
                null !== $permissionCheckedBy
                && $user !== $permissionCheckedBy
            ) {
                return 'Запись уже отмечена другим методистом: ' . $permissionCheckedBy->getFullName();
            } else {
                if (null === $permission->getCheckedBy()) {
                    $permission->setCheckeddBy($user);
                } else {
                    $permission->setCheckeddBy(null);
                }

                $this->permissionRepository->save($permission, true);
            }
        }

        return '';
    }

    public function selectAll(array $criteria, User $user): ?string
    {
        $permissionsArray = $this->userRepository->getUserSearchQuery($criteria['user_search'])->getResult();

        foreach ($permissionsArray as $permissionArray) {
            if (! isset($permissionArray['permissionId'])) {
                $participant = $this->userRepository->find($permissionArray['userId']);

                return 'Для слушателя: ' . $participant->getFullName() . ' не назначены курсы';
            }

            $permission = $this->permissionRepository->find($permissionArray['permissionId']);

            if ($permission instanceof Permission) {
                $permissionCheckedBy = $permission->getCheckedBy();

                if (
                    null !== $permissionCheckedBy
                    && $user !== $permissionCheckedBy
                ) {
                    return $permission->getCourse()->getShortName()
                        . ' для слушателя: ' . $permission->getUser()->getFullName()
                        . ' уже отмечен: ' . $permissionCheckedBy->getFullName();
                }
            }

            if (null === $permissionCheckedBy) {
                $permission->setCheckeddBy($user);
                $this->permissionRepository->save($permission, true);
            }
        }

        return null;
    }

    public function cancelSelectAll(array $criteria, User $user): ?string
    {
        $permissionsArray = $this->userRepository->getUserSearchQuery($criteria['user_search'])->getResult();

        foreach ($permissionsArray as $permissionArray) {
            if (! isset($permissionArray['permissionId'])) {
                $participant = $this->userRepository->find($permissionArray['userId']);

                return 'Для слушателя: ' . $participant->getFullName() . ' не назначены курсы';
            }

            $permission = $this->permissionRepository->find($permissionArray['permissionId']);

            if ($permission instanceof Permission) {
                $permissionCheckedBy = $permission->getCheckedBy();

                if (
                    null !== $permissionCheckedBy
                    && $user !== $permissionCheckedBy
                ) {
                    return $permission->getCourse()->getShortName()
                        . ' для слушателя: ' . $permission->getUser()->getFullName()
                        . ' уже отмечен: ' . $permissionCheckedBy->getFullName();
                }
            }

            if (null !== $permissionCheckedBy) {
                $permission->setCheckeddBy(null);
                $this->permissionRepository->save($permission, true);
            }
        }

        return null;
    }

    public function permissionProlongate(int $duration, User $user): int
    {
        $permissions = $this->permissionRepository->findBy(['checkedBy' => $user]);

        foreach($permissions as $permission) {
            $permission
                ->setDuration($permission->getDuration() + $duration)
                ->setCheckeddBy(null);

            $this->permissionRepository->save($permission, true);
        }

        return count($permissions);
    }
}
