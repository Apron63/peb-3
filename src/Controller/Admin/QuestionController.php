<?php

namespace App\Controller\Admin;

use App\Entity\CourseTheme;
use App\Entity\Questions;
use App\Repository\QuestionsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    public function __construct(
        readonly QuestionsRepository $questionsRepository
    ) {}

    #[Route('/admin/question/create/{id<\d+>}/', name: 'admin_question_create')]
    public function adminQuestionCreate(Request $request, CourseTheme $courseTheme): Response
    {
        $question = new Questions();
        $question->setParentId($courseTheme->getId());
        $nom = $this->questionsRepository->getNextNom($courseTheme);
        //$question->setNom($nom);
        $form = $this->createForm(QuestionsEditType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->questionsRepository->save($question, true);
            }

            return $this->redirect('/admin/course_theme/' . $question->getParentId() . '/');
        }

        return $this->render('admin/question/edit.html.twig', [
            'form' => $form->createView(),
            //'answers' => $question->getAnswers(),
            'courseId' => $question->getCourse()->getId(),
        ]);
    }
}
