<?php

namespace App\MessageHandler;

use App\Message\SendEmailMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsMessageHandler]
class SendEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(SendEmailMessage $message): void
    {
        $email = (new Email())
            ->from('ucoks@safety63.ru')
            ->to($message->getContent()['to'])
            ->subject($message->getContent()['subject'])
            ->text($message->getContent()['content']);

        $this->mailer->send($email);
    }
}
