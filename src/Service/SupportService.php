<?php

namespace App\Service;

use App\Entity\Support;
use App\Message\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class SupportService
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    public function sendSupportMailMessage(Support $support): void
    {
        $this->bus->dispatch(new SendEmailMessage(
            'support@safety63.ru',
            'Запрос технической поддержки СДО',
            $this->composeMail($support)
        )); 
        
        $this->bus->dispatch(new SendEmailMessage(
            'info@safety63.ru',
            'Запрос технической поддержки СДО',
            $this->composeMail($support)
        ));
        
        $this->bus->dispatch(new SendEmailMessage(
            '1103@safety63.ru',
            'Запрос технической поддержки СДО',
            $this->composeMail($support)
        ));
    }

    private function composeMail(Support $support): string
    {
        $result = 
            'Имя : ' . $support->getName() . '<br>'
            . 'E-Mail : ' . $support->getEmail() . '<br>'
            . 'Телефон : ' . $support->getPhone() . '<br>'
            . 'Курс : ' . $support->getCourse() . '<br>'
            . 'Вопрос : ' . $support->getQuestion() . '<br>';

        return $result;
    }
}
