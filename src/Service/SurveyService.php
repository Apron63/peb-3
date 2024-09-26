<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Permission;
use App\Entity\Survey;
use App\Message\SendEmailMessage;
use App\Repository\SurveyRepository;
use App\RequestDto\SurveyDto;
use Symfony\Component\Messenger\MessageBusInterface;

class SurveyService
{
    public function __construct(
        private readonly SurveyRepository $surveyRepository,
        private readonly MessageBusInterface $bus,
    ) {}

    public function saveSurvey(Permission $permission, SurveyDto $surveyDto): void
    {
        $survey = new Survey();

        $survey
            ->setUser($permission->getUser())
            ->setCourse($permission->getCourse())
            ->setQuestion1($surveyDto->question1)
            ->setQuestion2($surveyDto->question2)
            ->setQuestion3($surveyDto->question3)
            ->setQuestion4($surveyDto->question4);

        $this->surveyRepository->save($survey, true);

        $emailContent = $this->composeMail($permission, $surveyDto);

        $this->bus->dispatch(new SendEmailMessage(
            '1103@safety63.ru',
            'Результаты опроса',
            $emailContent,
        ));

        $this->bus->dispatch(new SendEmailMessage(
            '1105@safety63.ru',
            'Результаты опроса',
            $emailContent,
        ));
    }

    private function composeMail(Permission $permission, SurveyDto $surveyDto): string
    {
        return 'ФИО слушателя : ' . $permission->getUser()->getFullName() . '<br>'
        . 'Курс : ' . $permission->getCourse()->getName() . '<br>'
        . 'Курс полезен для Вас : ' . $surveyDto->question1 . '<br>'
        . 'Насколько материал курса соответствует вашим ожиданиям? Что бы вы предложили изменить/улучшить : ' . $surveyDto->question2 . '<br>'
        . 'Вам удобно и понятно пользоваться обучающей платформой : ' . $surveyDto->question3 . '<br>'
        . 'Ваши пожелания и предложения по обучающей платформе. Что нам изменить/улучшить в платформе : ' . $surveyDto->question4 . '<br>';
    }
}
