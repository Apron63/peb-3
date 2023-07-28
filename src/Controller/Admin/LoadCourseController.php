<?php

namespace App\Controller\Admin;

use App\Form\Admin\LoadCourseType;
use App\Decorator\MobileController;
use App\Message\CourseUploadMessage;
use App\Service\CourseUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LoadCourseController extends MobileController
{
    public function __construct(
        private readonly CourseUploadService $courseUploadService,
        private readonly MessageBusInterface $messageBus
    ) {}
    
    #[Route('/admin/load/course/', name: 'admin_load_course')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function actionLoadCourse(Request $request): Response
    {
        /** @var Session $session */
        $session = $request->getSession();

        $form = $this->createForm(LoadCourseType::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
            && $form->get('filename')->getData() !== null
        ) {
            $course = $this->courseUploadService->fileCourseUpload($form->get('filename')->getData());

            $this->messageBus->dispatch(
                new CourseUploadMessage(
                    $form->get('filename')->getData()->getClientOriginalName(), 
                    $this->getUser()->getId(),
                    $course->getId(),
                )
            );

            $session
                ->getFlashBag()
                ->add(
                    'error',
                    'Загрузка курса добавлена в очередь заданий'
                );

            return $this->redirectToRoute('admin_load_course');
        }

        return $this->mobileRender('admin/load-course/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
