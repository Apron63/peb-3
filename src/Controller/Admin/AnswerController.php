<?php

namespace App\Controller\Admin;

use App\Entity\Answer;
use App\Entity\Questions;
use App\Form\Admin\AnswerEditType;
use App\Decorator\MobileController;
use App\Repository\AnswerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnswerController extends MobileController
{
    public function __construct(
        readonly AnswerRepository $answerRepository
     ) {}

    #[Route('/admin/answer/create/{id<\d+>}/', name: 'admin_answer_create')]
    public function adminAnswerCreate(Request $request, Questions $question): Response
    {
        $answer = new Answer ();
        $answer->setQuestion($question);
        $nom = $this->answerRepository->getNextNom($question);
        $answer->setNom($nom);
        $form = $this->createForm(AnswerEditType::class, $answer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->answerRepository->save($answer, true);
            }

            return $this->redirect('/admin/question/' . $question->getId() . '/');
        }

        return $this->mobileRender('admin/answer/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/answer/{id<\d+>}/', name: 'admin_answer_edit')]
    public function adminAnswerEdit(Request $request, Answer $answer): Response
    {
        $form = $this->createForm(AnswerEditType::class, $answer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $this->answerRepository->save($answer, true);
            }

            return $this->redirect('/admin/question/' . $answer->getQuestion()->getId() . '/');
        }

        return $this->mobileRender('admin/answer/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/answer/delete/{id<\d+>}/', name: 'admin_answer_delete')]
    public function adminAnswerDelete(Request $request, Answer $answer): Response
    {
        $questionId = $answer->getQuestion() ? $answer->getQuestion()->getId() : null;
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->answerRepository->remove($answer, true);
        }

        return $this->redirect('/admin/question/' . $questionId . '/');
    }
}
