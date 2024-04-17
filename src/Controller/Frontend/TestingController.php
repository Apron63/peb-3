<?php

namespace App\Controller\Frontend;

use App\Entity\Logger;
use App\Entity\Permission;
use App\Entity\User;
use App\Service\TestingReportService;
use App\Service\TestingService;
use App\Service\UserPermissionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException as ExceptionAccessDeniedException;

class TestingController extends AbstractController
{
    public function __construct(
        private readonly TestingService $testingService,
        private readonly UserPermissionService $userPermissionService,
        private readonly TestingReportService $reportService,
    ) {}

    #[Route('/frontend/testing/{id<\d+>}/', name: 'app_frontend_testing')]
    public function index(Permission $permission): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new ExceptionAccessDeniedException();
        }

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, true)) {
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
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        $firstSuccessfullyLogger = $this->testingService->getFirstSuccessfullyLogger(
            $logger->getPermission(),
            $user,
        );

        return $this->render('frontend/testing/protocol.html.twig', [
            'logger' => $this->testingService->closeLogger($logger),
            'skipped' => $this->testingService->getSkippedQuestion($logger),
            'hasSuccess' => $firstSuccessfullyLogger instanceof Logger,
        ]);
    }

    #[Route('/frontend/testing/print/{id<\d+>}/', name: 'app_frontend_testing_print')]
    public function printTesting(Logger $logger): BinaryFileResponse
    {
        $permission = $logger->getPermission();
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('Не найден пользователь');
        }

        if ($permission->getUser()->getId() !== $user->getId()) {
            throw new ExceptionAccessDeniedException();
        }

        $fileName = $this->reportService->generateTestingPdf($logger);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/pdf');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'protocol.pdf')
            ->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/frontend/testing/success/print/{id<\d+>}/', name: 'app_frontend_testing_success_print')]
    public function printSuccessTesting(Permission $permission): BinaryFileResponse
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('Не найден пользователь');
        }

        if ($permission->getUser()->getId() !== $user->getId()) {
            throw new ExceptionAccessDeniedException();
        }

        $logger = $this->testingService->getFirstSuccessfullyLogger($permission, $user);

        if (null === $logger) {
            throw new NotFoundHttpException('Не найден протокол с успешным результатом');
        }

        $fileName = $this->reportService->generateTestingPdf($logger);
        $response = new BinaryFileResponse($fileName);
        $response->headers->set('Content-Type', 'application/pdf');
        $response
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'protocol.pdf')
            ->deleteFileAfterSend(true);

        return $response;
    }
}
