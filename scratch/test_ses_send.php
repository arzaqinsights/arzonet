<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

$sesKey = env('SES_ACCESS_KEY');
$sesSecret = env('SES_SECRET_KEY');
$sesRegion = env('SES_REGION', 'ap-south-1');
$fromEmail = env('SES_FROM_EMAIL', 'hello@arzaqinsights.com');
$recipientEmail = 'monisrazakhan2001@gmail.com';

echo "--- SES TEST SEND ---\n";
echo "From: $fromEmail\n";
echo "To: $recipientEmail\n\n";

$client = new SesClient([
    'version' => 'latest',
    'region'  => $sesRegion,
    'credentials' => [
        'key'    => $sesKey,
        'secret' => $sesSecret,
    ],
]);

try {
    $result = $client->sendEmail([
        'Destination' => [
            'ToAddresses' => [$recipientEmail],
        ],
        'Message' => [
            'Body' => [
                'Html' => [
                    'Charset' => 'UTF-8',
                    'Data' => '<h1>SES Test Successful!</h1><p>This is a test email sent via Amazon SES API from Arzonet.</p>',
                ],
                'Text' => [
                    'Charset' => 'UTF-8',
                    'Data' => 'SES Test Successful! This is a test email sent via Amazon SES API from Arzonet.',
                ],
            ],
            'Subject' => [
                'Charset' => 'UTF-8',
                'Data' => 'Arzonet SES Test Mail',
            ],
        ],
        'Source' => $fromEmail,
    ]);
    echo "Message sent! Message ID: " . $result['MessageId'] . "\n";
} catch (AwsException $e) {
    echo "Error sending email: " . $e->getAwsErrorMessage() . "\n";
    if (str_contains($e->getAwsErrorMessage(), 'Email address is not verified')) {
        echo "\nNOTE: Since you are in SES Sandbox mode, the recipient email MUST be verified in your AWS SES console before you can send to it.\n";
    }
}
echo "\n--- END ---\n";
