<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\User;
use App\Service\Whatsapp\UserSenderService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WhatsappController extends MobileController
{
    public function __construct(

        private readonly UserSenderService $userSenderService,
    ) {}

    #[Route('/admin/user/whatsapp/resend/{id<\d+>}/', name: 'admin_user_whatsapp_resend')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function whatsappResend(User $user): RedirectResponse
    {
        if (empty($user->getMobilePhone())) {
            $this->addFlash('error', 'Не задан номер телефона');
        } elseif (!$user->isWhatsappConfirmed()) {
            $this->addFlash('error', 'Не задано согласие на рассылку');
        } else {
            $result = $this->userSenderService->resendToUser($user);

            $this->addFlash($result['status'] ? 'success' : 'error', $result['message']);
        }

        return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
    }
}
