<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Email;

$email = Email::latest()->first();
if ($email) {
    echo "Email ID: " . $email->id . "\n";
    echo "Email Address: " . $email->email . "\n";
    echo "Name Attribute: " . ($email->name ?? 'NULL') . "\n";
    echo "Meta Data (Raw): " . json_encode($email->meta) . "\n";
    echo "Full toArray(): " . json_encode($email->toArray()) . "\n";
} else {
    echo "No email records found.\n";
}
