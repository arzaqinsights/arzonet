<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$sgKey = config('services.sendgrid.key');

echo "Verifying API Key validity and scopes...\n";

try {
    $response = Http::withToken($sgKey)
        ->get('https://api.sendgrid.com/v3/scopes');

    if ($response->successful()) {
        echo "API Key is VALID.\n";
        $scopes = $response->json()['scopes'] ?? [];
        if (in_array('mail.send', $scopes)) {
            echo "Permission 'mail.send' is PRESENT.\n";
        } else {
            echo "CRITICAL: 'mail.send' permission is MISSING from this API Key!\n";
        }
    } else {
        echo "API Key is INVALID: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
