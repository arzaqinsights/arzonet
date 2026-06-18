<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = App\Models\Campaign::find(100);
$query = $campaign->getAudienceQueryBuilder();
if ($query) {
    $validEmailIds = $query->pluck('emails.id')->toArray();

    // Delete all logs for this campaign where email_id is NOT in $validEmailIds
    $deletedCount = App\Models\EmailLog::where('campaign_id', $campaign->id)
        ->whereNotIn('email_id', $validEmailIds)
        ->delete();

    echo "Deleted $deletedCount invalid logs for campaign 100\n";
    
    // Also remove any pending jobs from the queue?
    // The user's horizon had 0 pending jobs so they probably already expired or failed.
} else {
    echo "No valid audience query builder.\n";
}
