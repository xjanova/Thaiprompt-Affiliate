<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // TPIX-specific queues
        'tpix-high' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'tpix-high',
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => false,
        ],

        'tpix-default' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'tpix-default',
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => false,
        ],

        'tpix-low' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'tpix-low',
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
