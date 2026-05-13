<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$accounts = \App\Models\WhatsAppAccount::all();
echo "Total Accounts: " . $accounts->count() . "\n";
foreach ($accounts as $account) {
    echo "ID: " . $account->id . " - Phone: " . $account->phone_number . " - Status: " . $account->status . "\n";
}
