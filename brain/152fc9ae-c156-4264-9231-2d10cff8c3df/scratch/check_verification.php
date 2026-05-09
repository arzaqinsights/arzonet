<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sender;
use Illuminate\Support\Facades\Http;

$email = 'monisrazakhan2001@gmail.com';
$sgKey = config('services.sendgrid.key');

echo "Checking SendGrid verification status for $email...\n";

try {
    $response = Http::withToken($sgKey)
        ->get('https://api.sendgrid.com/v3/verified_senders');

    if ($response->successful()) {
        $results = $response->json()['results'] ?? [];
        $found = false;
        
        foreach ($results as $v) {
            if (strtolower($v['from_email']) === strtolower($email)) {
                $found = true;
                if ($v['verified']) {
                    echo "YES! $email is now VERIFIED in SendGrid.\n";
                    // Update database
                    $sender = Sender::where('email', $email)->first();
                    if ($sender) {
                        $sender->update(['status' => 'verified', 'verified_at' => now()]);
                        echo "Database updated to verified status.\n";
                    }
                } else {
                    echo "Pending: $email is in SendGrid but NOT YET VERIFIED. Please click the link in your email.\n";
                }
                break;
            }
        }
        
        if (!$found) {
            echo "Not Found: $email is not even in the SendGrid verified senders list yet.\n";
        }
    } else {
        echo "Failed to fetch from SendGrid: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
