<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\ContactActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    /**
     * Track Email Open (Pixel)
     */
    public function open($logId)
    {
        $log = EmailLog::find($logId);
        
        if ($log) {
            $emailId = $log->email_id;
            
            // Check if the email still exists, if not, try to find a new record for the same address
            if (!\App\Models\Email::where('id', $emailId)->exists()) {
                $currentEmail = \App\Models\Email::where('email', $log->email_address)->first();
                if ($currentEmail) {
                    $emailId = $currentEmail->id;
                    $log->update(['email_id' => $emailId]);
                } else {
                    $emailId = null;
                }
            }

            ContactActivity::create([
                'email_id' => $emailId,
                'campaign_id' => $log->campaign_id,
                'type' => 'opened',
                'meta' => [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ]);
            
            if ($emailId && $email = \App\Models\Email::find($emailId)) {
                $email->update(['last_active_at' => now()]);
            }
        }

        // Return 1x1 transparent pixel
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Track Link Click
     */
    public function click(Request $request, $logId)
    {
        $url = $request->get('u');
        $log = EmailLog::find($logId);

        if ($log && $url) {
            $emailId = $log->email_id;

            if (!\App\Models\Email::where('id', $emailId)->exists()) {
                $currentEmail = \App\Models\Email::where('email', $log->email_address)->first();
                if ($currentEmail) {
                    $emailId = $currentEmail->id;
                    $log->update(['email_id' => $emailId]);
                } else {
                    $emailId = null;
                }
            }

            ContactActivity::create([
                'email_id' => $emailId,
                'campaign_id' => $log->campaign_id,
                'type' => 'clicked',
                'url' => $url,
                'meta' => [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ]);

            if ($emailId && $email = \App\Models\Email::find($emailId)) {
                $email->update(['last_active_at' => now()]);
            }
        }

        return redirect()->away($url ?? route('dashboard'));
    }
}
