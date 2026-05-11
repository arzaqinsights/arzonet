<?php

return [
    'app_id' => env('CASHFREE_APP_ID'),
    'secret_key' => env('CASHFREE_SECRET_KEY'),
    'mode' => env('CASHFREE_MODE', app()->environment('production') ? 'production' : 'sandbox'),
];
