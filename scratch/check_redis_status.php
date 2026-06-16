<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;

$bufferLength = Redis::llen('webhook:sendgrid:buffer');
echo "Raw Webhook Buffer (webhook:sendgrid:buffer) length: $bufferLength\n";

$queues = ['queues:default', 'queues:high', 'queues:low'];
foreach ($queues as $queue) {
    $len = Redis::llen($queue);
    echo "Queue '$queue' length: $len\n";
}

$failedCount = \DB::table('failed_jobs')->count();
echo "Failed jobs count: $failedCount\n";
