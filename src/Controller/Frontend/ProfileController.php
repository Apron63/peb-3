<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Service\ProfileService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    #[Route('/profile/', name: 'profilePage')]
    public function profileAction(): Response
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('frontend/profile/index.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/profile/upload_image/', name: 'profile_upload_image', methods: 'POST', condition: 'request.isXmlHttpRequest()')]
    public function profileUploadImageAction(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            $result = [
                'success' => false,
                'message' => 'Ошибка доступа',
            ];
        } else {
            $image = $request->files->get('image');

            if (! $image instanceof UploadedFile) {
                $result = [
                    'success' => false,
                    'message' => 'Ошибка загрузки файла',
                ];
            } else {
                $result = $this->profileService->UploadAvatar($user, $image);
            }
        }

        return new JsonResponse($result);
    }
}
