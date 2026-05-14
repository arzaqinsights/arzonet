<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$senders = \App\Models\Sender::all(['id', 'email', 'emails_per_minute']);
foreach($senders as $s) {
    echo $s->id . ' - ' . $s->email . ' : ' . $s->emails_per_minute . PHP_EOL;
}
