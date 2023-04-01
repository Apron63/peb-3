<?php

namespace App\Controller\Frontend;

use App\Entity\Permission;
use App\Repository\PermissionRepository;
use App\Service\TestingService;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class TestingController extends AbstractController
{
    public function __construct(
        readonly PermissionRepository $permissionRepository,
        readonly TestingService $testingService,
        readonly UserPermissionService $userPermissionService,
    ) {}

    #[Route('/frontend/testing/{id<\d+>}/', name: 'app_frontend_testing')]
    public function index(Permission $permission): Response
    {
        $user = $this->getUser();

        if (!$this->userPermissionService->checkPermissionForUser($permission, $user, false)) {
            throw new ExceptionAccessDeniedException();
        }

        $this->testingService->checkTestingScenario($permission, $user);

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
