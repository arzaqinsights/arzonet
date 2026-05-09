<?php

require __DIR__.'/../../../vendor/autoload.php';
$app = require_once __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sender;
use Illuminate\Support\Facades\Http;

$email = 'monisrazakhan2001@gmail.com';
$sender = Sender::where('email', $email)->first();

if (!$sender) {
    echo "Error: Sender $email not found.\n";
    exit(1);
}

$sgKey = config('services.sendgrid.key');

echo "Requesting SendGrid verification for $email...\n";

try {
    $response = Http::withToken($sgKey)
        ->post('https://api.sendgrid.com/v3/verified_senders', [
            'nickname' => $sender->from_name,
            'from_email' => $sender->email,
            'from_name' => $sender->from_name,
            'reply_to' => $sender->email,
            'address' => 'Global Infrastructure',
            'city' => 'Cloud',
            'country' => 'Global'
        ]);

    if ($response->successful()) {
        echo "Success! Verification email has been sent to $email.\n";
        echo "Please check your inbox and click the confirm button.\n";
    } else {
        echo "Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
