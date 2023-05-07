<?php

namespace App\Controller\Frontend;

use App\Repository\PermissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CabinetController extends AbstractController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository
    ) {}

    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_homepage');
        }

        $permissions = $this->permissionRepository->getPermissionLeftMenu($this->getUser());

        return $this->render('frontend/my-programs/index.html.twig', [
            'permissions' => $permissions,
        ]);
    }
}
