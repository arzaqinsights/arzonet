<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Campaign;

$campaigns = Campaign::select('id','name','status')->latest()->take(10)->get();
foreach ($campaigns as $c) {
    echo "ID: {$c->id} | Status: {$c->status} | Name: {$c->name}\n";
}
