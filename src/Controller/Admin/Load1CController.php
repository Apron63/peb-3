<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\User;
use App\Form\Admin\Load1CType;
use App\Repository\CourseRepository;
use App\Repository\ProfileRepository;
use App\Service\Query1CUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Load1CController extends MobileController
{
    public function __construct(
        readonly Query1CUploadService $uploadService,
        readonly CourseRepository $courseRepository,
        readonly ProfileRepository $profileRepository
    ) {
    }
    
    #[Route('/admin/load1c/select-file/', name: 'admin_load_1c_file')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(Load1CType::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
            && $form->get('filename')->getData() !== null
        ) {
            $userData = $this->uploadService->getUsersList($form->get('filename')->getData());

            return $this->render('admin/load-1c/select.html.twig', [
                'userData' => $userData,
            ]);
        }

        return $this->mobileRender('admin/load-1c/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/load1c/loader/', name: 'admin_get_modal_1c_load', condition: 'request.isXmlHttpRequest()')]
    public function getModalDataAction(): Response
    {
        $allCourse = $this->courseRepository->getAllCourses();
        $profiles = $this->profileRepository->getAllProfiles();

        return new Response(
            $this->renderView('admin/load-1c/_select_course.html.twig', [
                'course' => $allCourse,
                'profiles' => $profiles,
            ])
        );
    }

    #[Route('/admin/query/create/', name: 'admin_query_create', condition: 'request.isXmlHttpRequest()')]
    public function createUserQueryAction(Request $request): Response
    {
        $courseIds = $request->get('course');
        $duration = $request->get('duration');
        $data = json_decode($request->get('data'));

        /** @var User $user */
        $user = $this->getUser();
        $result = $this->uploadService->sendUserDataToQuery($user, $courseIds, $duration, $data);

        return new Response($result['message']);
    }
}
