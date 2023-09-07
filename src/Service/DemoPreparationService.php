<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Questions;
use App\Repository\AnswerRepository;
use App\Repository\QuestionsRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DemoPreparationService
{
    private const MAX_PAGES_IN_LINE = 30;
    private const PAGES_AT_ONCE = 5;
    private const PAGES_CHANGE_PAGINATOR = 4;

    public function __construct(
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}
    
    public function getQuestionDataForCourse(
        Course $course,
        ?int $themeId = null,
        int $page = 1,
        int $perPage = 20,
    ): array {
        $result = [];

        $totalQuestions  = $this->questionsRepository->getQuestionsCount($course, $themeId);

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
            ->getQuestionQuery($course, $themeId)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getResult();

        /** @var Questions $question */
        foreach ($questions as $question) {
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

        if ($course->getType() === Course::INTERACTIVE) {
            $url = $this->urlGenerator->generate('app_demo_preparation_interactive', ['id' => $course->getId()]);
        } else {
            $url = $this->urlGenerator->generate('app_demo_preparation_course', ['id' => $course->getId(), 'themeId' => $themeId]);
        }

        return [
            'courseId' => $course->getId(),
            'questions' => $result,
            'page' => $page,
            'perPage' => $perPage,
            'maxPages' => $maxPages,
            'url' => $url,
            'paginator' => $this->preparePaginator($course, $page, $perPage, $maxPages, $themeId),
        ];
    }

    private function preparePaginator(Course $course, int $page, int $perPage, int $maxPages, ?int $themeId = null): array
    {
        $paginator = [];

        if ($maxPages > self::MAX_PAGES_IN_LINE) {
            if ($page <= self::PAGES_CHANGE_PAGINATOR) {
                for ($index = 1; $index <= self::PAGES_AT_ONCE; $index ++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                            'id' => $course->getId(), 
                            'themeId' => $themeId, 
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];
                
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                        'id' => $course->getId(), 
                        'themeId' => $themeId, 
                        'page' => $maxPages,
                        'perPage' => $perPage,
                    ]),
                    'title' => $maxPages,
                    'isActive' => false,
                ];
                
            } elseif ($maxPages - $page < self::PAGES_CHANGE_PAGINATOR) {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                        'id' => $course->getId(), 
                        'themeId' => $themeId, 
                        'page' => 1,
                        'perPage' => $perPage,
                    ]),
                    'title' => 1,
                    'isActive' => false,
                ];

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                for ($index = $maxPages - self::PAGES_AT_ONCE + 1; $index <= $maxPages; $index ++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                            'id' => $course->getId(), 
                            'themeId' => $themeId, 
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }

            } else {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                        'id' => $course->getId(), 
                        'themeId' => $themeId, 
                        'page' => 1,
                        'perPage' => $perPage,
                    ]),
                    'title' => 1,
                    'isActive' => false,
                ];

                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                for ($index = $page - self::PAGES_CHANGE_PAGINATOR + 2; $index <=  $page + self::PAGES_CHANGE_PAGINATOR - 2; $index ++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                            'id' => $course->getId(), 
                            'themeId' => $themeId, 
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }
                
                $paginator[] = [
                    'url' => null,
                    'title' => '...',
                    'isActive' => true,
                ];

                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                        'id' => $course->getId(), 
                        'themeId' => $themeId, 
                        'page' => $maxPages,
                        'perPage' => $perPage,
                    ]),
                    'title' => $maxPages,
                    'isActive' => false,
                ];

            }
        } else {
            for ($index = 1; $index <= $maxPages; $index ++) {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_demo_preparation_course_one', [
                        'id' => $course->getId(), 
                        'themeId' => $themeId, 
                        'page' => $index,
                        'perPage' => $perPage,
                    ]),
                    'title' => $index,
                    'isActive' => $page === $index
                ];
            }
        }

        return $paginator;
    }
}
