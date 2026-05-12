<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- REAL-TIME SEND TEST ---\n\n";

$sender = \App\Models\Sender::where('email', 'hello@arzaqinsights.com')->first();
$to = 'arzaqinsights@gmail.com'; // Testing to your email
$subject = 'Test from Arzonet Production Diagnostic';
$html = '<h1>Hello!</h1><p>Testing SendGrid connection and limits.</p>';

$mailService = app(\App\Services\MailService::class);

try {
    echo "Attempting to send via MailService...\n";
    $result = $mailService->send($sender, $to, $subject, $html);
    echo "SUCCESS! Message ID: " . $result . "\n";
} catch (\Exception $e) {
    echo "FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
