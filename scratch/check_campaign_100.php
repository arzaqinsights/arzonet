<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\Campaign::find(100);
if (!$c) {
    echo "Campaign 100 not found\n";
    exit;
}
echo "Status: " . $c->status . "\n";
echo "Pending Logs: " . $c->logs()->where('status', 'pending')->count() . "\n";
echo "Failed Logs: " . $c->logs()->where('status', 'failed')->count() . "\n";
echo "Sent Logs: " . $c->logs()->where('status', 'sent')->count() . "\n";
