<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$type = \Illuminate\Support\Facades\DB::select("DESCRIBE email_logs");
print_r($type);
