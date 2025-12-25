<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Permission;
use App\Entity\Questions;
use App\Repository\AnswerRepository;
use App\Repository\PermissionRepository;
use App\Repository\QuestionsRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FavoritesService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
        private readonly QuestionsRepository $questionsRepository,
        private readonly AnswerRepository $answerRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function setFavorites(Permission $permission, int $questionId): string
    {
        $favorites = $permission->getFavorites();

        $has = array_search($questionId, $favorites);

        if (false !== $has) {
            unset($favorites[$has]);
        }
        else {
            $favorites[] = $questionId;
        }

        sort($favorites);
        $permission->setFavorites($favorites);
        $this->permissionRepository->save($permission, true);

        if (false !== $has) {
            $html = '<img src="/img/add-favorites.svg" alt=""><div>Добавить в избранное</div>';
        }
        else {
            $html = '<img src="/img/added-favorites.svg" alt=""><div>Удалить из избранного</div>';
        }

        return $html;
    }

    public function getFavoritesQuestionData(
        Permission $permission,
        int $page = 1,
        int $perPage = 20,
    ): array {
        $result = [];

        $totalQuestions = $this->questionsRepository->getFavoritesQuestionsCount($permission);

        if (0 === $totalQuestions) {
            return [];
        }

        if (! in_array($perPage, [20, 50, 100])) {
            $perPage = 20;
        }

        $maxPages = intdiv($totalQuestions, $perPage);
        if ($totalQuestions % $perPage > 0) {
            $maxPages++;
        }

        if ($page > $maxPages) {
            $page = $maxPages;
        }

        $questions = $this->questionsRepository
            ->getFavoritesQuestionQuery($permission)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getResult();

        $favorites = $permission->getFavorites();

        /** @var Questions $question */
        foreach ($questions as $question) {
            $answers = $this->answerRepository->getAnswers($question, true);
            $answerData = [];

            foreach ($answers as $answer) {
                $answerData[] = [
                    'description' => $answer['description'],
                    'right' => $answer['isCorrect'],
                    'nom' => $answer['nom'],
                    'id' => $answer['id'],
                    'checked' => $preparationContent[$question->getId()]['answers'][$answer['id']] ?? false,
                ];
            }

            $result[] = [
                'id' => $question->getId(),
                'nom' => $question->getNom(),
                'type' => $question->getType(),
                'description' => $question->getDescription(),
                'help' => $question->getHelp(),
                'answers' => $answerData,
                'result' => $preparationContent[$question->getId()]['result'] ?? false,
                'hasRight' => $preparationContent[$question->getId()]['hasRight'] ?? false,
                'inFavorites' => in_array($question->getId(), $favorites),
            ];
        }

        $url = $this->urlGenerator->generate('app_frontend_favorites_list', ['id' => $permission->getId()]);

        return [
            'permissionId' => $permission->getId(),
            'permissionLastAccess' => $permission->getLastAccess()->getTimestamp(),
            'questions' => $result,
            'perPage' => $perPage,
            'maxPages' => $maxPages,
            'total' => $totalQuestions,
            'url' => $url,
            'themeId' => 1,
            'paginator' => $this->preparePaginator($permission, $page, $perPage, $maxPages),
        ];
    }

    private function preparePaginator(Permission $permission, int $page, int $perPage, int $maxPages): array
    {
        $paginator = [];

        if ($maxPages > PreparationService::MAX_PAGES_IN_LINE) {
            if ($page <= PreparationService::PAGES_CHANGE_PAGINATOR) {
                for ($index = 1; $index <= PreparationService::PAGES_AT_ONCE; $index++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                            'id' => $permission->getId(),
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
                    'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                        'id' => $permission->getId(),
                        'page' => $maxPages,
                        'perPage' => $perPage,
                    ]),
                    'title' => $maxPages,
                    'isActive' => false,
                ];
            } elseif ($maxPages - $page < PreparationService::PAGES_CHANGE_PAGINATOR) {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                        'id' => $permission->getId(),
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

                for ($index = $maxPages - PreparationService::PAGES_AT_ONCE + 1; $index <= $maxPages; $index++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                            'id' => $permission->getId(),
                            'page' => $index,
                            'perPage' => $perPage,
                        ]),
                        'title' => $index,
                        'isActive' => $page === $index,
                    ];
                }
            } else {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                        'id' => $permission->getId(),
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

                for ($index = $page - PreparationService::PAGES_IN_INTERVAL_PART + 2; $index <=  $page + PreparationService::PAGES_IN_INTERVAL_PART - 2; $index++) {
                    $paginator[] = [
                        'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                            'id' => $permission->getId(),
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
                    'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                        'id' => $permission->getId(),
                        'page' => $maxPages,
                        'perPage' => $perPage,
                    ]),
                    'title' => $maxPages,
                    'isActive' => false,
                ];
            }
        } else {
            for ($index = 1; $index <= $maxPages; $index++) {
                $paginator[] = [
                    'url' => $this->urlGenerator->generate('app_frontend_favorites_list', [
                        'id' => $permission->getId(),
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
