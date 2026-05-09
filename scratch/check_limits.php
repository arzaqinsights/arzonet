<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- PROVIDER LIMITS CHECK ---\n\n";

// 1. SendGrid Credits
$sgKey = env('SENDGRID_API_KEY');
if ($sgKey) {
    echo "[SendGrid]\n";
    $ch = curl_init('https://api.sendgrid.com/v3/user/credits');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $sgKey"
    ]);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['remain'])) {
        echo "Remaining Credits: " . $data['remain'] . "\n";
        echo "Total Limit: " . $data['total'] . "\n";
        echo "Overage: " . $data['overage'] . "\n";
        echo "Reset Frequency: " . $data['reset_frequency'] . "\n";
    } else {
        echo "Error fetching SendGrid credits: " . $response . "\n";
    }
}

echo "\n";

// 2. Amazon SES Quota
$sesKey = env('SES_ACCESS_KEY');
$sesSecret = env('SES_SECRET_KEY');
$sesRegion = env('SES_REGION', 'ap-south-1');

if ($sesKey && $sesSecret) {
    echo "[Amazon SES]\n";
    try {
        $sesClient = new \Aws\Ses\SesClient([
            'version' => 'latest',
            'region'  => $sesRegion,
            'credentials' => [
                'key'    => $sesKey,
                'secret' => $sesSecret,
            ],
        ]);

        $result = $sesClient->getSendQuota();
        echo "Sent in last 24h: " . $result['SentLast24Hours'] . "\n";
        echo "Max 24h Send: " . $result['Max24HourSend'] . "\n";
        echo "Max Send Rate: " . $result['MaxSendRate'] . " emails/sec\n";
    } catch (\Exception $e) {
        echo "Error fetching SES quota: " . $e->getMessage() . "\n";
    }
}

echo "\n--- END OF CHECK ---\n";
