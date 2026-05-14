<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\EmailLog;

$campaign = Campaign::where('name', 'LIKE', '%16:23%')->first();

if (!$campaign) {
    echo "Campaign not found by name.\n";
    // Search for ANY paused campaign
    $campaign = Campaign::where('status', 'paused')->first();
}

if (!$campaign) {
    echo "No paused campaigns found.\n";
    exit;
}

echo "Found Campaign: {$campaign->name} (ID: {$campaign->id}, Status: {$campaign->status})\n";

$statusBreakdown = EmailLog::where('campaign_id', $campaign->id)
    ->select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "\nStatus Breakdown:\n";
foreach ($statusBreakdown as $row) {
    echo "- {$row->status}: {$row->count}\n";
}

$finished = ['sent', 'delivered', 'failed', 'bounced', 'complaint', 'spamreport', 'dropped'];
$toSend = EmailLog::where('campaign_id', $campaign->id)
    ->whereNotIn('status', $finished)
    ->pluck('email_id');

echo "\nEligible for resume: " . $toSend->count() . "\n";
