<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Exception;

class MetaEmbeddedSignupService
{
    public function __construct(
        protected MetaTokenExchangeService $tokenExchange,
        protected MetaAccountSyncService $accountSync,
        protected MetaWebhookSubscriptionService $webhookSubscription
    ) {}

    /**
     * Complete the onboarding flow.
     */
    public function completeOnboarding(string $code, int $userId, ?string $clientWabaId = null, ?string $clientPhoneNumberId = null): WhatsAppAccount
    {
        return DB::transaction(function () use ($code, $userId, $clientWabaId, $clientPhoneNumberId) {
            // 1. Exchange code for long-lived token
            $tokenData = $this->tokenExchange->exchangeCodeForToken($code);
            $accessToken = $tokenData['access_token'];

            // 2. Use WABA ID from client postMessage if available, otherwise fetch from Meta API
            if ($clientWabaId) {
                Log::info('Using WABA ID from client postMessage', ['waba_id' => $clientWabaId]);
                $wabaId = $clientWabaId;
                $wabaName = 'WhatsApp Business Account';
            } else {
                $businessAccounts = $this->accountSync->getBusinessAccounts($accessToken);
                if (empty($businessAccounts)) {
                    throw new Exception("No WhatsApp Business Accounts found for this user.");
                }
                $waba = $businessAccounts[0];
                $wabaId = $waba['id'];
                $wabaName = $waba['name'] ?? 'WhatsApp Business Account';
            }

            // 3. Use Phone Number ID from client postMessage if available, otherwise fetch from Meta API
            if ($clientPhoneNumberId) {
                Log::info('Using Phone Number ID from client postMessage', ['phone_number_id' => $clientPhoneNumberId]);
                // Fetch details of this specific phone number
                $phoneNumbers = $this->accountSync->getPhoneNumbers($wabaId, $accessToken);
                $primaryPhone = collect($phoneNumbers)->firstWhere('id', $clientPhoneNumberId) ?? $phoneNumbers[0] ?? [];
            } else {
                $phoneNumbers = $this->accountSync->getPhoneNumbers($wabaId, $accessToken);
                if (empty($phoneNumbers)) {
                    throw new Exception("No phone numbers found for the WhatsApp Business Account.");
                }
                $primaryPhone = $phoneNumbers[0];
            }

            if (empty($primaryPhone)) {
                throw new Exception("Could not fetch phone number details from Meta.");
            }

            // 4. Subscribe to Webhooks
            $this->webhookSubscription->subscribeWabaToApp($wabaId, $accessToken);

            // 4.5 Register Phone Number (Cloud API activation)
            try {
                $api = app(MetaApiService::class);
                $api->registerPhoneNumber($primaryPhone['id'] ?? $clientPhoneNumberId, $accessToken);
            } catch (\Exception $regEx) {
                Log::warning('WhatsApp Registration Step Failed during onboarding: ' . $regEx->getMessage());
            }

            // 5. Store/Update WhatsApp Account
            $account = WhatsAppAccount::updateOrCreate(
                [
                    'user_id' => $userId,
                    'whatsapp_business_account_id' => $wabaId,
                ],
                [
                    'business_name' => $wabaName,
                    'display_name' => $primaryPhone['verified_name'] ?? 'WhatsApp Number',
                    'phone_number' => $primaryPhone['display_phone_number'] ?? null,
                    'phone_number_id' => $primaryPhone['id'] ?? $clientPhoneNumberId,
                    'access_token' => Crypt::encryptString($accessToken),
                    'status' => 'active',
                    'metadata' => [
                        'quality_rating' => $primaryPhone['quality_rating'] ?? 'UNKNOWN',
                        'messaging_limit' => $primaryPhone['messaging_limit_tier'] ?? 'UNKNOWN',
                    ],
                ]
            );

            return $account;
        });
    }
}
