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
        foreach ($bounce['bouncedRecipients'] as $recipient) {
            $email = $recipient['emailAddress'];

            // Update suppression list
            EmailStatus::updateOrCreate(
                ['email' => $email],
                ['status' => 'bounced']
            );

            // Update local Email records if they exist
            Email::where('email', $email)->update([
                'status' => 'invalid',
                'subscription_status' => 'bounced',
                'reason' => 'SES Bounce'
            ]);

            Log::warning('Email Suppressed (Bounce): ' . $email);
        }
    }

    protected function handleComplaint(array $message)
    {
        $complaint = $message['complaint'];
        foreach ($complaint['complainedRecipients'] as $recipient) {
            $email = $recipient['emailAddress'];

            // Update suppression list
            EmailStatus::updateOrCreate(
                ['email' => $email],
                ['status' => 'complaint']
            );

            // Update local Email records if they exist
            Email::where('email', $email)->update([
                'status' => 'invalid',
                'subscription_status' => 'unsubscribed',
                'unsubscribed_at' => now(),
                'reason' => 'SES Complaint'
            ]);

            Log::warning('Email Suppressed (Complaint): ' . $email);
        }
    }

    protected function handleDelivery(array $message)
    {
        // Deliveries are logged for analytics, but don't affect suppression usually
        Log::info('SES Delivery', ['recipients' => $message['delivery']['recipients']]);
    }
}
