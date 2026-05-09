<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendGridDomainService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.sendgrid.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.sendgrid.key');
    }

    /**
     * Create a new domain authentication request.
     */
    public function authenticateDomain(string $domain)
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/whitelabel/domains", [
                'domain' => $domain,
                'automatic_security' => true,
                'custom_spf' => true,
                'default' => false
            ]);

        if (!$response->successful()) {
            Log::error("SendGrid Domain Auth Error: " . $response->body());
            throw new \Exception("Failed to initiate domain authentication: " . $response->json()['errors'][0]['message']);
        }

        return $response->json();
    }

    /**
     * Validate a domain authentication.
     */
    public function validateDomain(int $domainId)
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/whitelabel/domains/{$domainId}/validate");

        if (!$response->successful()) {
            Log::error("SendGrid Domain Validation Error: " . $response->body());
            throw new \Exception("Validation failed: " . $response->json()['errors'][0]['message']);
        }

        return $response->json();
    }

    /**
     * Get details of a specific domain authentication.
     */
    public function getDomainDetails(int $domainId)
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/whitelabel/domains/{$domainId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch domain details.");
        }

        return $response->json();
    }
}
