<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get any user
$user = \App\Models\User::first();
if (!$user) {
    echo "No user found!\n";
    exit;
}

$campaign = \App\Models\Campaign::first();
$email = \App\Models\Email::first();

if (!$campaign || !$email) {
    echo "Need at least one campaign and one email in DB to test!\n";
    exit;
}

$rawCount = $user->logs()->where('status', '!=', 'pending')->count();
$scopeCount = $user->logs()->countedTowardsUsage()->count();

echo "User ID: {$user->id}\n";
echo "Raw count (not pending): {$rawCount}\n";
echo "Scope count (counted towards usage): {$scopeCount}\n";

// Let's create a temporary log with "Skipped:" in error message and make sure it is not counted
$tempLog1 = \App\Models\EmailLog::create([
    'user_id' => $user->id,
    'campaign_id' => $campaign->id,
    'email_id' => $email->id,
    'email_address' => 'temp1@example.com',
    'status' => 'failed',
    'error_message' => 'Skipped: Campaign suspended due to high bounce rate.',
]);

$tempLog2 = \App\Models\EmailLog::create([
    'user_id' => $user->id,
    'campaign_id' => $campaign->id,
    'email_id' => $email->id,
    'email_address' => 'temp2@example.com',
    'status' => 'failed',
    'error_message' => 'Campaign cancelled',
]);

$tempLog3 = \App\Models\EmailLog::create([
    'user_id' => $user->id,
    'campaign_id' => $campaign->id,
    'email_id' => $email->id,
    'email_address' => 'temp3@example.com',
    'status' => 'failed',
    'error_message' => 'SMTP timeout error',
]);

$tempLog4 = \App\Models\EmailLog::create([
    'user_id' => $user->id,
    'campaign_id' => $campaign->id,
    'email_id' => $email->id,
    'email_address' => 'temp4@example.com',
    'status' => 'pending',
]);

$newRawCount = $user->logs()->where('status', '!=', 'pending')->count();
$newScopeCount = $user->logs()->countedTowardsUsage()->count();

echo "After inserting temp logs:\n";
echo "Raw count (not pending): {$newRawCount} (should be old_raw + 3)\n";
echo "Scope count (counted towards usage): {$newScopeCount} (should be old_scope + 1)\n";

// Clean up
$tempLog1->delete();
$tempLog2->delete();
$tempLog3->delete();
$tempLog4->delete();
