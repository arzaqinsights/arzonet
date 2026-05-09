<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EmailLog;
use App\Models\Campaign;

$log = EmailLog::withoutGlobalScopes()->where('status', 'failed')->latest()->first();

if ($log) {
    echo "Log ID: " . $log->id . "\n";
    echo "Email: " . $log->email_address . "\n";
    echo "Error: " . $log->error_message . "\n";
    
    $campaign = Campaign::withoutGlobalScopes()->find($log->campaign_id);
    if ($campaign) {
        echo "Campaign: " . $campaign->name . "\n";
        $sender = \App\Models\Sender::withoutGlobalScopes()->find($campaign->sender_id);
        if ($sender) {
            echo "Sender Email: " . $sender->email . "\n";
            echo "Sender Type: " . $sender->type . "\n";
            echo "Sender SG Key exists: " . ($sender->sendgrid_api_key ? 'YES' : 'NO') . "\n";
            if ($sender->sendgrid_api_key) {
                echo "Sender SG Key (masked): " . substr($sender->sendgrid_api_key, 0, 10) . "...\n";
            }
        }
    }
} else {
    echo "No failed logs found.\n";
}
