<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains deployment-specific configurations for TP-Affiliate
    |
    */

    'maintenance' => [
        'enabled' => env('DEPLOY_MAINTENANCE_MODE', true),
        'retry_after' => env('DEPLOY_RETRY_AFTER', 60),
        'secret' => env('DEPLOY_MAINTENANCE_SECRET'),
    ],

    'git' => [
        'enabled' => env('DEPLOY_GIT_PULL', true),
        'branch' => env('DEPLOY_GIT_BRANCH', 'main'),
    ],

    'composer' => [
        'enabled' => env('DEPLOY_COMPOSER_INSTALL', true),
        'flags' => [
            '--no-dev',
            '--optimize-autoloader',
            '--no-interaction',
        ],
    ],

    'migrations' => [
        'enabled' => env('DEPLOY_RUN_MIGRATIONS', true),
        'force' => true,
    ],

    'cache' => [
        'enabled' => env('DEPLOY_CACHE_ENABLED', true),
        'types' => [
            'config' => true,
            'routes' => true,
            'views' => true,
            'events' => false,
        ],
    ],

    'optimization' => [
        'enabled' => env('DEPLOY_OPTIMIZATION_ENABLED', true),
        'autoloader' => true,
        'clear_before' => true,
    ],

    'backup' => [
        'enabled' => env('DEPLOY_BACKUP_ENABLED', false),
        'database' => true,
        'files' => false,
    ],

    'notifications' => [
        'enabled' => env('DEPLOY_NOTIFICATIONS_ENABLED', false),
        'channels' => ['mail', 'slack'],
        'slack_webhook' => env('DEPLOY_SLACK_WEBHOOK'),
        'email' => env('DEPLOY_NOTIFICATION_EMAIL'),
    ],

    'rollback' => [
        'enabled' => env('DEPLOY_ROLLBACK_ENABLED', true),
        'keep_releases' => env('DEPLOY_KEEP_RELEASES', 5),
    ],

    'health_check' => [
        'enabled' => env('DEPLOY_HEALTH_CHECK', true),
        'url' => env('APP_URL') . '/up',
        'timeout' => 30,
    ],
];
