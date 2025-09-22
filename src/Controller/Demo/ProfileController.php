<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    public function __construct(
    ) {}

    #[Route('/demo/profile/', name: 'app_demo_profile')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('frontend/demo/profile.html.twig');
    }
}
