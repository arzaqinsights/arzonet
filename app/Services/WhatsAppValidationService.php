<?php

namespace App\Services;

class WhatsAppValidationService
{
    /**
     * Validate and format a phone number for WhatsApp.
     */
    public function validate(?string $number): array
    {
        if (empty($number)) {
            return [
                'is_valid' => false,
                'formatted' => null,
                'original' => $number,
                'reason' => 'Empty number'
            ];
        }

        // 1. Basic Cleaning: Keep only digits
        $clean = preg_replace('/[^0-9]/', '', $number);
        
        // 2. Handle Indian Numbers (Default logic)
        // If 10 digits, assume India and add 91
        if (strlen($clean) === 10) {
            $clean = '91' . $clean;
        } 
        // If 11 digits starting with 0, assume India and replace 0 with 91
        elseif (strlen($clean) === 11 && str_starts_with($clean, '0')) {
            $clean = '91' . substr($clean, 1);
        }

        // 3. General International Length Validation (11-15 digits)
        $length = strlen($clean);
        $isValid = ($length >= 11 && $length <= 15);

        // 4. Additional Syntax Checks (Optional: check if it starts with valid country code blocks)
        // For now, we trust the 11-15 range as standard Meta requirement.

        return [
            'is_valid' => $isValid,
            'formatted' => $isValid ? $clean : null,
            'original' => $number,
            'reason' => $isValid ? null : ($length < 11 ? 'Too short (Missing country code?)' : 'Invalid length/syntax')
        ];
    }

    /**
     * Batch validate phone numbers.
     */
    public function validateBatch(array $numbers): array
    {
        $results = [];
        foreach ($numbers as $number) {
            $results[$number] = $this->validate($number);
        }
        return $results;
    }
}
