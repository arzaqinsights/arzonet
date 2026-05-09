<?php

namespace App\Services;

class PersonalizationService
{
    /**
     * Replace template variables with actual recipient data.
     */
    public function personalize(string $content, array $recipientData, bool $escapeHtml = true): string
    {
        $defaults = config('emailplatform.defaults', []);

        // Build variables map with all available data
        $variables = array_merge($defaults, $recipientData);
        
        // Ensure standard aliases
        $variables['name'] = $recipientData['name'] ?? $recipientData['full_name'] ?? $variables['name'] ?? 'Recipient';
        $variables['full_name'] = $variables['name'];
        $variables['email'] = $recipientData['email'] ?? $variables['email'] ?? '';

        // Flatten meta data if present
        if (!empty($recipientData['meta']) && is_array($recipientData['meta'])) {
            foreach ($recipientData['meta'] as $key => $value) {
                if (is_scalar($value)) $variables[$key] = $value;
            }
        }

        // Logic for first/last name if only 'name' exists
        if (!isset($variables['first_name']) && !empty($variables['full_name'])) {
            $parts = explode(' ', $variables['full_name']);
            $variables['first_name'] = $parts[0] ?? '';
            $variables['last_name'] = count($parts) > 1 ? end($parts) : '';
        }

        // Add Unsubscribe Link if provided
        if (isset($recipientData['unsubscribe_url'])) {
            $variables['unsubscribe_url'] = $recipientData['unsubscribe_url'];
        }

        // Replace all variables using Regex for spacing flexibility
        $result = $content;
        foreach ($variables as $key => $value) {
            // Only replace if the value is a string or number (scalar)
            if (!is_scalar($value) && !is_null($value)) continue;

            $val = $value ?? '';
            if ($escapeHtml && is_string($val)) {
                $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
            }
            
            // Matches {{key}}, {{ key }}, {{  key  }} etc. (case-insensitive)
            $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/i';
            $result = preg_replace($pattern, $val, $result);
        }

        // Fallback for old style @{{name}} if any
        $result = str_ireplace('@{{name}}', $variables['full_name'], $result);

        return $result;
    }

    /**
     * Extract all variable names from a template.
     */
    public function extractVariables(string $html): array
    {
        preg_match_all('/\{\{([a-zA-Z0-9_]+)\}\}/', $html, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Generate a preview with sample data.
     */
    public function preview(string $html, ?array $sampleData = null): string
    {
        $sample = $sampleData ?? [
            'name'  => 'John Alexander Doe',
            'email' => 'john.doe@example-crm.com',
            'meta'  => [
                'company'   => 'Antigravity AI Solutions',
                'job_title' => 'Director of Engineering',
                'city'      => 'San Francisco',
            ],
            'unsubscribe_url' => '#',
        ];

        return $this->personalize($html, $sample);
    }
}
