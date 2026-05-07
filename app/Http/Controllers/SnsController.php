<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Email;
use Illuminate\Support\Facades\DB;

class SnsController extends Controller
{
    /**
     * Handle AWS SNS Webhook for Amazon SES Notifications.
     */
    public function handle(Request $request)
    {
        // AWS SNS sends JSON payload as raw body
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['Type'])) {
            Log::warning('SNS Webhook: Invalid payload received', ['body' => $request->getContent()]);
            return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        // Log the request for debugging and audit trail
        Log::info('SNS Webhook received', [
            'type' => $data['Type'], 
            'message_id' => $data['MessageId'] ?? 'N/A',
            'topic' => $data['TopicArn'] ?? 'N/A'
        ]);

        // 1. Handle Subscription Confirmation
        if ($data['Type'] === 'SubscriptionConfirmation') {
            Log::info('SNS Subscription Confirmation', ['url' => $data['SubscribeURL']]);
            
            // AWS requires us to GET the SubscribeURL to confirm the endpoint
            Http::get($data['SubscribeURL']);
            
            return response()->json(['status' => 'ok', 'message' => 'Subscribed']);
        }

        // 2. Handle Notifications (Bounces, Complaints, Deliveries)
        if ($data['Type'] === 'Notification') {
            $message = json_decode($data['Message'], true);

            if (!$message || !isset($message['notificationType'])) {
                Log::error('SNS Webhook: Message part is missing notificationType', ['message' => $data['Message']]);
                return response()->json(['status' => 'error', 'message' => 'Invalid message format'], 400);
            }

            $type = $message['notificationType'];

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
                default:
                    Log::info('SNS Notification type received but not explicitly handled', ['type' => $type]);
                    break;
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle SES Bounce notification.
     */
    protected function handleBounce(array $message): void
    {
        $bounce = $message['bounce'];
        $timestamp = $bounce['timestamp'] ?? now();

        foreach ($bounce['bouncedRecipients'] as $recipient) {
            $emailAddress = $recipient['emailAddress'];
            
            Log::warning('SES Bounce Detected', [
                'email' => $emailAddress,
                'type' => $bounce['bounceType'],
                'sub_type' => $bounce['bounceSubType']
            ]);

            // 1. Store event in history
            DB::table('ses_events')->insert([
                'email' => $emailAddress,
                'type' => 'bounce',
                'sub_type' => $bounce['bounceType'] . ':' . ($bounce['bounceSubType'] ?? 'N/A'),
                'message_id' => $message['mail']['messageId'] ?? null,
                'payload' => json_encode($message),
                'occurred_at' => $timestamp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Update status in audience table
            Email::where('email', $emailAddress)->update([
                'status' => 'invalid',
                'subscription_status' => 'bounced',
                'reason' => 'Bounced: ' . ($bounce['bounceType'] ?? 'Permanent')
            ]);
        }
    }

    /**
     * Handle SES Complaint notification.
     */
    protected function handleComplaint(array $message): void
    {
        $complaint = $message['complaint'];
        $timestamp = $complaint['timestamp'] ?? now();

        foreach ($complaint['complainedRecipients'] as $recipient) {
            $emailAddress = $recipient['emailAddress'];

            Log::alert('SES Complaint Received', [
                'email' => $emailAddress,
                'type' => $complaint['complaintFeedbackType'] ?? 'N/A'
            ]);

            // 1. Store event in history
            DB::table('ses_events')->insert([
                'email' => $emailAddress,
                'type' => 'complaint',
                'sub_type' => $complaint['complaintFeedbackType'] ?? 'N/A',
                'message_id' => $message['mail']['messageId'] ?? null,
                'payload' => json_encode($message),
                'occurred_at' => $timestamp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Update status in audience table - immediately unsubscribe them
            Email::where('email', $emailAddress)->update([
                'status' => 'invalid',
                'subscription_status' => 'unsubscribed',
                'unsubscribed_at' => now(),
                'reason' => 'SES Complaint: ' . ($complaint['complaintFeedbackType'] ?? 'N/A')
            ]);
        }
    }

    /**
     * Handle SES Delivery notification.
     */
    protected function handleDelivery(array $message): void
    {
        $delivery = $message['delivery'];
        $timestamp = $delivery['timestamp'] ?? now();

        foreach ($delivery['recipients'] as $emailAddress) {
            // Log delivery for analytics if needed
            DB::table('ses_events')->insert([
                'email' => $emailAddress,
                'type' => 'delivery',
                'message_id' => $message['mail']['messageId'] ?? null,
                'payload' => json_encode($message),
                'occurred_at' => $timestamp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
