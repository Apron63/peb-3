<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Service\ActionService;
use DateInterval;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActionReportController extends MobileController
{
    public function __construct(
        private readonly ActionService $actionService
    ) {}

    #[Route('/admin/action/report', name: 'admin_action_report')]
    public function actionReportAction(Request $request): Response
    {
        $interval = $request->get('action_interval');
        $start = new DateTime($interval['startAt']);
        $end = (new DateTime($interval['endAt']))->add(new DateInterval('PT23H59M59S'));

        if ($interval) {
            return $this->mobileRender('admin/action-report/report.html.twig', [
                'data' => $this->actionService->generateReportData($start, $end),
                'start' => $start,
                'end' => $end,
            ]);
        }

        return $this->redirectToRoute('admin_user_list');
    }
}
