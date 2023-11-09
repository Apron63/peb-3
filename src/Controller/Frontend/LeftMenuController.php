<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Repository\PermissionRepository;
use App\Service\MyProgramsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LeftMenuController extends AbstractController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly MyProgramsService $myProgramsService
    ) {}

    #[Route('/frontend/left/menu/', name: 'app_frontend_left_menu')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new AccessDeniedException('User access denied');
        }

        return $this->render('frontend/left-menu/index.html.twig', [
            'activeItem' => $request->get('activeItem'),
            'activeCourse' => (int)$request->get('activeCourse'),
            'permissions' => $this->myProgramsService->createSideMenuForUser($user),
        ]);
    }
}
