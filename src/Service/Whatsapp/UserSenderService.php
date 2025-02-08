<?php

declare (strict_types=1);

namespace App\Service\Whatsapp;

use App\Entity\User;
use App\Repository\PermissionRepository;

readonly class UserSenderService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly WhatsappService $whatsappService,
    ) {}

    public function resendToUser(User $user): array
    {
        $result = [
            'status' => false,
            'message' => 'Нет действующих доступов',
        ];

        $activePermissions = $this->permissionRepository->getPermissionLeftMenu($user);

            foreach ($activePermissions as $permission) {
                $result = $this->whatsappService->addNewPermissionToWhatsappQueue($permission);

                if (!$result['status']) {
                    break;
                }
            }

            $result['status'] = true;
            $result['message'] = 'Рассылка выполнена';

        return $result;
    }
}
