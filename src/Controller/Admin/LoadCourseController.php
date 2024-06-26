<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\LoadCourseType;
use App\Decorator\MobileController;
use App\Message\CourseUploadMessage;
use App\Service\CourseDownloadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

class LoadCourseController extends MobileController
{
    public function __construct(
        private readonly CourseDownloadService $courseDownloadService,
        private readonly MessageBusInterface $messageBus
    ) {}

    #[Route('/admin/load/course/', name: 'admin_load_course')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function actionLoadCourse(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(LoadCourseType::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
            && $form->get('filename')->getData() !== null
        ) {
            try {
                $course = $this->courseDownloadService->downloadCourse($form->get('filename')->getData());

                $this->messageBus->dispatch(
                    new CourseUploadMessage(
                        $form->get('filename')->getData()->getClientOriginalName(),
                        $user->getId(),
                        $course->getId(),
                    )
                );

                $this->addFlash('success', 'Загрузка курса добавлена в очередь заданий');
            } catch (Throwable $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('admin_load_course');
        }

        return $this->mobileRender('admin/load-course/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
