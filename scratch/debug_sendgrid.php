<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- PRODUCTION SENDGRID DIAGNOSTIC ---\n\n";

$apiKey = config('services.sendgrid.key');

if (empty($apiKey)) {
    echo "ERROR: SENDGRID_API_KEY is not set in config. Check your .env or run php artisan config:clear\n";
    exit;
}

echo "API Key Found: " . substr($apiKey, 0, 10) . "...\n";

// Test 1: Check Credits
echo "Testing Connection to SendGrid API...\n";
$response = \Illuminate\Support\Facades\Http::withToken($apiKey)
    ->get('https://api.sendgrid.com/v3/user/credits');

if ($response->successful()) {
    echo "SUCCESS: Connected to SendGrid.\n";
    echo "Credits Response: " . $response->body() . "\n";
} else {
    echo "FAILED: SendGrid rejected the request.\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
}

// Test 2: Check Verified Senders
echo "\nTesting Verified Senders...\n";
$response = \Illuminate\Support\Facades\Http::withToken($apiKey)
    ->get('https://api.sendgrid.com/v3/verified_senders');

if ($response->successful()) {
    echo "SUCCESS: Fetched verified senders.\n";
    echo "Data: " . $response->body() . "\n";
} else {
    echo "FAILED to fetch verified senders.\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
}
