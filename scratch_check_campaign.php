<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 17;
$c = Campaign::find($id);

if (!$c) {
    echo "Campaign $id not found\n";
    exit;
}

echo "Campaign: " . $c->name . " (ID: $id)\n";
echo "Total Recipients (Model): " . $c->total_recipients . "\n";
echo "Logs Count in DB: " . $c->logs()->count() . "\n";
echo "Statuses Breakdown:\n";
$stats = $c->logs()->select('status', DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status')->toArray();
print_r($stats);
