<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Starting Database Stats Healing ===\n";

// 1. Heal logs with error messages but 'sent' status
$logsWithErrors = DB::table('email_logs')
    ->where('status', 'sent')
    ->whereNotNull('error_message')
    ->get();

echo "Found " . $logsWithErrors->count() . " logs with status 'sent' but having an error message.\n";

$healedBounces = 0;
$healedBlocks = 0;
$healedDrops = 0;
$healedDeferred = 0;

foreach ($logsWithErrors as $log) {
    $err = strtolower($log->error_message);
    $newStatus = null;

    if (str_contains($err, 'dropped')) {
        $newStatus = 'dropped';
        $healedDrops++;
    } elseif (str_contains($err, 'block') || str_contains($err, 'suppress')) {
        $newStatus = 'blocked';
        $healedBlocks++;
    } elseif (str_contains($err, 'defer')) {
        $newStatus = 'deferred';
        $healedDeferred++;
    } else {
        // Default to bounced for delivery failure messages
        $newStatus = 'bounced';
        $healedBounces++;
    }

    if ($newStatus) {
        DB::table('email_logs')
            ->where('id', $log->id)
            ->update([
                'status' => $newStatus,
                'updated_at' => now()
            ]);
    }
}

echo "Healed from error messages:\n";
echo "  - Bounces: $healedBounces\n";
echo "  - Blocks: $healedBlocks\n";
echo "  - Drops: $healedDrops\n";
echo "  - Deferred: $healedDeferred\n";

// 2. Heal logs with 'sent' status but having open/click events
$logsWithEvents = DB::table('email_logs')
    ->join('email_events', 'email_logs.id', '=', 'email_events.email_log_id')
    ->whereIn('email_logs.status', ['sent', 'processed', 'pending'])
    ->select('email_logs.id')
    ->distinct()
    ->pluck('id');

echo "\nFound " . $logsWithEvents->count() . " logs in sent/processed/pending status but having open/click events.\n";

$healedDelivered = 0;
if ($logsWithEvents->isNotEmpty()) {
    $healedDelivered = DB::table('email_logs')
        ->whereIn('id', $logsWithEvents)
        ->update([
            'status' => 'delivered',
            'delivered_at' => DB::raw('COALESCE(delivered_at, updated_at, NOW())'),
            'updated_at' => now()
        ]);
}

echo "Healed to 'delivered' status: $healedDelivered\n";
echo "=== Database Stats Healing Completed ===\n";
