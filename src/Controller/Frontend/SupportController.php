<?php

namespace App\Controller\Frontend;

use App\Entity\Support;
use App\Form\Frontend\SupportType;
use App\Service\SupportService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SupportController extends AbstractController
{
    public function __construct(
        private readonly SupportService $supportService,
    ) {}
    
    #[Route('/support/', name: 'app_frontend_support')]
    public function index(Request $request): Response
    {
        $support = new Support();
        $form = $this->createForm(SupportType::class, $support);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->supportService->sendSupportMailMessage($support);

            return $this->redirectToRoute('homepage');
        }

        return $this->render('frontend/support/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
