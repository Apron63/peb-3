<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Profile;
use App\Form\Admin\ProfileEditType;
use App\Repository\ProfileRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends MobileController
{
    public function __construct(readonly ProfileRepository $profileRepository)
    { }

    #[Route('/admin/profile/', name: 'admin_profile_list')]
    public function index(): Response
    {
        return $this->mobileRender('admin/profile/index.html.twig', [
            'profileQuery' => $this->profileRepository->getAllProfiles(),
        ]);
    }
    
    #[Route('/admin/profile/create/', name: 'admin_profile_create')]
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
    
    #[Route('/admin/profile/edit/{id<\d+>}', name: 'admin_profile_edit')]
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
    
    #[Route('/admin/profile/delete/{id<\d+>}', name: 'admin_profile_delete')]
    public function delete(Request $request, Profile $profile): Response
    {
        $this->profileRepository->remove($profile, true);

        return $this->redirectToRoute('admin_profile_list');
    }
}
