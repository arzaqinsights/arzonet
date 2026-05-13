<?php

use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'mkmonisraza0786@gmail.com';

try {
    echo "Attempting to send Welcome Mail to: $email...\n";
    
    Mail::to($email)->send(new WelcomeMail());
    
    echo "Success! Welcome Mail sent to $email.\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
