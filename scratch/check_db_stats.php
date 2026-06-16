<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EmailLog;

$totalLogs = EmailLog::count();
$latestLog = EmailLog::orderBy('id', 'desc')->first();
$latestTime = $latestLog ? $latestLog->created_at : 'none';

$statusBreakdown = EmailLog::select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "=== DB Stats overall ===\n";
echo "Total EmailLogs: $totalLogs\n";
echo "Latest log timestamp: $latestTime\n";
echo "Status breakdown:\n";
foreach ($statusBreakdown as $row) {
    echo "  - {$row->status}: {$row->count}\n";
}
