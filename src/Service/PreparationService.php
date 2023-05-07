<?php

namespace App\Service;

use App\Entity\Questions;
use App\Entity\Permission;
use App\Repository\AnswerRepository;
use App\Repository\QuestionsRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreparationService
{
    public function __construct(
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function getQuestionData(
        Permission $permission,
        ?int $themeId = null,
        int $page = 1,
        int $perPage = 20,
    ): array {
        $result = [];

        $totalQuestions  = $this->questionsRepository->getQuestionsCount($permission->getCourse(), $themeId);

        if (!in_array($perPage, [20, 50, 100])) {
            $perPage = 20;
        }

        $maxPages = intdiv($totalQuestions, $perPage);
        if ($totalQuestions % $perPage > 0 ) {
            $maxPages ++;
        }

        if ($page > $maxPages) {
            $page = $maxPages;
        }

        $questions = $this->questionsRepository
            ->getQuestionQuery($permission->getCourse(), $themeId)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getResult();

        /** @var Questions $question */
        foreach($questions as $question) {
            $answers = $this->answerRepository->getAnswers($question, true);
            $answerData = [];

            foreach ($answers as $answer) {
                $answerData[] = [
                    'description' => $answer['description'],
                    'right' => $answer['isCorrect'],
                    'nom' => $answer['nom'],
                    
                ];
            }

            $result[] = [
                'id' => $question->getId(),
                'nom' =>$question->getNom(),
                'type' => $question->getType(),
                'description' => $question->getDescription(),
                'help' => $question->getHelp(),
                'answers' => $answerData,
            ];
        }

        return [
            'permissionId' => $permission->getId(),
            'permissionLastAccess' => $permission->getLastAccess()->getTimestamp(),
            'questions' => $result,
            'page' => $page,
            'perPage' => $perPage,
            'maxPages' => $maxPages,
            'url' => $this->urlGenerator->generate('app_frontend_preparation_interactive', ['id' => $permission->getId()]),
        ];
    }
}
