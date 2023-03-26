<?php

namespace App\Controller\Frontend;

use App\Repository\PermissionRepository;
use App\Service\MyProgramsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeftMenuController extends AbstractController
{
    public function __construct(
        readonly PermissionRepository $permissionRepository,
        readonly MyProgramsService $myProgramsService
    ) {}

    #[Route('/frontend/left/menu/', name: 'app_frontend_left_menu')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        return $this->render('frontend/left-menu/index.html.twig', [
            'activeItem' => $request->get('activeItem'),
            'activeCourse' => (int)$request->get('activeCourse'),
            'permissions' => $this->myProgramsService->createSideMenuForUser($user),
        ]);
    }
}
