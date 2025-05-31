<?php

declare(strict_types=1);

use App\EventListener\CourseRemoveEventListener;
use App\EventListener\ModuleRemoveEventListener;
use App\EventListener\ModuleSectionRemoveEventListener;
use App\EventListener\PermissionEventListener;
use App\EventListener\QuestionRemoveEventListener;
use App\TwigExtension\HistoryTwigExtension;
use App\TwigExtension\TwigExtension;
use Monolog\Processor\WebProcessor;
// use Symfony\Bridge\Monolog\Processor\TokenProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('doc_upload_directory', '%kernel.project_dir%/public/storage/doc');
    $parameters->set('course_upload_directory', '%kernel.project_dir%/public/storage/course');
    $parameters->set('report_upload_directory', '%kernel.project_dir%/public/storage/report');
    $parameters->set('exchange_1c_upload_directory', '%kernel.project_dir%/public/storage/exchange1c');
    $parameters->set('view_directory', '%kernel.project_dir%/templates');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$courseUploadPath', '%kernel.project_dir%/public/storage/course')
        ->bind('$reportUploadPath', '%kernel.project_dir%/public/storage/report')
        ->bind('$exchange1cUploadDirectory', '%kernel.project_dir%/public/storage/exchange1c')
        ->bind('$avatarUploadPath', '%kernel.project_dir%/public/storage/avatar')
        ->bind('$greenApiIdInstance', '%env(GREEN_API_ID_INSTANCE)%')
        ->bind('$greenApiTokenInstance', '%env(GREEN_API_API_TOKEN_INSTANCE)%');

    $services->load('App\\', __DIR__ . '/../src/')
        ->exclude([
        __DIR__ . '/../src/DependencyInjection/',
        __DIR__ . '/../src/Entity/',
        __DIR__ . '/../src/Kernel.php',
    ]);

    $services->set(TwigExtension::class)
        ->tag('twig.extension');

    $services->set(HistoryTwigExtension::class)
        ->tag('twig.extension');

    $services->set('monolog.processor.web', WebProcessor::class)
        ->tag('monolog.processor');

    // $services->set('monolog.processor.token', TokenProcessor::class)
    //     ->tag('monolog.processor');

    $services->set(CourseRemoveEventListener::class)
        ->tag('doctrine.event_listener', [
        'event' => 'preRemove',
    ]);

    $services->set(QuestionRemoveEventListener::class)
        ->tag('doctrine.event_listener', [
        'event' => 'preRemove',
    ]);

    $services->set(PermissionEventListener::class)
        ->tag('doctrine.event_listener', [
        'event' => 'preRemove',
    ])
        ->tag('doctrine.event_listener', [
        'event' => 'prePersist',
    ])
        ->tag('doctrine.event_listener', [
        'event' => 'postPersist',
    ]);

    $services->set(ModuleSectionRemoveEventListener::class)
        ->tag('doctrine.event_listener', [
        'event' => 'preRemove',
    ]);

    $services->set(ModuleRemoveEventListener::class)
        ->tag('doctrine.event_listener', [
        'event' => 'preRemove',
    ]);
};
