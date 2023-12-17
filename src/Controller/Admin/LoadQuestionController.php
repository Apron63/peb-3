<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\User;
use App\Form\Admin\LoadCourseType;
use App\Decorator\MobileController;
use App\Message\QuestionUploadMessage;
use App\Service\QuestionUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Messenger\MessageBusInterface;

class LoadQuestionController extends MobileController
{
    public function __construct(
        private readonly QuestionUploadService $questionUploadService,
        private readonly MessageBusInterface $messageBus,
    ) {}
    
    #[Route('/admin/load/question/{id<\d+>}/', name: 'admin_load_question')]
    public function actionLoadCourse(Request $request, Course $course): Response
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
            $this->questionUploadService->fileQuestionUpload($form->get('filename')->getData(), $course);

            $this->messageBus->dispatch(
                new QuestionUploadMessage(
                    $form->get('filename')->getData()->getClientOriginalName(), 
                    $user->getId(),
                    $course->getId()
                )
            );

            $this->addFlash('success', 'Загрузка вопросов для курса добавлена в очередь заданий');

            return $this->redirectToRoute('admin_course_list');
        }

        return $this->mobileRender('admin/load-question/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
