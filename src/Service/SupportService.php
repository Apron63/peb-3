<?php

declare (strict_types=1);

namespace App\Service;

use App\Message\SendEmailMessage;
use App\RequestDto\SupportDto;
use Symfony\Component\Messenger\MessageBusInterface;

class SupportService
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function sendSupportMailMessage(SupportDto $supportDto): void
    {
        $this->bus->dispatch(new SendEmailMessage(
            'uc@safety63.ru',
            'Запрос технической поддержки СДО PROобучение',
            $this->composeMail($supportDto)
        ));

        $this->bus->dispatch(new SendEmailMessage(
            'info@safety63.ru',
            'Запрос технической поддержки СДО PROобучение',
            $this->composeMail($supportDto)
        ));

        $this->bus->dispatch(new SendEmailMessage(
            '1103@safety63.ru',
            'Запрос технической поддержки СДО PROобучение',
            $this->composeMail($supportDto)
        ));
    }

    private function composeMail(SupportDto $supportDto): string
    {
        return 'ФИО полностью : ' . $supportDto->name . '<br>'
        . 'E-Mail : ' . $supportDto->email . '<br>'
        . 'Телефон : ' . $supportDto->phone . '<br>'
        . 'Курс : ' . $supportDto->course . '<br>'
        . 'Вопрос : ' . $supportDto->question . '<br>';
    }
}
