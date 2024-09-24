<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\Permission;
use App\Entity\User;
use App\Repository\SurveyRepository;
use App\RequestDto\SurveyDto;
use App\Service\HistoryService;
use App\Service\SurveyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SurveyController extends AbstractController
{
    public function __construct(
        private readonly HistoryService $historyService,
        private readonly SurveyService $surveyService,
        private readonly SurveyRepository $surveyRepository,
    ) {}

    #[Route('/frontend/survey/{id<\d+>}/', name: 'app_frontend_survey')]
    public function index(Permission $permission): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        if ($user->getId() !== $permission->getUser()->getId()) {
            throw new AccessDeniedException('Permission : ' . $permission->getId() . ' not available for user : ' . $user->getId());
        }

        if (! $permission->isSurveyEnabled()) {
            throw new AccessDeniedException('Survey not enebled for permission: ' . $permission->getId());
        }

        $survey = $this->surveyRepository->getLastSurvey($user, $permission->getCourse());

        return $this->render('frontend/survey/index.html.twig', [
            'permission' => $permission,
            'survey' => $survey,
        ]);
    }

    #[Route('/frontend/survey/save/{id<\d+>}/', name: 'app_frontend_save_survey', methods: 'POST')]

    public function saveSurvey(
        Permission $permission,
        #[MapRequestPayload] SurveyDto $surveyDto,
    ): JsonResponse {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        if ($user->getId() !== $permission->getUser()->getId()) {
            throw new AccessDeniedException('Permission : ' . $permission->getId() . ' not available for user : ' . $user->getId());
        }

        if (! $permission->isSurveyEnabled()) {
            throw new AccessDeniedException('Survey not enebled for permission: ' . $permission->getId());
        }

        $this->surveyService->saveSurvey($permission, $surveyDto);

        return new JsonResponse();
    }
}
