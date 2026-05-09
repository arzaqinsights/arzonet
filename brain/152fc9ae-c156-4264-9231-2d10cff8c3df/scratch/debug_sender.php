<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sender;

$sender = Sender::withoutGlobalScopes()->where('email', 'hello@arzaqinsights.com')->first();

if ($sender) {
    echo "ID: " . $sender->id . "\n";
    echo "Email: " . $sender->email . "\n";
    echo "Type: " . $sender->type . "\n";
    echo "SG Key in DB: '" . $sender->sendgrid_api_key . "'\n";
    echo "Global SG Key in Config: '" . config('services.sendgrid.key') . "'\n";
} else {
    echo "Sender not found.\n";
}
