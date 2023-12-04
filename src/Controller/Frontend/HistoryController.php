<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Service\HistoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HistoryController extends AbstractController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly HistoryService $historyService,
    ) {}

    #[Route('/frontend/history/', name: 'app_frontend_history')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        return $this->render('frontend/history/index.html.twig', [
            'items' => $this->historyService->getPermissionTestingResults(
                $this->permissionRepository->getPermissionLeftMenu($user),
                $user
            ),
        ]);
    }
}
