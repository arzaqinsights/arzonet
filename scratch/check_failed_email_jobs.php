<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = Illuminate\Support\Facades\DB::table('failed_jobs')
    ->where('payload', 'LIKE', '%SendEmail%')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();
foreach($jobs as $j) {
    echo $j->exception . "\n---\n";
}
