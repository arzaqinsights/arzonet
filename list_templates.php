<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$templates = App\Models\Template::all();
foreach($templates as $t) {
    echo "ID: {$t->id} | Name: {$t->name} | HTML Length: " . strlen($t->html_content) . "\n";
}
