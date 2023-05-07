<?php

namespace App\Controller\Frontend;

use App\Entity\Logger;
use App\Entity\Permission;
use App\Repository\PermissionRepository;
use App\Service\TestingService;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class TestingController extends AbstractController
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly TestingService $testingService,
        private readonly UserPermissionService $userPermissionService,
    ) {}

    #[Route('/frontend/testing/{id<\d+>}/', name: 'app_frontend_testing')]
    public function index(Permission $permission): Response
    {
        $user = $this->getUser();

        if (!$this->userPermissionService->checkPermissionForUser($permission, $user, false)) {
            throw new ExceptionAccessDeniedException();
        }

        $logger = $this->testingService->getLogger($permission, $user);

        if (0 === $logger->getTimeLeftInSeconds()) {
            return $this->redirect(
                $this->generateUrl('app_frontend_testing_end', ['id' => $logger->getId()])
            );
        } else {
            return $this->render('frontend/testing/index.html.twig', [
                'permission' => $permission,
                'data' =>  $this->testingService->getData($logger, $permission),
            ]);
        }
    } 
    
    #[Route('/frontend/testing-next-step/{id<\d+>}/', name: 'app_frontend_testing_next_step',  condition: 'request.isXmlHttpRequest()')]
    public function nextStep(Permission $permission, Request $request): JsonResponse
    {
        return new JsonResponse([
            'redirectUrl' => $this->testingService->ticketProcessing(
                $request->request->all(), 
                $permission->getCourse()->getType()
            )
        ]);
    }

    #[Route('/frontend/testing/end/{id<\d+>}/', name: 'app_frontend_testing_end')]
    public function endTesting(Logger $logger): Response
    {
        return $this->render('frontend/testing/protocol.html.twig', [
            'logger' => $this->testingService->closeLogger($logger),
            'skipped' => $this->testingService->getSkippedQuestion($logger),
        ]);
    }
}
