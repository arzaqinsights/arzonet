<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaignId = 95; // From the user's campaign URL /campaigns/95

$campaign = \App\Models\Campaign::find($campaignId);
if (!$campaign) {
    echo "Campaign $campaignId not found!\n";
    exit;
}

echo "=== Campaign 95 Details ===\n";
echo "Name: {$campaign->name}\n";
echo "Status: {$campaign->status}\n";
echo "Total Recipients: {$campaign->total_recipients}\n";
echo "Sent Count: {$campaign->sent_count}\n";
echo "Failed Count: {$campaign->failed_count}\n";

$logsCount = \DB::table('email_logs')->where('campaign_id', $campaignId)->count();
echo "\nTotal email_logs in DB: $logsCount\n";

$statusBreakdown = \DB::table('email_logs')
    ->where('campaign_id', $campaignId)
    ->select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "Status breakdown of email_logs:\n";
foreach ($statusBreakdown as $row) {
    echo "  - {$row->status}: {$row->count}\n";
}

$hasMessageId = \DB::table('email_logs')
    ->where('campaign_id', $campaignId)
    ->whereNotNull('message_id')
    ->count();
$noMessageId = \DB::table('email_logs')
    ->where('campaign_id', $campaignId)
    ->whereNull('message_id')
    ->count();

echo "\nMessage IDs:\n";
echo "  - With message_id: $hasMessageId\n";
echo "  - Without message_id: $noMessageId\n";

$eventBreakdown = \DB::table('email_events')
    ->join('email_logs', 'email_events.email_log_id', '=', 'email_logs.id')
    ->where('email_logs.campaign_id', $campaignId)
    ->select('email_events.type', \DB::raw('count(*) as count'))
    ->groupBy('email_events.type')
    ->get();

echo "\nEvents recorded in email_events:\n";
foreach ($eventBreakdown as $row) {
    echo "  - {$row->type}: {$row->count}\n";
}

$unsubCount = \DB::table('unsubscribes')->where('campaign_id', $campaignId)->count();
echo "\nUnsubscribes for this campaign: $unsubCount\n";

// Show latest 3 errors/messages
$errors = \DB::table('email_logs')
    ->where('campaign_id', $campaignId)
    ->whereNotNull('error_message')
    ->select('email_address', 'status', 'error_message')
    ->take(3)
    ->get();

if ($errors->isNotEmpty()) {
    echo "\nSample Error Messages:\n";
    foreach ($errors as $err) {
        echo "  - Email: {$err->email_address} | Status: {$err->status} | Error: {$err->error_message}\n";
    }
}
