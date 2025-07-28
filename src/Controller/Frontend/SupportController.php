<?php

declare (strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\Support;
use App\RequestDto\SupportDto;
use App\Service\SupportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class SupportController extends AbstractController
{
    public function __construct(
        private readonly SupportService $supportService,
    ) {}

    #[Route('/support/', name: 'app_frontend_support')]
    public function index(): Response
    {
        $support = new Support();

        return $this->render('frontend/support/index.html.twig', [
            'support' => $support,
        ]);
    }

    #[Route('/support/send-email/', name: 'app_frontend_support_send_email', methods: 'POST')]
    public function sendEmailToSupport(
        #[MapRequestPayload(
        )] SupportDto $supportDto
    ): JsonResponse {
        $status = Response::HTTP_NOT_FOUND;

        if ($this->isCsrfTokenValid('support', $supportDto->_token)) {
            $this->supportService->sendSupportMailMessage($supportDto);

            $status = Response::HTTP_OK;
        }

        return new JsonResponse(status: $status);
    }
}
