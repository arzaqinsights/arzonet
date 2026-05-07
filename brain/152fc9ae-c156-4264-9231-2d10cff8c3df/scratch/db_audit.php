<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EmailList;
use Illuminate\Support\Facades\DB;

// Use the list ID from the user's dashboard (usually the latest one in this context)
$list = EmailList::latest()->first();
if (!$list) {
    echo "No lists found.\n";
    exit;
}

$listId = $list->id;
echo "--- DB Audit for List #$listId: {$list->name} ---\n";

$total = DB::table('emails')->where('email_list_id', $listId)->count();
$active = DB::table('emails')->where('email_list_id', $listId)->where('is_archived', false)->count();
$valid = DB::table('emails')->where('email_list_id', $listId)->where('status', 'valid')->count();
$duplicate_marked = DB::table('emails')->where('email_list_id', $listId)->where('status', 'duplicate')->count();

echo "Total Records in DB: $total\n";
echo "Marked as 'duplicate': $duplicate_marked\n";
echo "Marked as 'valid': $valid\n";

// Now find actual duplicates using TRIM and LOWER
$actual_dupes = DB::table('emails')
    ->where('email_list_id', $listId)
    ->select(DB::raw('TRIM(LOWER(email)) as email_key'))
    ->groupBy(DB::raw('TRIM(LOWER(email))'))
    ->havingRaw('COUNT(*) > 1')
    ->get();

$redundant_count = 0;
foreach ($actual_dupes as $d) {
    $count = DB::table('emails')
        ->where('email_list_id', $listId)
        ->whereRaw('TRIM(LOWER(email)) = ?', [$d->email_key])
        ->count();
    $redundant_count += ($count - 1);
}

echo "--- Sample Discrepancy Check ---\n";
foreach ($actual_dupes->take(1) as $d) {
    echo "Processing: '{$d->email_key}'\n";
    $records = DB::table('emails')
        ->where('email_list_id', $listId)
        ->whereRaw('TRIM(LOWER(email)) = ?', [$d->email_key])
        ->get(['id', 'email', 'status']);
    
    foreach ($records as $r) {
        echo " - ID: {$r->id}, Status: {$r->status}, Raw Email: '{$r->email}' (Len: " . strlen($r->email) . ")\n";
    }
}
