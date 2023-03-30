<?php

namespace App\Controller\Frontend;

use App\Entity\CourseTheme;
use App\Entity\Permission;
use App\Repository\PermissionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TestingController extends AbstractController
{
    public function __construct(
        readonly PermissionRepository $permissionRepository
    ) {}

    #[Route('/frontend/testing/{id<\d+>}/', name: 'app_frontend_testing')]
    public function index(Permission $permission): Response
    {
        return $this->render('frontend/testing/index.html.twig', [
            'permission' => $permission,
        ]);
    } 
    
    #[Route('/frontend/testing/end/{id<\d+>}/', name: 'app_frontend_testing_end')]
    public function endTesting(Permission $permission): Response
    {
        return $this->render('frontend/testing/protocol.html.twig', [
            'permission' => $permission,
        ]);
    }
}
