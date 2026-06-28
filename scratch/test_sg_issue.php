<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sender = \App\Models\Sender::where('type', 'sendgrid')->whereNotNull('sendgrid_api_key')->first();
$apiKey = $sender ? $sender->sendgrid_api_key : config('services.sendgrid.key');

echo "API KEY prefix: " . substr($apiKey, 0, 10) . "\n";

$res = \Illuminate\Support\Facades\Http::withToken($apiKey)->get('https://api.sendgrid.com/v3/suppression/bounces');
$bounces = $res->json();
echo "Bounces count: " . (is_array($bounces) ? count($bounces) : 0) . "\n";

$res = \Illuminate\Support\Facades\Http::withToken($apiKey)->get('https://api.sendgrid.com/v3/suppression/blocks');
$blocks = $res->json();
echo "Blocks count: " . (is_array($blocks) ? count($blocks) : 0) . "\n";

$res = \Illuminate\Support\Facades\Http::withToken($apiKey)->get('https://api.sendgrid.com/v3/mail_settings');
echo "Mail Settings: \n" . json_encode($res->json(), JSON_PRETTY_PRINT) . "\n";

$res = \Illuminate\Support\Facades\Http::withToken($apiKey)->get('https://api.sendgrid.com/v3/messages?limit=5');
echo "Recent Messages: \n" . json_encode($res->json(), JSON_PRETTY_PRINT) . "\n";
