<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Campaign::latest()->first();

$c->update(['audience_config' => [
    'list_ids' => [\App\Models\EmailList::first()->id],
    'exclude_unhealthy' => true,
    'exclude_risky' => true,
    'exclude_disposable' => true,
    'exclude_role_based' => true,
]]);

try {
    $query = $c->getAudienceQueryBuilder();
    echo "Count with just list_ids: " . $query->count() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
