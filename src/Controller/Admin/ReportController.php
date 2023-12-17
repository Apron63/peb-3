<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ReportController extends MobileController
{
    #[Route('/admin/load1C/report/', name: 'admin_load1C_report')]
    public function index(Request $request): Response
    {
        $fileName = $request->get('fileName');
        
        if (! file_exists($fileName)) {
            throw new NotFoundHttpException('Отчет не найден');
        }

        $response = new Response(file_get_contents($fileName));

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'report.csv'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
