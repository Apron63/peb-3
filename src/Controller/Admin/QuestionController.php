<?php

namespace App\Controller\Admin;

use App\Decorator\MobileController;
use App\Entity\Course;
use App\Entity\Questions;
use App\Form\Admin\QuestionsEditType;
use App\Repository\AnswerRepository;
use App\Repository\QuestionsRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends MobileController
{
    public function __construct(
        readonly QuestionsRepository $questionsRepository,
        readonly AnswerRepository $answerRepository
    ) {}

    #[Route('/admin/question/create/{id<\d+>}/{parentId}', name: 'admin_question_create')]
    public function adminQuestionCreate(Request $request, Course $course, ?int $parentId = null): Response
    {
        $question = new Questions();
        $question->setCourse($course);
        $question->setParentId($parentId);
        $question->setNom(
            $this->questionsRepository->getNextNumber($course, $parentId)
        );
        $form = $this->createForm(QuestionsEditType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->questionsRepository->save($question, true);
            }

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
    public function adminQuestionEdit(Request $request, PaginatorInterface $paginator, Questions $question): Response
    {
        $form = $this->createForm(QuestionsEditType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->questionsRepository->save($question, true);
            }

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
    public function adminQuestionDelete(Request $request, Questions $question): Response
    {
        if ($question->getCourse()->gettype() === Course::INTERACTIVE) {
            $redirectUrl = $this->generateUrl('admin_course_edit', ['id' => $question->getCourse()->getId()]);
        } else {
            $redirectUrl = $this->generateUrl('admin_course_theme_edit', ['id' => $question->getParentId()]);
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->questionsRepository->remove($question, true);
        }

        return $this->redirect($redirectUrl);
    }
}
