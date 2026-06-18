<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$campaign = new App\Models\Campaign([
    'email_list_id' => 1,
    'audience_config' => [
        'include_tags' => ['Tag A'],
        'exclude_tags' => ['Tag B'],
        'include_segments' => ['Segment X'],
        'exclude_segments' => ['Segment Y']
    ]
]);

$query = $campaign->getAudienceQueryBuilder();
echo "SQL Query:\n";
echo $query->toSql() . "\n";
echo "Bindings:\n";
print_r($query->getBindings());

