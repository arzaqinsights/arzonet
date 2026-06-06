<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Campaign::latest()->first();

// Simulate payload
$payload = [
    'audience_config' => [
        'list_ids' => [47],
        'include_tags' => [],
        'include_segments' => [],
        'exclude_tags' => [],
        'exclude_segments' => [],
        'exclude_unhealthy' => true,
        'exclude_risky' => false,
        'exclude_disposable' => false,
        'exclude_role_based' => false,
    ]
];

$c->update($payload);

try {
    echo "Count: " . $c->getEstimatedRecipientCount() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
