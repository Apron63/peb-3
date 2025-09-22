<?php

declare (strict_types=1);

namespace App\Controller\Demo;

use App\Entity\User;
use App\Repository\FaqRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class FaqController extends AbstractController
{
    public function __construct(
        private readonly FaqRepository $faqRepository,
    ) {}

    #[Route('/demo/faq/', name: 'app_demo_faq')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            throw new NotFoundHttpException('User Not Allowed');
        }

        return $this->render('frontend/demo/faq.html.twig', [
            'faq' => $this->faqRepository->findAll(),
        ]);
    }
}
