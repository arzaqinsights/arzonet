<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CashfreeService
{
    protected $baseUrl;
    protected $appId;
    protected $secretKey;

    public function __construct()
    {
        $this->appId = config('cashfree.app_id');
        $this->secretKey = config('cashfree.secret_key');
        
        $mode = config('cashfree.mode');
        
        $this->baseUrl = ($mode === 'production') 
            ? 'https://api.cashfree.com/pg/orders' 
            : 'https://sandbox.cashfree.com/pg/orders';
    }

    public function createOrder($orderId, $amount, $customerDetails, $metaData = [])
    {
        // Build URLs on the admin subdomain so they resolve correctly in production
        $adminBase = 'https://admin.' . config('app.domain');
        $returnUrl = $adminBase . '/billing/payment-return?order_id={order_id}';
        $notifyUrl = $adminBase . '/webhooks/cashfree';

        $response = Http::withHeaders([
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version' => '2023-08-01',
        ])->post($this->baseUrl, [
            'order_id' => $orderId,
            'order_amount' => (float) $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => (string) $customerDetails['id'],
                'customer_email' => $customerDetails['email'],
                'customer_phone' => $customerDetails['phone'] ?? '9999999999',
            ],
            'order_meta' => [
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
            ],
            'order_note' => 'Plan Upgrade - Arzonet',
            'order_tags' => $metaData
        ]);

        return $response->json();
    }

    public function getOrder($orderId)
    {
        $response = Http::withHeaders([
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version' => '2023-08-01',
        ])->get($this->baseUrl . '/' . $orderId);

        return $response->json();
    }
}
