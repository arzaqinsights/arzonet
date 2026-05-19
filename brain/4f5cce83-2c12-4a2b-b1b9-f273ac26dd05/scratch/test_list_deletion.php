<?php

// Bootstrap Laravel
require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\EmailList;
use App\Models\Campaign;

try {
    echo "Starting Campaign Preservation Test...\n";

    // 1. Get or create a test user
    $user = User::first() ?? User::create([
        'name' => 'Test User',
        'email' => 'test_' . time() . '@example.com',
        'password' => bcrypt('password'),
    ]);

    // 2. Create a test email list
    $list = EmailList::create([
        'user_id' => $user->id,
        'name' => 'Test List ' . time(),
        'status' => 'completed',
        'list_type' => 'email',
    ]);
    echo "Created Email List ID: {$list->id}\n";

    // 3. Create a campaign linked to this list
    $campaign = Campaign::create([
        'user_id' => $user->id,
        'email_list_id' => $list->id,
        'name' => 'Test Campaign ' . time(),
        'status' => 'draft',
    ]);
    echo "Created Campaign ID: {$campaign->id} with email_list_id: {$campaign->email_list_id}\n";

    // 4. Delete the email list
    echo "Deleting Email List...\n";
    $list->delete();

    // 5. Assert campaign still exists and its email_list_id is null
    $campaign->refresh();
    echo "After list deletion, Campaign still exists: " . ($campaign ? 'YES' : 'NO') . "\n";
    echo "Campaign email_list_id: " . ($campaign->email_list_id === null ? 'NULL (Correct)' : $campaign->email_list_id) . "\n";

    if ($campaign->email_list_id === null) {
        echo "SUCCESS: Campaign successfully preserved and list reference set to NULL!\n";
    } else {
        echo "FAILURE: Campaign email_list_id was not set to NULL!\n";
    }

    // Cleanup
    $campaign->delete();

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
