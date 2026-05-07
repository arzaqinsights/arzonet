<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EmailList;
use App\Models\Email;
use Illuminate\Support\Facades\DB;

$list = EmailList::latest()->first();
$listId = $list->id;

echo "Force Scrubbing List #$listId...\n";

// 1. Get all unique emails in this list
$all_emails = DB::table('emails')
    ->where('email_list_id', $listId)
    ->select(DB::raw('DISTINCT TRIM(LOWER(email)) as email_key'))
    ->pluck('email_key');

echo "Found " . count($all_emails) . " unique email addresses.\n";

$total_marked = 0;

foreach ($all_emails as $email) {
    // Find all IDs for this email
    $ids = Email::where('email_list_id', $listId)
        ->whereRaw('TRIM(LOWER(email)) = ?', [$email])
        ->orderByRaw("FIELD(status, 'valid', 'invalid', 'duplicate') ASC")
        ->orderBy('id', 'ASC')
        ->pluck('id')
        ->toArray();

    if (count($ids) > 1) {
        $keepId = $ids[0];
        $dupeIds = array_slice($ids, 1);
        
        $count = Email::whereIn('id', $dupeIds)->update(['status' => 'duplicate']);
        $total_marked += $count;
        
        if ($email == 'amjain1978@gmail.com') {
            echo "Example 'amjain1978@gmail.com': Kept $keepId, Marked " . implode(',', $dupeIds) . " as duplicate.\n";
        }
    }
}

$list->recalculateStats();

echo "Done! Total records marked as duplicate: $total_marked\n";
echo "New Duplicate Count for List: " . $list->duplicate_count . "\n";
echo "New Valid Count for List: " . $list->valid_count . "\n";
