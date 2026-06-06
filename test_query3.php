<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Campaign::latest()->first();
echo "Audience config in DB:\n";
echo json_encode($c->audience_config, JSON_PRETTY_PRINT);
