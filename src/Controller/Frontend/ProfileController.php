<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    public function __construct(readonly Security $security)
    {}

    #[Route('/profile/', name: 'profilePage')]
    public function profileAction(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('frontend/profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
