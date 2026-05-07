<?php

use App\Services\SuppressionService;

// Example list of emails you want to send a campaign to
$emails = [
    'valid@example.com',
    'bounced-user@domain.com',
    'complainer@gmail.com',
    'new-lead@company.com'
];

// Before sending, filter out suppressed emails
$validEmails = SuppressionService::filterValidEmails($emails);

/*
$validEmails will contain:
[
    'valid@example.com',
    'new-lead@company.com'
]
(Assuming bounced-user and complainer are in the email_statuses table)
*/

foreach ($validEmails as $email) {
    // Logic to send email via SES
}
