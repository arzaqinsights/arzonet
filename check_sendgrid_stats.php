<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sender = \App\Models\Sender::where('type', 'sendgrid')->whereNotNull('sendgrid_api_key')->first();
$apiKey = $sender ? $sender->sendgrid_api_key : config('services.sendgrid.key');

if (!$apiKey) {
    echo "No SendGrid API Key found!\n";
    exit;
}

$today = date('Y-m-d');
$response = \Illuminate\Support\Facades\Http::withToken($apiKey)
    ->get('https://api.sendgrid.com/v3/stats', [
        'start_date' => $today,
        'end_date' => $today
    ]);

if ($response->successful()) {
    $data = $response->json();
    if (!empty($data)) {
        $stats = $data[0]['stats'][0]['metrics'];
        echo "=== SendGrid Stats for Today ($today) ===\n";
        echo "Requests (Total Sent Attempts): " . ($stats['requests'] ?? 0) . "\n";
        echo "Delivered: " . ($stats['delivered'] ?? 0) . "\n";
        echo "Opens: " . ($stats['opens'] ?? 0) . "\n";
        echo "Unique Opens: " . ($stats['unique_opens'] ?? 0) . "\n";
        echo "Clicks: " . ($stats['clicks'] ?? 0) . "\n";
        echo "Unique Clicks: " . ($stats['unique_clicks'] ?? 0) . "\n";
        echo "Bounces: " . ($stats['bounces'] ?? 0) . "\n";
        echo "Blocks: " . ($stats['blocks'] ?? 0) . "\n";
        echo "Spam Reports: " . ($stats['spam_reports'] ?? 0) . "\n";
        echo "Drops: " . ($stats['drops'] ?? 0) . "\n";
        echo "Unsubscribes: " . ($stats['unsubscribes'] ?? 0) . "\n";
    } else {
        echo "No stats data returned from SendGrid for today.\n";
    }
} else {
    echo "Error fetching stats: " . $response->body() . "\n";
}
