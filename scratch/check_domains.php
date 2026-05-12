<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- DOMAIN AUTHENTICATION CHECK ---\n\n";

$apiKey = config('services.sendgrid.key');

echo "Testing Domain Authentications...\n";
$response = \Illuminate\Support\Facades\Http::withToken($apiKey)
    ->get('https://api.sendgrid.com/v3/whitelabel/domains');

if ($response->successful()) {
    echo "SUCCESS: Fetched authenticated domains.\n";
    $domains = $response->json();
    foreach ($domains as $domain) {
        echo "- Domain: " . $domain['domain'] . " (Verified: " . ($domain['valid'] ? 'YES' : 'NO') . ")\n";
    }
} else {
    echo "FAILED to fetch domains.\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
}

echo "\nChecking IP Whitelabel...\n";
$response = \Illuminate\Support\Facades\Http::withToken($apiKey)
    ->get('https://api.sendgrid.com/v3/whitelabel/ips');
echo "IP Whitelabel Status: " . $response->status() . "\n";
