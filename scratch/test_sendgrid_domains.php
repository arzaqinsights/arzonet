<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

$senders = [
    'billing@arzonet.com',
    'welcome@arzonet.com',
    'no-reply@arzonet.com'
];

$toEmail = 'arzaqinsights@gmail.com'; // Some test email, or we can use admin.

foreach ($senders as $sender) {
    echo "Testing sender: {$sender}...\n";
    try {
        Mail::raw("This is a test email sent from {$sender}.", function (Message $message) use ($sender, $toEmail) {
            $message->to($toEmail)
                    ->from($sender, 'Arzonet Test')
                    ->subject("Test from {$sender}");
        });
        echo "✅ Success: Mail sent from {$sender}!\n\n";
    } catch (\Exception $e) {
        echo "❌ Failed: {$sender}\n";
        echo "Error: " . $e->getMessage() . "\n\n";
    }
}
