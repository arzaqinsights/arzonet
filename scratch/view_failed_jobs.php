<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failed = \DB::table('failed_jobs')->orderBy('id', 'desc')->take(10)->get();
foreach ($failed as $job) {
    $payload = json_decode($job->payload, true);
    $commandName = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'Unknown');
    echo "ID: {$job->id} | Queue: {$job->queue} | Class: {$commandName} | Failed At: {$job->failed_at}\n";
    echo "Exception: " . substr($job->exception, 0, 300) . "\n";
    echo "--------------------------------------------------\n";
}
