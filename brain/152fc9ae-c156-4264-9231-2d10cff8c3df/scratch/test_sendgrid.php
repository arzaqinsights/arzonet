<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MailService;
use App\Models\Sender;

$to = 'mkmonisraza0786@gmail.com';
$sender = Sender::where('email', 'monisrazakhan2001@gmail.com')->first();

if (!$sender) {
    echo "Error: No SendGrid sender found in database.\n";
    exit(1);
}

echo "Attempting to send test email to $to via SendGrid (Sender: {$sender->email})...\n";

try {
    $mailService = app(MailService::class);
    $messageId = $mailService->send(
        sender: $sender,
        to: $to,
        subject: 'SendGrid Test - Arzonet',
        html: '<h1>Hello!</h1><p>This is a test email from Arzonet via SendGrid.</p>',
        emailRecord: null,
        logId: null
    );

    echo "Success! Message ID: $messageId\n";
} catch (\Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
}
