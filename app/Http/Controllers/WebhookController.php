<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailLog;
use App\Models\BlacklistedEmail;
use App\Models\ContactActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle AWS SES Webhooks (Bounce, Complaint)
     */
    public function handleSES(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Handle SNS Subscription Confirmation
        if (isset($payload['Type']) && $payload['Type'] === 'SubscriptionConfirmation') {
            Log::info("SNS Subscription Confirmation URL: " . $payload['SubscribeURL']);
            return response()->json(['message' => 'Confirmation logged'], 200);
        }

        // Handle SES Notification
        if (isset($payload['Message'])) {
            $message = json_decode($payload['Message'], true);
            $type = $message['notificationType'] ?? null;

            if ($type === 'Bounce') {
                $this->processBounce($message['bounce']);
            } elseif ($type === 'Complaint') {
                $this->processComplaint($message['complaint']);
            }
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    protected function processBounce($bounce)
    {
        $type = $bounce['bounceType']; // Permanent, Transient
        
        foreach ($bounce['bouncedRecipients'] as $recipient) {
            $emailAddress = $recipient['emailAddress'];
            
            Log::warning("Email Bounced: {$emailAddress} (Type: {$type})");

            // 1. Update Global Status
            Email::where('email', $emailAddress)->update([
                'status' => 'invalid',
                'reason' => 'Bounced: ' . $type
            ]);

            // 2. Update Logs for Analytics
            EmailLog::where('email_address', $emailAddress)
                ->where('status', 'sent')
                ->update([
                    'status' => 'bounced',
                    'error_message' => 'Bounce Type: ' . $type
                ]);

            // 3. Blacklist if Permanent
            if ($type === 'Permanent') {
                BlacklistedEmail::firstOrCreate(
                    ['email' => $emailAddress],
                    ['reason' => 'Hard Bounce recorded via SES Webhook']
                );
            }

            // 4. Log Activity
            $email = Email::where('email', $emailAddress)->first();
            if ($email) {
                ContactActivity::create([
                    'email_id' => $email->id,
                    'type' => 'bounced',
                    'meta' => ['bounce_type' => $type]
                ]);
            }
        }
    }

    protected function processComplaint($complaint)
    {
        foreach ($complaint['complainedRecipients'] as $recipient) {
            $emailAddress = $recipient['emailAddress'];
            
            Log::error("Spam Complaint Received: {$emailAddress}");

            // 1. Mark as Unsubscribed
            Email::where('email', $emailAddress)->update([
                'subscription_status' => 'unsubscribed',
                'reason' => 'Spam Complaint'
            ]);

            // 2. Blacklist immediately
            BlacklistedEmail::firstOrCreate(
                ['email' => $emailAddress],
                ['reason' => 'Spam Complaint recorded via SES Webhook']
            );
        }
    }

    public function handleCashfree(Request $request)
    {
        $payload = $request->all();
        Log::info("Cashfree Webhook Received", $payload);

        // In a real app, verify the signature here
        // For this demo, we check if the payment is SUCCESS
        $orderId = $payload['data']['order']['order_id'] ?? null;
        $status = $payload['data']['order']['order_status'] ?? null;

        if ($orderId && $status === 'PAID') {
            $invoice = \App\Models\Invoice::where('payment_id', $orderId)->first();

            if ($invoice && $invoice->status !== 'paid') {
                $invoice->update([
                    'status' => 'paid',
                    'payment_id' => $payload['data']['payment']['cf_payment_id'] ?? $orderId
                ]);

                // Update User Subscription
                $details = $invoice->plan_details;
                \App\Models\Subscription::updateOrCreate(
                    ['user_id' => $invoice->user_id],
                    [
                        'contacts_limit' => $details['contacts_limit'],
                        'emails_limit' => $details['emails_limit'],
                        'plan_name' => 'Custom Power Plan',
                        'status' => 'active',
                        'starts_at' => now(),
                        'ends_at' => now()->addMonth(),
                    ]
                );

                // Send Invoice Email
                try {
                    \Illuminate\Support\Facades\Mail::to($invoice->user->email)->send(new \App\Mail\InvoiceMail($invoice));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to send invoice email via webhook: " . $e->getMessage());
                }

                Log::info("Plan activated for User: " . $invoice->user_id);
            }
        }

        return response()->json(['status' => 'OK']);
    }
}
