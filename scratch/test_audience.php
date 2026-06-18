<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = App\Models\Campaign::first();
if (!$campaign) {
    $campaign = new App\Models\Campaign();
}

echo "Testing Audience Query Builder...\n";
$campaign->audience_config = [
    'list_ids' => [1],
    'include_segments' => [],
    'exclude_segments' => ['recent openers']
];

$query = $campaign->getAudienceQueryBuilder();
if ($query) {
    try {
        $count = $query->count();
        echo "Count: " . $count . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
