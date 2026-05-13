<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sender;

$senders = Sender::whereNotNull('sendgrid_api_key')->get();
foreach ($senders as $sender) {
    echo "ID: " . $sender->id . " | Name: " . $sender->from_name . " | API Key: " . substr($sender->sendgrid_api_key, 0, 10) . "...\n";
    
    $ch = curl_init('https://api.sendgrid.com/v3/user/credits');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $sender->sendgrid_api_key
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    if (isset($data['remain'])) {
        echo "  Remaining: " . $data['remain'] . " / " . $data['total'] . " (" . $data['reset_frequency'] . ")\n";
    } else {
        echo "  Error: " . $response . "\n";
    }
}
