<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;

$c = Campaign::withoutGlobalScopes()->latest()->first();
if ($c) {
    echo "Campaign: " . $c->name . " (ID: " . $c->id . ")\n";
    echo "Status: " . $c->status . "\n";
    echo "Template: " . ($c->template ? $c->template->name . " (ID: " . $c->template_id . ")" : 'NULL') . "\n";
    echo "Sender: " . ($c->sender ? $c->sender->email . " (ID: " . $c->sender_id . ")" : 'NULL') . "\n";
    echo "Email List ID: " . $c->email_list_id . "\n";
}
