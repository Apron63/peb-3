<?php

namespace App\EventListener;

use App\Entity\Questions;
use App\Repository\AnswerRepository;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class QuestionRemoveEventListener
{
    public function __construct(
        readonly AnswerRepository $answerRepository
    ) {}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Questions) {
            return;
        }

        $this->answerRepository->removeAnswersForQuestion($entity);
    }
}
