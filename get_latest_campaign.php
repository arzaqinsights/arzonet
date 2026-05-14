<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;

$campaign = Campaign::latest()->first();

if (!$campaign) {
    echo "No campaigns found.\n";
    exit;
}

echo "Latest Campaign: " . $campaign->name . " (ID: " . $campaign->id . ", Status: " . $campaign->status . ")\n";
