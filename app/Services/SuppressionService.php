<?php

namespace App\Services;

use App\Models\EmailStatus;

class SuppressionService
{
    /**
     * Filter out bounced or complaint emails from a list.
     * 
     * @param array $emails Array of email addresses
     * @return array Only valid email addresses
     */
    public static function filterValidEmails(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        // Find all suppressed emails in the provided list
        $suppressed = EmailStatus::whereIn('email', $emails)
            ->whereIn('status', ['bounced', 'complaint'])
            ->pluck('email')
            ->toArray();

        // Return only those not in the suppressed list
        return array_diff($emails, $suppressed);
    }
}
