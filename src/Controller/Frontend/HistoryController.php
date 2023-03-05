<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HistoryController extends AbstractController
{
    #[Route('/frontend/history', name: 'app_frontend_history')]
    public function index(): Response
    {
        return $this->render('frontend/history/index.html.twig', [
            'controller_name' => 'HistoryController',
        ]);
    }
}
