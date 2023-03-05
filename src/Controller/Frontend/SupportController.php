<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    #[Route('/frontend/support', name: 'app_frontend_support')]
    public function index(): Response
    {
        return $this->render('frontend/support/index.html.twig', [
            'controller_name' => 'SupportController',
        ]);
    }
}
