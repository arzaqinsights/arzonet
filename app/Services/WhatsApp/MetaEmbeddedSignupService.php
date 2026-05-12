<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
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
    public function completeOnboarding(string $code, int $userId): WhatsAppAccount
    {
        return DB::transaction(function () use ($code, $userId) {
            // 1. Exchange code for long-lived token
            $tokenData = $this->tokenExchange->exchangeCodeForToken($code);
            $accessToken = $tokenData['access_token'];

            // 2. Fetch Business Accounts
            $businessAccounts = $this->accountSync->getBusinessAccounts($accessToken);

            if (empty($businessAccounts)) {
                throw new Exception("No WhatsApp Business Accounts found for this user.");
            }

            // For simplicity in this flow, we take the first one or iterate.
            // A more advanced flow would let the user pick, but usually with code exchange,
            // we get the IDs that were just authorized.
            $waba = $businessAccounts[0];
            $wabaId = $waba['id'];

            // 3. Fetch Phone Numbers
            $phoneNumbers = $this->accountSync->getPhoneNumbers($wabaId, $accessToken);

            if (empty($phoneNumbers)) {
                throw new Exception("No phone numbers found for the WhatsApp Business Account.");
            }

            $primaryPhone = $phoneNumbers[0];

            // 4. Subscribe to Webhooks
            $this->webhookSubscription->subscribeWabaToApp($wabaId, $accessToken);

            // 5. Store/Update WhatsApp Account
            $account = WhatsAppAccount::updateOrCreate(
                [
                    'user_id' => $userId,
                    'whatsapp_business_account_id' => $wabaId,
                ],
                [
                    'business_name' => $waba['name'] ?? 'WhatsApp Business Account',
                    'display_name' => $primaryPhone['verified_name'] ?? 'WhatsApp Number',
                    'phone_number' => $primaryPhone['display_phone_number'] ?? null,
                    'phone_number_id' => $primaryPhone['id'] ?? null,
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
