<?php

namespace App\Controller\Frontend;

use App\Repository\PermissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HistoryController extends AbstractController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
    ) {}

    #[Route('/frontend/history/', name: 'app_frontend_history')]
    public function index(): Response
    {
        return $this->render('frontend/history/index.html.twig', [
            'permissions' => $this->permissionRepository->getPermissionLeftMenu($this->getUser()),
        ]);
    }
}
