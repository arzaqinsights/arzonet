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
use App\Services\CampaignService;
use Illuminate\Support\Facades\Auth;

try {
    echo "Starting Null Email Dispatch Test...\n";

    // 1. Get or create a test user
    $user = User::first() ?? User::create([
        'name' => 'Test User',
        'email' => 'test_' . time() . '@example.com',
        'password' => bcrypt('password'),
    ]);

    // Authenticate session
    Auth::login($user);
    echo "Logged in as user ID: {$user->id}\n";

    // 2. Create a test email list
    $list = EmailList::create([
        'user_id' => $user->id,
        'name' => 'Test Null Email List ' . time(),
        'status' => 'completed',
        'list_type' => 'email',
    ]);
    echo "Created Email List ID: {$list->id}\n";

    // 3. Create valid and null email contacts
    $contact1 = Email::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'email' => 'valid_recipient@example.com',
        'name' => 'Valid Recipient',
        'status' => 'valid',
    ]);

    $contact2 = Email::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'email' => null, // NULL email address (e.g. whatsapp list contact)
        'name' => 'Null Email Recipient',
        'status' => 'valid',
    ]);

    $contact3 = Email::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'email' => '', // Empty string email address
        'name' => 'Empty Email Recipient',
        'status' => 'valid',
    ]);

    echo "Created 3 test contacts (1 valid, 1 null email, 1 empty email)\n";

    // 4. Create campaign linked to this list
    $campaign = Campaign::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'name' => 'Test Null Email Campaign ' . time(),
        'status' => 'draft',
    ]);
    echo "Created Campaign ID: {$campaign->id}\n";

    // 5. Try to dispatch using CampaignService
    echo "Dispatching campaign...\n";
    $service = app(CampaignService::class);
    $service->dispatch($campaign);

    echo "Campaign dispatched successfully without crashing!\n";

    // 6. Assertions
    $campaign->refresh();
    echo "Campaign status: {$campaign->status}\n";
    echo "Campaign total recipients: {$campaign->total_recipients} (Expected: 1)\n";

    $logsCount = $campaign->logs()->count();
    echo "Number of campaign logs created: {$logsCount} (Expected: 1)\n";

    if ($campaign->total_recipients === 1 && $logsCount === 1) {
        echo "SUCCESS: Null and empty email addresses were successfully ignored, preventing database constraint crashes!\n";
    } else {
        echo "FAILURE: Recipient matching counts did not match expectations.\n";
    }

    // Cleanup
    $campaign->logs()->delete();
    $campaign->delete();
    $contact1->delete();
    $contact2->delete();
    $contact3->delete();
    $list->delete();

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
