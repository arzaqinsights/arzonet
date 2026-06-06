<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Campaign::latest()->first();

$payload = [
    'email_list_id' => 47,
    'audience_config' => [
        'list_ids' => [47, 48],
        'include_tags' => [],
        'include_segments' => [],
        'exclude_tags' => [],
        'exclude_segments' => [],
        'exclude_unhealthy' => true,
        'exclude_risky' => false,
        'exclude_disposable' => false,
        'exclude_role_based' => false,
        'limit' => null
    ]
];

// Replicate controller behavior
$request = new \Illuminate\Http\Request([], $payload);
$data = $request->only(['name', 'subject', 'email_list_id', 'template_id', 'sender_id', 'scheduled_at', 'audience_config']);
$data = array_filter($data, fn($value) => !is_null($value));

$c->update($data);

// Simulate DB retrieval (as if it was next request)
$c = \App\Models\Campaign::find($c->id);

try {
    $count = $c->getEstimatedRecipientCount();
    echo "Count: " . $count . "\n";
    echo "Query: " . $c->getAudienceQueryBuilder()->toSql() . "\n";
    echo "Bindings: " . json_encode($c->getAudienceQueryBuilder()->getBindings()) . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
