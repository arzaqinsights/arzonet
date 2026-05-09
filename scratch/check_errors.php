<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = \App\Models\EmailLog::where('campaign_id', 54)->get();
foreach ($logs as $log) {
    echo "Email: {$log->email_address}, Status: {$log->status}, Error: {$log->error_message}\n";
}
