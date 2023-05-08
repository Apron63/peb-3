<?php

namespace App\Message;

class SendEmailMessage
{
    private ?string $to = null;
    private ?string $subject = null;
    private ?string $content = null;

    public function __construct(?string $to, ?string $subject, ?string $content)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->content = $content;
    }

    public function getContent(): array
    {
        return [
            'to' => $this->to,
            'subject' => $this->subject,
            'content' => $this->content,
        ];
    }
}
