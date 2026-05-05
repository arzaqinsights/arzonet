<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$jobs = DB::table('failed_jobs')->get();
foreach ($jobs as $job) {
    $payload = json_decode($job->payload);
    $command = unserialize($payload->data->command);
    echo "Job ID: " . $job->id . "\n";
    if (isset($command->emailIds)) {
        print_r($command->emailIds);
    } else {
        echo "No emailIds in command: " . get_class($command) . "\n";
    }
}
