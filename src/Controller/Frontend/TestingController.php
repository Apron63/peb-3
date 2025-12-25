<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\Logger;
use App\Entity\Permission;
use App\Entity\User;
use App\Repository\PermissionRepository;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TestingController extends AbstractController
{
    public function __construct(
        private readonly TestingService $testingService,
        private readonly UserPermissionService $userPermissionService,
        private readonly TestingReportService $reportService,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    #[Route('/frontend/testing/{id<\d+>}/', name: 'app_frontend_testing')]
    public function index(Permission $permission): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new AccessDeniedException('No Auth User');
        }

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, true)) {
            throw new AccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        $logger = $this->testingService->getLogger($permission, $user);

        $permission = $this->testingService->checkPermissionIfFirstTimeTesting($permission);

        if (0 === $logger->getTimeLeftInSeconds()) {
            return $this->redirect(
                $this->generateUrl('app_frontend_testing_end', ['id' => $logger->getId()])
            );
        } else {
            return $this->render('frontend/testing/index.html.twig', [
                'permission' => $permission,
                'data' => $this->testingService->getData($logger, $permission),
            ]);
        }
    }

    #[Route('/frontend/testing/set-question/{id<\d+>}/{questionId<\d+>}/', name: 'app_frontend_set_question')]
    public function setQuestion(Permission $permission, int $questionId): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new AccessDeniedException('No Auth User');
        }

        if (! $this->userPermissionService->checkPermissionForUser($permission, $user, true)) {
            throw new AccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
        }

        $logger = $this->testingService->getLogger($permission, $user);

        $permission = $this->testingService->checkPermissionIfFirstTimeTesting($permission);

        return $this->render('frontend/testing/index.html.twig', [
            'permission' => $permission,
            'data' => $this->testingService->getData($logger, $permission, $questionId),
        ]);
    }

    #[Route('/frontend/testing-next-step/{id<\d+>}/', name: 'app_frontend_testing_next_step', condition: 'request.isXmlHttpRequest()')]
    public function nextStep(Permission $permission, Request $request): JsonResponse
    {
        return new JsonResponse([
            'redirectUrl' => $this->testingService->ticketProcessing(
                $request->request->all(),
                $permission->getCourse()->getType(),
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

        $permission = $logger->getPermission();

        $firstSuccessfullyLogger = $this->testingService->getFirstSuccessfullyLogger(
            $permission,
            $user,
        );

        $showGreeting = false;

        $logger = $this->testingService->closeLogger($logger);

        if ($permission->isGreetingEnabled() && $logger->getResult()) {
            $permission->setGreetingEnabled(false);
            $this->permissionRepository->save($permission, true);

            $showGreeting = true;
        }

        return $this->render('frontend/testing/protocol.html.twig', [
            'logger' => $logger,
            'errorsActually' => $this->testingService->getErrorsQuestion($logger),
            'skipped' => $this->testingService->getSkippedQuestion($logger),
            'hasSuccess' => $firstSuccessfullyLogger instanceof Logger,
            'showGreetings' => $showGreeting,
            'permissionId' => $permission->getId(),
        ]);
    }

    #[Route('/frontend/testing/print/{id<\d+>}/', name: 'app_frontend_testing_print')]
    public function printTesting(Logger $logger): BinaryFileResponse
    {
        $permission = $logger->getPermission();
        /** @var User $user */
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        if ($permission->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
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
            throw new AccessDeniedException('Permission: ' . $permission->getId() . ' not available for user: ' . $user->getId());
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

    #[Route(
        '/frontend/testing/setFirstTime/{id<\d+>}/',
        name: 'app_frontend_testing_set_first_time',
        condition: 'request.isXmlHttpRequest()',
        methods: 'POST',
    )]
    public function setPermissionFirsTime(Permission $permission): JsonResponse
    {
        if ($permission instanceof Permission) {
            $permission->setFirstTimeEnabled(false);

            $this->permissionRepository->save($permission, true);
        }

        return new JsonResponse([]);
    }
}
