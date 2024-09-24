<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Form\Admin\SurveyType;
use App\Service\ReportGenerator\SurveyGeneratorService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SurveyController extends MobileController
{
    public function __construct(
        private readonly SurveyGeneratorService $surveyGeneratorService,
    ) {}

    #[Route('/admin/survey/', name: 'admin_survey')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(SurveyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'startPeriod' => $form->get('startPeriod')->getData(),
                'endPeriod' => $form->get('endPeriod')->getData(),
                'profilesArr' => $form->get('profile')->getData()->toArray(),
                'reportType' => $form->get('reportType')->getData(),
            ];

            $reportDto = $this->surveyGeneratorService->generateSurveyReport($user, $data);

            $response = new BinaryFileResponse($reportDto['filename']);
            $response->headers->set('Content-Type', $reportDto['contentType']);
            $response
                ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $reportDto['attachment'])
                ->deleteFileAfterSend(true);

            return $response;
        }

        return $this->mobileRender('admin/survey/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
