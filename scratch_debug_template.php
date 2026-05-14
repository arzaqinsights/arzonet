<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\Template::find(1);
if ($template) {
    echo "ID: " . $template->id . "\n";
    echo "Name: " . $template->name . "\n";
    echo "JSON Design Length: " . strlen($template->json_design) . "\n";
    echo "JSON Design Snippet: " . substr($template->json_design, 0, 500) . "\n";
} else {
    echo "Template not found.\n";
}
