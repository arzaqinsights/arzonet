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

        return view('auth.unsubscribe', compact('email', 'token', 'logId'));
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

        $duration = $request->input('duration', 'forever');
        $expiresAt = null;
        $durationText = 'permanently';

        if ($duration !== 'forever') {
            $days = (int) $duration;
            if ($days > 0) {
                $expiresAt = now()->addDays($days);
                $durationText = "for {$days} days (until " . $expiresAt->format('F d, Y') . ")";
            }
        }

        // 1. Mark ONLY this specific record as unsubscribed (Isolated to this list)
        $email->update([
            'subscription_status' => 'unsubscribed',
            'unsubscribed_at' => now(),
            'unsubscribe_expires_at' => $expiresAt,
        ]);

        // Trigger segment recalculation for this contact
        \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $email->id);

        // 3. Log the Unsubscribe for Analytics
        if ($logId) {
            $log = EmailLog::find($logId);
            if ($log) {
                // Campaign Specific Analytics
                Unsubscribe::create([
                    'email' => $email->email,
                    'campaign_id' => $log->campaign_id,
                    'unsubscribed_at' => now(),
                ]);

                // Activity Feed
                ContactActivity::create([
                    'email_id' => $email->id,
                    'campaign_id' => $log->campaign_id,
                    'type' => 'unsubscribed',
                ]);
            }
        }

        return view('auth.unsubscribe-success', compact('email', 'durationText', 'expiresAt'));
    }
}
