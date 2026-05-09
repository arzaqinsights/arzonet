<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$listId = 47;
$emails = \App\Models\Email::where('email_list_id', $listId)->get();
foreach ($emails as $e) {
    echo "Email: {$e->email}, Status: {$e->status}, Health: {$e->email_status}, Score: {$e->email_score}\n";
}
