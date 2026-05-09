<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sender;
use App\Services\MailService;

$sender = Sender::withoutGlobalScopes()->where('type', 'sendgrid')->latest()->first();

if (!$sender) {
    echo "No SendGrid sender found.\n";
    exit;
}

echo "Using Sender: " . $sender->email . "\n";

$mailService = app(MailService::class);

try {
    $to = "mkmonisraza0786@gmail.com";
    $subject = "Test Email from Arzonet (SendGrid Check)";
    $html = "<h1>Success!</h1><p>If you see this, SendGrid permissions are fixed.</p>";
    
    $result = $mailService->send($sender, $to, $subject, $html);
    
    echo "Result: " . $result . "\n";
    echo "Email sent successfully!\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
