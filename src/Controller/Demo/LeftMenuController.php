<?php

declare(strict_types=1);

namespace App\Controller\Demo;

use App\Entity\User;
use App\Service\MyProgramsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LeftMenuController extends AbstractController
{
    public function __construct(
        private readonly MyProgramsService $myProgramsService
    ) {}

    #[Route('/demo/left/menu/', name: 'app_demo_left_menu')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            throw new AccessDeniedException('Only Demo User access alowed');
        }

        return $this->render('frontend/demo/left-menu/index.html.twig', [
            'activeItem' => $request->get('activeItem'),
            'activeCourse' => (int) $request->get('activeCourse'),
            'permissions' => $this->myProgramsService->createSideMenuForDemo(),
        ]);
    }
}
