<?php
$sgKey = config('services.sendgrid.key'); // WARNING: Do not hardcode API keys here!

function callSg($endpoint) {
    global $sgKey;
    $ch = curl_init("https://api.sendgrid.com/v3/" . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $sgKey",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

echo "--- SENDGRID ACCOUNT INFO ---\n";
print_r(callSg("user/account"));

echo "\n--- SENDGRID CREDITS ---\n";
print_r(callSg("user/credits"));

echo "\n--- SENDGRID PROFILE ---\n";
print_r(callSg("user/profile"));

echo "\n--- SENDGRID USAGE (Monthly) ---\n";
print_r(callSg("stats?start_date=2026-05-01&aggregated_by=month"));
