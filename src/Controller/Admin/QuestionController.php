<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use App\Entity\Questions;
use App\Decorator\MobileController;
use App\Repository\AnswerRepository;
use App\Form\Admin\QuestionsEditType;
use App\Repository\QuestionsRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class QuestionController extends MobileController
{
    public function __construct(
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository
    ) {}

    #[Route('/admin/question/create/{id<\d+>}/{parentId}', name: 'admin_question_create')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminQuestionCreate(Request $request, Course $course, ?int $parentId = null): Response
    {
        $question = new Questions();
        $question
            ->setCourse($course)
            ->setParentId($parentId)
            ->setNom(
                $this->questionsRepository->getNextNumber($course, $parentId)
            );

        $form = $this->createForm(QuestionsEditType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->questionsRepository->save($question, true);

            if ($question->getCourse()->gettype() === Course::INTERACTIVE) {
                $redirectUrl = $this->generateUrl('admin_course_edit', ['id' => $question->getCourse()->getId()]);
            } else {
                $redirectUrl = $this->generateUrl('admin_course_theme_edit', ['id' => $question->getParentId()]);
            }

            return $this->redirect($redirectUrl);
        }

        return $this->render('admin/question/edit.html.twig', [
            'form' => $form->createView(),
            'answers' => $this->answerRepository->getAnswers($question),
            'courseId' => $question->getCourse()->getId(),
        ]);
    }

    #[Route('/admin/question/{id<\d+>}/', name: 'admin_question_edit')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminQuestionEdit(Request $request, Questions $question): Response
    {
        $form = $this->createForm(QuestionsEditType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->questionsRepository->save($question, true);

            if ($question->getCourse()->gettype() === Course::INTERACTIVE) {
                $redirectUrl = $this->generateUrl('admin_course_edit', ['id' => $question->getCourse()->getId()]);
            } else {
                $redirectUrl = $this->generateUrl('admin_course_theme_edit', ['id' => $question->getParentId()]);
            }

            return $this->redirect($redirectUrl);
        }

        return $this->mobileRender('admin/question/edit.html.twig', [
            'form' => $form->createView(),
            'answers' => $this->answerRepository->getAnswers($question),
            'courseId' => $question->getCourse()->getId(),
        ]);
    }

    #[Route('/admin/question/delete/{id<\d+>}/', name: 'admin_question_delete')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function adminQuestionDelete(Questions $question): Response
    {
        if ($question->getCourse()->gettype() === Course::INTERACTIVE) {
            $redirectUrl = $this->generateUrl('admin_course_edit', ['id' => $question->getCourse()->getId()]);
        } else {
            $redirectUrl = $this->generateUrl('admin_course_theme_edit', ['id' => $question->getParentId()]);
        }

        $this->questionsRepository->remove($question, true);

        return $this->redirect($redirectUrl);
    }
}
