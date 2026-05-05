<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Sending Defaults
    |--------------------------------------------------------------------------
    |
    | Configuration values for the Amazon SES sending batch worker.
    |
    */

    'sending_rate_per_minute' => env('EMAIL_SENDING_RATE', 60),

    'batch_size' => env('EMAIL_BATCH_SIZE', 50),

    'retry_attempts' => env('EMAIL_RETRY_ATTEMPTS', 3),

];
