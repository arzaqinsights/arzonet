<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check template ID 4 json_design structure
$template = \App\Models\Template::find(4);
if ($template) {
    echo "ID: " . $template->id . "\n";
    echo "Name: " . $template->name . "\n";
    $jsonLen = strlen($template->json_design ?? '');
    echo "JSON Design Length: " . $jsonLen . "\n";
    
    if ($jsonLen > 0) {
        $decoded = json_decode($template->json_design, true);
        echo "JSON decode error: " . json_last_error_msg() . "\n";
        echo "JSON is double encoded? " . (is_string($decoded) ? "YES - double encoded!" : "NO - it's a proper object") . "\n";
        
        if (is_string($decoded)) {
            echo "Double encoded value snippet: " . substr($decoded, 0, 200) . "\n";
            // Try double decode
            $decoded2 = json_decode($decoded, true);
            echo "After double decode, top keys: " . implode(', ', array_keys($decoded2 ?? [])) . "\n";
        } else {
            echo "Top-level keys: " . implode(', ', array_keys($decoded ?? [])) . "\n";
        }
    }
} else {
    // List all templates
    $templates = \App\Models\Template::all(['id', 'name']);
    echo "Available templates:\n";
    foreach ($templates as $t) {
        echo "  ID: " . $t->id . " | Name: " . $t->name . "\n";
    }
}
