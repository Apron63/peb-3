<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Form\Admin\UserSearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends MobileController
{
    #[Route('/admin/', name: 'admin_homepage')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(UserSearchType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('admin_homepage');
        }

        return $this->mobileRender('admin/homepage/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
