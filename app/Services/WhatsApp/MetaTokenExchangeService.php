<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Exception;

class MetaTokenExchangeService
{
    protected string $baseUrl;
    protected string $appId;
    protected string $appSecret;

    public function __construct()
    {
        $apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $this->baseUrl = "https://graph.facebook.com/{$apiVersion}";
        $this->appId = (string) config('services.whatsapp.app_id');
        $this->appSecret = (string) config('services.whatsapp.app_secret');
    }

    /**
     * Exchange short-lived code for long-lived access token.
     */
    public function exchangeCodeForToken(string $code): array
    {
        $params = [
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'code'          => $code,
        ];

        \Log::info('Attempting Meta token exchange', ['app_id' => $this->appId]);

        $response = Http::get("{$this->baseUrl}/oauth/access_token", $params);

        \Log::info('Meta token exchange response', [
            'status'  => $response->status(),
            'body'    => $response->body(),
        ]);

        if ($response->failed()) {
            throw new Exception("Meta Token Exchange Failed: " . $response->body());
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new Exception("Meta Token Exchange returned no access_token: " . $response->body());
        }

        return $data;
    }
}
