<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SupportController extends AbstractController
{
    public function __construct(
    ) {}

    #[Route('/demo/support/', name: 'app_demo_support')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            throw new NotFoundHttpException('User Not Allowed');
        }

        return $this->render('frontend/demo/support.html.twig');
    }
}
