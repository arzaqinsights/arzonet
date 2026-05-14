<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;
use App\Models\EmailLog;

$campaignId = 39; // From the screenshot
$campaign = Campaign::find($campaignId);

if (!$campaign) {
    echo "Campaign not found.\n";
    exit;
}

echo "Campaign: " . $campaign->name . " (Status: " . $campaign->status . ")\n";
echo "Total Recipients: " . $campaign->total_recipients . "\n";
echo "Sent Count: " . $campaign->sent_count . "\n";
echo "Failed Count: " . $campaign->failed_count . "\n";

$statusBreakdown = EmailLog::where('campaign_id', $campaignId)
    ->select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "\nLog Status Breakdown:\n";
foreach ($statusBreakdown as $row) {
    echo "- " . $row->status . ": " . $row->count . "\n";
}

$pendingQuery = EmailLog::where('campaign_id', $campaignId)
    ->whereNotIn('status', ['sent', 'delivered', 'failed', 'bounced', 'complaint', 'spamreport', 'dropped']);

echo "\nRecipients eligible for Resume: " . $pendingQuery->count() . "\n";
echo "Sample pending IDs: " . implode(', ', $pendingQuery->take(5)->pluck('email_id')->toArray()) . "\n";
