<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\BlacklistedEmail;
use App\Jobs\ProcessTrackingEventJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SESWebhookController extends Controller
{
    /**
     * Handle incoming SNS notifications from SES.
     */
    public function handle(Request $request)
    {
        $payload = json_decode($request->getContent(), true);

        // Handle SNS Subscription Confirmation
        if ($request->header('x-amz-sns-message-type') === 'SubscriptionConfirmation') {
            Log::info("SES Webhook Subscription Confirmation: " . $payload['SubscribeURL']);
            return response('OK');
        }

        $message = json_decode($payload['Message'] ?? '{}', true);
        $type = $message['notificationType'] ?? null;

        if (!$type) return response('No type', 400);

        $messageId = $message['mail']['messageId'] ?? null;
        $log = EmailLog::where('message_id', $messageId)->first();

        switch ($type) {
            case 'Delivery':
                $this->handleDelivery($log, $message);
                break;
            case 'Bounce':
                $this->handleBounce($log, $message);
                break;
            case 'Complaint':
                $this->handleComplaint($log, $message);
                break;
        }

        return response('OK');
    }

    protected function handleDelivery(?EmailLog $log, array $message)
    {
        if ($log) {
            $log->update([
                'status' => 'sent',
                'delivered_at' => now()
            ]);
            
            ProcessTrackingEventJob::dispatch($log->id, 'delivery', []);
        }
    }

    protected function handleBounce(?EmailLog $log, array $message)
    {
        $bounce = $message['bounce'];
        $bounceType = $bounce['bounceType']; // Permanent, Transient
        $recipients = $bounce['bouncedRecipients'];

        foreach ($recipients as $recipient) {
            $emailAddress = $recipient['emailAddress'];
            
            if ($log) {
                $log->update([
                    'status' => 'bounced',
                    'bounce_type' => strtolower($bounceType),
                    'bounce_reason' => $recipient['diagnosticCode'] ?? null
                ]);
            }

            // Auto-blacklist hard bounces
            if ($bounceType === 'Permanent') {
                BlacklistedEmail::firstOrCreate(['email' => strtolower($emailAddress)], [
                    'reason' => 'Hard Bounce: ' . ($recipient['diagnosticCode'] ?? 'Unknown')
                ]);
            }
            
            if ($log) {
                ProcessTrackingEventJob::dispatch($log->id, 'bounce', [
                    'reason' => $recipient['diagnosticCode'] ?? null
                ]);
            }
        }
    }

    protected function handleComplaint(?EmailLog $log, array $message)
    {
        $complaint = $message['complaint'];
        $recipients = $complaint['complainedRecipients'];

        foreach ($recipients as $recipient) {
            $emailAddress = $recipient['emailAddress'];
            
            if ($log) {
                $log->update(['status' => 'complained']);
            }

            // Always blacklist complaints
            BlacklistedEmail::firstOrCreate(['email' => strtolower($emailAddress)], [
                'reason' => 'Spam Complaint'
            ]);

            if ($log) {
                ProcessTrackingEventJob::dispatch($log->id, 'complaint', []);
            }
        }
    }
}
