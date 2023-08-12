<?php

namespace App\Controller\Admin;

use App\Entity\Profile;
use App\Decorator\MobileController;
use App\Form\Admin\ProfileEditType;
use App\Repository\ProfileRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends MobileController
{
    public function __construct(
        private readonly ProfileRepository $profileRepository
    ) {}

    #[Route('/admin/profile/', name: 'admin_profile_list')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function index(): Response
    {
        return $this->mobileRender('admin/profile/index.html.twig', [
            'profileQuery' => $this->profileRepository->getAllProfiles(),
        ]);
    }
    
    #[Route('/admin/profile/create/', name: 'admin_profile_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(Request $request): Response
    {
        $profile = new Profile();
        $form = $this->createForm(ProfileEditType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->profileRepository->save($profile, true);

            return $this->redirectToRoute('admin_profile_list');
        }

        return $this->mobileRender('admin/profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/admin/profile/edit/{id<\d+>}/', name: 'admin_profile_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Profile $profile): Response
    {
        $form = $this->createForm(ProfileEditType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->profileRepository->save($profile, true);

            return $this->redirectToRoute('admin_profile_list');
        }

        return $this->mobileRender('admin/profile/edit.html.twig', [
            'form' => $form->createView(),
            'profile' => $profile,
        ]);
    }
    
    #[Route('/admin/profile/delete/{id<\d+>}/', name: 'admin_profile_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Profile $profile): Response
    {
        $this->profileRepository->remove($profile, true);

        return $this->redirectToRoute('admin_profile_list');
    }
}
