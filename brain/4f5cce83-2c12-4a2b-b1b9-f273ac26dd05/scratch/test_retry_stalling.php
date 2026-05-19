<?php

// Bootstrap Laravel
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\EmailList;
use App\Models\Campaign;
use App\Models\Email;
use App\Models\EmailLog;
use App\Services\CampaignService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

try {
    echo "Starting Retry and Stalling Fix Test...\n";

    // 1. Get or create a test user
    $user = User::first() ?? User::create([
        'name' => 'Test User',
        'email' => 'test_' . time() . '@example.com',
        'password' => bcrypt('password'),
    ]);

    Auth::login($user);

    // 2. Create a test email list
    $list = EmailList::create([
        'user_id' => $user->id,
        'name' => 'Test Retry List ' . time(),
        'status' => 'completed',
        'list_type' => 'email',
    ]);

    // 3. Create test contacts
    $c1 = Email::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'email' => 'recipient1@example.com',
        'status' => 'valid',
    ]);
    $c2 = Email::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'email' => 'recipient2@example.com',
        'status' => 'valid',
    ]);
    $c3 = Email::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'email' => 'recipient3@example.com',
        'status' => 'valid',
    ]);

    // 4. Create campaign
    $campaign = Campaign::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'name' => 'Test Retry Campaign ' . time(),
        'status' => 'sending',
        'total_recipients' => 3,
        'sent_count' => 1,
        'failed_count' => 1,
    ]);

    $t = time();
    // 5. Create logs with different statuses
    $log1 = EmailLog::create([
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'email_id' => $c1->id,
        'email_address' => $c1->email,
        'status' => 'sent',
        'tracking_token' => 't1_' . $t,
    ]);

    $log2 = EmailLog::create([
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'email_id' => $c2->id,
        'email_address' => $c2->email,
        'status' => 'failed',
        'error_message' => 'Some SMTP error',
        'tracking_token' => 't2_' . $t,
    ]);

    $log3 = EmailLog::create([
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'email_id' => $c3->id,
        'email_address' => $c3->email,
        'status' => 'bounced',
        'tracking_token' => 't3_' . $t,
    ]);

    echo "Pre-Retry State:\n";
    echo "Campaign status: {$campaign->status}\n";
    echo "Campaign sent_count: {$campaign->sent_count}, failed_count: {$campaign->failed_count}\n";

    // 6. Run campaign service retry
    echo "Calling retryFailed()...\n";
    $service = app(CampaignService::class);
    $service->retryFailed($campaign);

    $campaign->refresh();
    echo "\nPost-Retry State:\n";
    echo "Campaign status: {$campaign->status}\n";
    echo "Campaign sent_count: {$campaign->sent_count} (Expected: 1)\n";
    echo "Campaign failed_count: {$campaign->failed_count} (Expected: 0 - because the failed log is reset to pending)\n";

    // Check logs status
    $log1->refresh();
    $log2->refresh();
    $log3->refresh();

    echo "Log 1 (sent) status: {$log1->status} (Expected: sent)\n";
    echo "Log 2 (failed) status: {$log2->status} (Expected: pending, error_message: NULL)\n";
    echo "Log 3 (bounced) status: {$log3->status} (Expected: bounced)\n";

    if ($log2->status === 'pending' && is_null($log2->error_message) && $log3->status === 'bounced') {
        echo "\nSUCCESS: retryFailed successfully targeted only failed and pending logs, resetting them to pending and leaving bounced logs intact!\n";
    } else {
        echo "\nFAILURE: Log statuses did not match expectations after retry.\n";
    }

    // Cleanup
    $log1->delete();
    $log2->delete();
    $log3->delete();
    $campaign->delete();
    $c1->delete();
    $c2->delete();
    $c3->delete();
    $list->delete();

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
