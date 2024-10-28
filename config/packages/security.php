<?php

declare(strict_types=1);

use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => 'auto',
        ],
        'providers' => [
            'app_user_provider' => [
                'entity' => [
                    'class' => User::class,
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider' => 'app_user_provider',
                'custom_authenticator' => LoginFormAuthenticator::class,
                'logout' => [
                    'path' => 'app_logout',
                ],
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => 'ROLE_USER',
            'ROLE_SUPER_ADMIN' => [
                'ROLE_ADMIN',
                'ROLE_USER',
                'ROLE_ALLOWED_TO_SWITCH',
            ],
        ],
        'access_control' => [
            [
                'path' => '^/admin',
                'roles' => 'ROLE_ADMIN',
            ],
            [
                'path' => '^/login',
                'roles' => 'PUBLIC_ACCESS',
            ],
            [
                'path' => '^/demo',
                'roles' => 'PUBLIC_ACCESS',
            ],
            [
                'path' => '^/',
                'roles' => 'ROLE_USER',
            ],
        ],
    ]);
    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => [
                    'algorithm' => 'auto',
                    'cost' => 4,
                    'time_cost' => 3,
                    'memory_cost' => 10,
                ],
            ],
        ]);
    }
};
