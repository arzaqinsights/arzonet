<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MetaApiService
{
    protected string $baseUrl;
    protected string $appId;
    protected string $appSecret;

    public function __construct()
    {
        $apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $this->baseUrl = "https://graph.facebook.com/{$apiVersion}";
        $this->appId = config('services.whatsapp.app_id');
        $this->appSecret = config('services.whatsapp.app_secret');
    }

    /**
     * Exchange the short-lived code for a long-lived user access token.
     */
    public function getLongLivedToken(string $code): string
    {
        $response = Http::get("{$this->baseUrl}/oauth/access_token", [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to fetch access token: " . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Fetch WhatsApp Business Account details.
     */
    public function getBusinessAccounts(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/me/whatsapp_business_accounts");

        return $response->json('data', []);
    }

    /**
     * Fetch phone numbers for a specific WABA.
     */
    public function getPhoneNumbers(string $wabaId, string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/{$wabaId}/phone_numbers");

        return $response->json('data', []);
    }

    /**
     * Send a template message.
     */
    public function sendTemplateMessage(string $phoneNumberId, string $accessToken, string $to, string $templateName, string $languageCode = 'en_US', array $components = []): array
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                    'components' => $components,
                ],
            ]);

        if ($response->failed()) {
            Log::error("WhatsApp send error: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch templates for a specific WABA.
     */
    public function getTemplates(string $wabaId, string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/{$wabaId}/message_templates");

        return $response->json('data', []);
    }

    /**
     * Register/Subscribe to webhooks for a specific WABA.
     */
    public function subscribeToWebhooks(string $wabaId, string $accessToken): bool
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/{$wabaId}/subscribed_apps");

        return $response->successful();
    }
}
