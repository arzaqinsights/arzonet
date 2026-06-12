<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Email;
use App\Models\EmailStatus;
use Illuminate\Support\Facades\DB;

class SnsController extends Controller
{
    /**
     * Handle AWS SNS Webhook for Amazon SES Notifications.
     */
    public function handle(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['Type'])) {
                return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
            }

            Log::info('SNS Webhook received', ['type' => $data['Type']]);

            // 1. Handle Subscription Confirmation
            if ($data['Type'] === 'SubscriptionConfirmation') {
                Http::get($data['SubscribeURL']);
                return response()->json(['status' => 'ok', 'message' => 'Subscribed']);
            }

            // 2. Handle Notifications
            if ($data['Type'] === 'Notification') {
                $message = json_decode($data['Message'], true);
                $type = $message['notificationType'] ?? null;

                switch ($type) {
                    case 'Bounce':
                        $this->handleBounce($message);
                        break;
                    case 'Complaint':
                        $this->handleComplaint($message);
                        break;
                    case 'Delivery':
                        $this->handleDelivery($message);
                        break;
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('SNS Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API to view email statuses (Bonus)
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = EmailStatus::query();

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(50));
    }

    protected function handleBounce(array $message)
    {
        $bounce = $message['bounce'];
        $bounceType = $bounce['bounceType']; // Permanent, Transient
        $sesMessageId = $message['mail']['messageId'] ?? null;

        foreach ($bounce['bouncedRecipients'] as $recipient) {
            $email = $recipient['emailAddress'];
            $reason = $recipient['diagnosticCode'] ?? ($bounce['bounceSubType'] ?? 'SES Bounce');

            // 1. Global Suppression
            if ($bounceType === 'Permanent') {
                EmailStatus::updateOrCreate(['email' => $email], ['status' => 'bounced']);
            }

            // 2. Update local Email records
            $updateData = [
                'status' => 'invalid',
                'subscription_status' => ($bounceType === 'Permanent') ? 'bounced' : 'soft_bounce',
                'reason' => $reason,
                // New health columns
                'email_status' => ($bounceType === 'Permanent') ? 'hard_bounce' : 'soft_bounce',
                'last_bounce_type' => strtolower($bounceType),
                'last_campaign_status' => 'bounced',
            ];

            if ($bounceType === 'Permanent') {
                $updateData['email_score'] = 1;
                $updateData['bounce_count'] = DB::raw('bounce_count + 1');
                $updateData['subscribed_topics'] = json_encode([]);
            } else {
                $updateData['email_score'] = DB::raw('GREATEST(1, email_score - 1)');
            }

            Email::where('email', $email)->update($updateData);

            // 3. Update EmailLog (Analytics)
            if ($sesMessageId) {
                $log = \App\Models\EmailLog::where('message_id', $sesMessageId)->where('email_address', $email)->first();
                $wasNotBounced = $log && $log->status !== 'bounced';

                \App\Models\EmailLog::where('message_id', $sesMessageId)
                    ->where('email_address', $email)
                    ->update([
                        'status' => 'bounced',
                        'bounce_type' => strtolower($bounceType),
                        'bounce_reason' => $reason,
                    ]);
                
                if ($log) {
                    $campaign = $log->campaign;
                    if ($campaign && $wasNotBounced) {
                        $campaign->decrement('sent_count');
                        $campaign->increment('bounce_count');
                    }
                    \App\Jobs\ProcessTrackingEventJob::dispatch($log->id, 'bounce', ['reason' => $reason]);
                }
            }

            Log::warning('Email Suppressed (Bounce): ' . $email . ' - ' . $reason);
        }
    }

    protected function handleComplaint(array $message)
    {
        $complaint = $message['complaint'];
        $sesMessageId = $message['mail']['messageId'] ?? null;

        foreach ($complaint['complainedRecipients'] as $recipient) {
            $email = $recipient['emailAddress'];

            // 1. Global Suppression
            EmailStatus::updateOrCreate(['email' => $email], ['status' => 'complaint']);

            // 2. Update local Email records
            Email::where('email', $email)->update([
                'status' => 'invalid',
                'subscription_status' => 'unsubscribed',
                'unsubscribed_at' => now(),
                'reason' => 'SES Complaint',
                // New health columns
                'email_status' => 'complaint',
                'email_score' => 1,
                'complaint_count' => DB::raw('complaint_count + 1'),
                'last_campaign_status' => 'complaint',
                'subscribed_topics' => json_encode([]),
            ]);

            // 3. Update EmailLog (Analytics)
            if ($sesMessageId) {
                \App\Models\EmailLog::where('message_id', $sesMessageId)
                    ->where('email_address', $email)
                    ->update(['status' => 'complaint']);

                $log = \App\Models\EmailLog::where('message_id', $sesMessageId)->where('email_address', $email)->first();
                if ($log) {
                    \App\Jobs\ProcessTrackingEventJob::dispatch($log->id, 'complaint', []);
                }
            }

            Log::warning('Email Suppressed (Complaint): ' . $email);
        }
    }

    protected function handleDelivery(array $message)
    {
        $sesMessageId = $message['mail']['messageId'] ?? null;
        $deliveryTime = $message['delivery']['timestamp'] ?? now();

        if ($sesMessageId) {
            $log = \App\Models\EmailLog::where('message_id', $sesMessageId)->first();
            if ($log) {
                $log->update([
                    'status' => 'delivered',
                    'delivered_at' => $deliveryTime
                ]);
                
                // Update health metrics
                Email::where('id', $log->email_id)->update([
                    'email_status' => 'clean',
                    'email_score' => DB::raw('LEAST(5, email_score + 1)'),
                    'last_campaign_status' => 'delivered',
                ]);

                \App\Jobs\ProcessTrackingEventJob::dispatch($log->id, 'delivery', []);
            }
        }

        Log::info('SES Delivery', ['recipients' => $message['delivery']['recipients']]);
    }
}
