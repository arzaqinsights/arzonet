<?php

use Illuminate\Support\Facades\DB;

$failedJobs = DB::table('failed_jobs')->latest('failed_at')->take(5)->get();

if ($failedJobs->isEmpty()) {
    echo "No failed jobs found in database.\n";
    exit;
}

foreach ($failedJobs as $job) {
    echo "--- FAILED JOB ID: {$job->id} ---\n";
    echo "Queue: {$job->queue}\n";
    echo "Failed At: {$job->failed_at}\n";
    
    // Extract exception message
    $exception = $job->exception;
    $message = explode("\n", $exception)[0]; // Get the first line of the error
    
    echo "Error: {$message}\n\n";
}
