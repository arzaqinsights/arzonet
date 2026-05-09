<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.sendgrid.key');
echo "Testing Key: " . substr($apiKey, 0, 10) . "...\n";

$response = \Illuminate\Support\Facades\Http::withToken($apiKey)
    ->get('https://api.sendgrid.com/v3/user/profile');

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
