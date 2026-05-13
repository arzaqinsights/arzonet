<?php

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\Notification;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$targetEmail = 'ighosh.1457@gmail.com';

// We need a user object that can generate a verification URL
$user = User::first();
if (!$user) {
    echo "No users found in database. Please create a user first.\n";
    exit;
}

// We will send the notification to the target email but using the first user's context
// to satisfy the VerifyEmail requirements (like generating a signed URL).
// Note: We are using AnonymousNotifiable but with a custom toMail if possible, 
// or just manually triggering it.

try {
    echo "Attempting to send verification email to: $targetEmail...\n";
    
    // We use a custom notification that doesn't rely on signed URLs for this simple test,
    // OR we just use the existing one but force the destination.
    
    Notification::route('mail', $targetEmail)
        ->notify(new class extends CustomVerifyEmail {
            public function toMail($notifiable) {
                // Manually provide a dummy URL to avoid calling verificationUrl() which fails on AnonymousNotifiable
                return $this->buildMailMessage("https://admin.arzonet.com/verify-email/999/dummy-hash");
            }
        });
        
    echo "Success! Please check the inbox (and spam folder) of $targetEmail.\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
