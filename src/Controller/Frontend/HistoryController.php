<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Service\HistoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class HistoryController extends AbstractController
{
    public function __construct(
        private readonly HistoryService $historyService,
    ) {}

    #[Route('/frontend/history/', name: 'app_frontend_history')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 20);

        if (! in_array($perPage, [4, 20, 50, 100])) {
            $perPage = 20;
        }

        return $this->render('frontend/history/index.html.twig', [
            'data' => $this->historyService->getHistory($user, $page, $perPage),
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/frontend/history/next-page/', name: 'app_frontend_history_next_page')]
    public function preparationOneNextPage(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            throw new NotFoundHttpException('User Not Found');
        }

        $requestContent = json_decode($request->getContent(), true);

        $page = $requestContent['page'];
        $perPage = $requestContent['per_page'];

        $data = $this->historyService->getHistory($user, $page, $perPage);

        return new JsonResponse([
            'status' => 'success',
            'page' => $page + 1,
            'per_page' => $perPage,
            'total' => $data['total'],
            'content' => $this->renderView('frontend/history/_partial.html.twig', [
                'data' => $data,
            ]),
        ]);
    }
}
