<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Bulk Email Provider
    |--------------------------------------------------------------------------
    | Options: 'ses', 'sendgrid'
    | This decides which infrastructure to use when a user selects 'Bulk Mode'.
    */
    'bulk_provider' => env('DEFAULT_BULK_PROVIDER', 'ses'),

    /*
    |--------------------------------------------------------------------------
    | Default Throughput Profiles
    |--------------------------------------------------------------------------
    */
    'profiles' => [
        'bulk' => [
            'emails_per_second' => 50,
            'emails_per_minute' => 3000,
            'daily_limit' => 500000,
        ],
        'normal' => [
            'emails_per_second' => 1,
            'emails_per_minute' => 30,
            'daily_limit' => 500,
        ]
    ],

    'cost_per_email' => 0.0001,

    'limits' => [
        'daily' => 10000,
        'weekly' => 50000,
        'monthly' => 200000,
    ],

    'upload' => [
        'max_file_size' => 102400, // 100MB in KB
    ],
];
