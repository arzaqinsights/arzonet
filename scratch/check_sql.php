<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$c = \App\Models\Campaign::latest()->first();
$list = $c->emailList;

$unsubscribedEmails = \App\Models\Unsubscribe::pluck('email')->toArray();
$suppressedEmails = \App\Models\EmailStatus::whereIn('status', ['bounced', 'complaint'])->pluck('email')->toArray();
$allExclusions = array_merge($unsubscribedEmails, $suppressedEmails);

$query = $list->emails()
    ->valid()
    ->subscribed()
    ->whereNotIn('email', $allExclusions);

if ($c->audience_config) {
    $config = $c->audience_config;
    if (isset($config['exclude_unhealthy']) && $config['exclude_unhealthy']) {
        $query->where(function($q) {
            $q->whereNotIn('email_status', ['hard_bounce', 'complaint', 'invalid', 'blocked'])
              ->orWhereNull('email_status');
        });
        $query->where('email_score', '>', 1);
    }

}

echo "SQL: " . $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";
echo "Count: " . $query->count() . "\n";
