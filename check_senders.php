<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sender;

$types = Sender::distinct()->pluck('type');
echo "Sender Types in DB: " . implode(', ', $types->toArray()) . "\n";

foreach (Sender::all() as $s) {
    echo "ID: {$s->id} | Type: {$s->type} | Email: {$s->email}\n";
}
