<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\EmailLog;
use App\Models\Unsubscribe;
use App\Models\ContactActivity;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(Request $request, $id)
    {
        $email = Email::find($id);
        $logId = $request->query('lid');
        
        // Robust Lookup: If contact was deleted, try finding them via the email log
        if (!$email && $logId) {
            $log = EmailLog::find($logId);
            if ($log) {
                // Try to find if they've been re-added since deletion
                $email = Email::where('email', $log->email_address)->first();
            }
        }

        if (!$email) {
            abort(404, 'Contact not found. You may have already been removed.');
        }

        $token = $request->query('token');
        $expectedToken = hash_hmac('sha256', $id . $email->email, config('app.key'));
        
        if (!hash_equals($expectedToken, (string)$token)) {
            abort(403, 'Invalid unsubscribe link.');
        }

        $topics = \App\Models\SubscriptionTopic::where('email_list_id', $email->email_list_id)->get();

        return view('auth.unsubscribe', compact('email', 'token', 'logId', 'topics'));
    }

    public function confirm(Request $request, $id)
    {
        $email = Email::find($id);
        $logId = $request->input('lid');
        
        if (!$email && $logId) {
            $log = EmailLog::find($logId);
            if ($log) {
                $email = Email::where('email', $log->email_address)->first();
            }
        }

        if (!$email) {
            return view('auth.unsubscribe-success'); // Already gone
        }

        $token = $request->input('token');
        $expectedToken = hash_hmac('sha256', $id . $email->email, config('app.key'));
        
        if (!hash_equals($expectedToken, (string)$token)) {
            abort(403, 'Invalid request.');
        }

        $selectedTopicIds = $request->input('topics', []);
        $globalUnsubscribe = empty($selectedTopicIds);
        $durationText = 'permanently';

        if ($globalUnsubscribe) {
            // Mark ONLY this specific record as unsubscribed (Isolated to this list/workspace)
            $email->update([
                'subscription_status' => 'unsubscribed',
                'whatsapp_subscription_status' => 'unsubscribed',
                'unsubscribed_at' => now(),
                'subscribed_topics' => [], // Empty means subscribed to nothing
            ]);
            $durationText = 'from all updates';
        } else {
            // Keep status as subscribed, but update their specific topic list
            $email->update([
                'subscription_status' => 'subscribed',
                'whatsapp_subscription_status' => 'subscribed',
                'unsubscribed_at' => null,
                'unsubscribe_expires_at' => null,
                'subscribed_topics' => array_map('intval', $selectedTopicIds),
            ]);
            $durationText = 'for selected topics';
        }

        // Trigger segment recalculation for this contact
        \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $email->id);

        // 3. Log the Unsubscribe for Analytics
        if ($logId) {
            $log = EmailLog::find($logId);
            if ($log) {
                // Campaign Specific Analytics
                if ($globalUnsubscribe) {
                    Unsubscribe::create([
                        'email' => $email->email,
                        'campaign_id' => $log->campaign_id,
                        'unsubscribed_at' => now(),
                    ]);
                }

                // Activity Feed
                ContactActivity::create([
                    'email_id' => $email->id,
                    'campaign_id' => $log->campaign_id,
                    'type' => $globalUnsubscribe ? 'unsubscribed' : 'preferences_updated',
                ]);
            }
        }

        return view('auth.unsubscribe-success', compact('email', 'durationText', 'globalUnsubscribe'));
    }
}

