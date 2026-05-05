<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ContactActivity;
use App\Models\Unsubscribe;
use App\Models\EmailLog;

echo "--- Stats ---\n";
echo "Total Activities: " . ContactActivity::count() . "\n";
echo "Total Unsubscribes: " . Unsubscribe::count() . "\n";
echo "Total Email Logs: " . EmailLog::count() . "\n";

echo "\n--- Latest Activity ---\n";
print_r(ContactActivity::latest()->first()?->toArray());

echo "\n--- Latest Unsubscribe ---\n";
print_r(Unsubscribe::latest()->first()?->toArray());
