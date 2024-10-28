<?php

declare(strict_types=1);

use App\Message\CourseCopyMessage;
use App\Message\CourseUploadMessage;
use App\Message\Query1CUploadMessage;
use App\Message\QuestionUploadMessage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'messenger' => [
            'failure_transport' => 'failed',
            'transports' => [
                'async' => [
                    'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%',
                    'options' => [
                        'use_notify' => true,
                        'check_delayed_interval' => 60000,
                    ],
                    'retry_strategy' => [
                        'max_retries' => 1,
                        'multiplier' => 2,
                    ],
                ],
                'failed' => 'doctrine://default?queue_name=failed',
            ],
            'routing' => [
                SendEmailMessage::class => 'async',
                \App\Message\SendEmailMessage::class => 'async',
                CourseUploadMessage::class => 'async',
                Query1CUploadMessage::class => 'async',
                QuestionUploadMessage::class => 'async',
                CourseCopyMessage::class => 'async',
            ],
        ],
    ]);
};
