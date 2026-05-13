<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Exception;

class MetaAccountSyncService
{
    protected string $baseUrl;

    public function __construct()
    {
        $apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $this->baseUrl = "https://graph.facebook.com/{$apiVersion}";
    }

    /**
     * Fetch WhatsApp Business Accounts.
     */
    public function getBusinessAccounts(string $accessToken): array
    {
        // Using explicit fields query which is more robust in newer API versions
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/me", [
                'fields' => 'whatsapp_business_accounts'
            ]);

        if ($response->failed()) {
            throw new Exception("Failed to fetch WhatsApp Business Accounts: " . $response->body());
        }

        $data = $response->json('whatsapp_business_accounts.data', []);
        
        if (empty($data)) {
             \Log::warning('No WABAs found in Meta response', ['response' => $response->json()]);
        }

        return $data;
    }

    /**
     * Fetch details for a specific WABA.
     */
    public function getWabaDetails(string $wabaId, string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/{$wabaId}");

        if ($response->failed()) {
            throw new Exception("Failed to fetch WABA details: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch phone numbers for a specific WABA.
     */
    public function getPhoneNumbers(string $wabaId, string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/{$wabaId}/phone_numbers");

        if ($response->failed()) {
            throw new Exception("Failed to fetch phone numbers: " . $response->body());
        }

        return $response->json('data', []);
    }
}
