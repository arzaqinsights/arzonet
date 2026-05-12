<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Exception;

class MetaWebhookSubscriptionService
{
    protected string $baseUrl;

    public function __construct()
    {
        $apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $this->baseUrl = "https://graph.facebook.com/{$apiVersion}";
    }

    /**
     * Automatically subscribe the app to the WABA events.
     */
    public function subscribeWabaToApp(string $wabaId, string $accessToken): bool
    {
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/{$wabaId}/subscribed_apps");

        if ($response->failed()) {
            throw new Exception("Failed to subscribe WABA to Webhooks: " . $response->body());
        }

        return $response->successful();
    }
}
