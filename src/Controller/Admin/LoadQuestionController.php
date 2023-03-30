<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Form\Admin\LoadCourseType;
use App\Message\QuestionUploadMessage;
use App\Service\QuestionUploadService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;

class LoadQuestionController extends MobileController
{
    public function __construct(
        readonly QuestionUploadService $questionUploadService,
        readonly MessageBusInterface $messageBus
    ) { }
    
    #[Route('/admin/load/question/{id<\d+>}/', name: 'admin_load_question')]
    public function actionLoadCourse(Request $request, Course $course): Response
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
            $this->questionUploadService->fileQuestionUpload($form->get('filename')->getData(), $course);

            $this->messageBus->dispatch(
                new QuestionUploadMessage(
                    $form->get('filename')->getData()->getClientOriginalName(), 
                    $this->getUser()->getId(),
                    $course->getId()
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

        return $this->mobileRender('admin/load-question/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
