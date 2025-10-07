<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HistotyController extends AbstractController
{
    public function __construct(
    ) {}

    #[Route('/demo/history/', name: 'app_demo_history')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('perPage', 20);

        if (! in_array($perPage, [4, 20, 50, 100])) {
            $perPage = 20;
        }

        $data = [
            'items' => [
                [
                    'name' => 'Б.1.9 Строительство, реконструкция, техническое перевооружение, капитальный ремонт, консервация и ликвидация химически опасных производственных объектов',
                    'stage' => 1,
                    'activatedAt' => new DateTime('2025-08-01'),
                    'endDate' => '30.09.2025',
                    'result' => 'Сдано',
                    'permissionId' => 999,
                    'loggerId' => 222,
                    'duration' => '20 ч. 10 м.',
                ],
                [
                    'name' => 'Обучение безопасным методам и приемам выполнения работ при воздействии вредных и опасных производственных факторов',
                    'stage' => 3,
                    'activatedAt' => new DateTime('2025-08-07'),
                    'endDate' => new DateTime('2025-09-01'),
                    'result' => 'Сдано',
                    'permissionId' => 999,
                    'loggerId' => 222,
                    'duration' => '30 ч. 15 м.',
                ],
            ],
            'paginator' => [
                [
                    'isActive' => true,
                    'url' => '/',
                    'title' => '1',
                ],
            ],
        ];

        return $this->render('frontend/demo/history/index.html.twig', [
            'data' => $data,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }
}
