<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, you may specify the entire domain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store its
    | data. You may use any of the Redis connections defined in your
    | application's "database" configuration file.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix if you are running multiple installations
    | of Horizon on the same Redis server.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the dashboard should display
    | a warning for a given queue's wait time. This can help you quickly
    | determine when a queue is beginning to back up.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | These options determine how many minutes Horizon will keep different
    | types of jobs in its recent and failed logs. This helps you keep
    | your Redis server's memory usage under control.
    |
    */

    'trim' => [
        'recent' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait for all jobs to finish before exiting. This can be useful
    | when running Horizon in a local environment.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure the maximum amount of memory that
    | a Horizon worker may consume before it is terminated and restarted.
    | This helps ensure that your workers do not consume too much memory.
    |
    */

    'memory_limit' => 256,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings that should be used by
    | Horizon. These settings will be applied to each of the environments
    | that you have defined.
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 5,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 600, // Match ImportEmailChunkJob timeout
            'nice' => 0,
        ],
        'supervisor-bulk' => [
            'connection' => 'redis',
            'queue' => ['bulk'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 30, // Optimized for parallel API calls
            'minProcesses' => 5,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 10,
        ],
    ],

    'environments' => [
        'production' => [

            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['high', 'default'],
                'balance' => 'auto',
                'maxProcesses' => 4,
                'memory' => 256,
                'tries' => 3,
                'timeout' => 600, // Large import chunk jobs need up to 600s
            ],

            'supervisor-ses' => [
                'connection' => 'redis',
                'queue' => ['bulk-ses'],
                'balance' => 'auto',
                'maxProcesses' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 120,
            ],

            'supervisor-sendgrid' => [
                'connection' => 'redis',
                'queue' => ['bulk-sendgrid'],
                'balance' => 'auto',
                'maxProcesses' => 10,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 120,
            ],

            'supervisor-smtp' => [
                'connection' => 'redis',
                'queue' => ['bulk-smtp'],
                'balance' => 'auto',
                'maxProcesses' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 120,
            ],

            'supervisor-webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 300,
            ],

            'supervisor-segments' => [
                'connection' => 'redis',
                'queue' => ['segments'],
                'balance' => 'auto',
                'maxProcesses' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 300,
            ],

        ],

        'local' => [

            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['high', 'default'],
                'balance' => 'auto',
                'maxProcesses' => 3,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 60,
            ],

            'supervisor-ses' => [
                'connection' => 'redis',
                'queue' => ['bulk-ses'],
                'balance' => false,
                'maxProcesses' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 900,
            ],

            'supervisor-sendgrid' => [
                'connection' => 'redis',
                'queue' => ['bulk-sendgrid'],
                'balance' => false,
                'maxProcesses' => 4,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 900,
            ],

            'supervisor-smtp' => [
                'connection' => 'redis',
                'queue' => ['bulk-smtp'],
                'balance' => false,
                'maxProcesses' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 900,
            ],

            'supervisor-webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => false,
                'maxProcesses' => 1,
                'memory' => 128,
                'tries' => 1,
                'timeout' => 300,
            ],

            'supervisor-segments' => [
                'connection' => 'redis',
                'queue' => ['segments'],
                'balance' => false,
                'maxProcesses' => 1,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 300,
            ],

        ],
    ],
];
