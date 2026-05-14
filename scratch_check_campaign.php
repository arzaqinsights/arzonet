<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Campaign::latest()->first();
if ($c) {
    echo "Campaign ID: " . $c->id . " | emails_per_minute: " . $c->emails_per_minute . PHP_EOL;
}
$safeRate = app(\App\Services\QuotaManager::class)->getSafeRate();
echo "Safe Rate: " . $safeRate . PHP_EOL;
