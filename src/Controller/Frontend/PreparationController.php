<?php

namespace App\Controller\Frontend;

use App\Entity\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PreparationController extends AbstractController
{
    #[Route('/preparation/{id<\d+>}/', name: 'app_frontend_preparation')]
    public function index(Permission $permission): Response
    {
        return $this->render('frontend/preparation/index.html.twig', [
            'permission' => $permission,
        ]);
    }
}
