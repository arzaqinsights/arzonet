<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EmailLog;
use App\Models\Campaign;

// Let's find the campaign that was likely the one in the screenshot
$campaign = Campaign::where('status', 'paused')->latest()->first() 
            ?? Campaign::where('status', 'completed')->latest()->first();

if (!$campaign) {
    echo "No campaigns found.\n";
    exit;
}

echo "Inspecting Campaign: {$campaign->name} (ID: {$campaign->id}, Status: {$campaign->status})\n";

$statuses = EmailLog::where('campaign_id', $campaign->id)
    ->select('status')
    ->distinct()
    ->pluck('status');

echo "Unique Statuses in Logs: " . implode(', ', $statuses->toArray()) . "\n";

$finished = ['sent', 'delivered', 'failed', 'bounced', 'complaint', 'spamreport', 'dropped'];
$pending = EmailLog::where('campaign_id', $campaign->id)
    ->whereNotIn('status', $finished)
    ->get();

echo "Count of logs NOT in finished list: " . $pending->count() . "\n";
if ($pending->count() > 0) {
    echo "Sample pending statuses: " . implode(', ', $pending->take(5)->pluck('status')->unique()->toArray()) . "\n";
}
