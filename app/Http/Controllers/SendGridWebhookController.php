<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\Campaign;
use App\Models\Unsubscribe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendGridWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $events = $request->all();

        foreach ($events as $event) {
            $messageId = $event['sg_message_id'] ?? null;
            $email = $event['email'] ?? null;
            $type = $event['event'] ?? null;

            if (!$messageId) continue;

            // Extract the base message ID (SendGrid appends stuff)
            $baseId = explode('.', $messageId)[0];

            $log = EmailLog::where('message_id', 'LIKE', $baseId . '%')->first();

            if ($log) {
                $campaign = $log->campaign;

                switch ($type) {
                    case 'delivered':
                        $log->update(['status' => 'delivered', 'delivered_at' => now()]);
                        break;

                    case 'bounce':
                    case 'dropped':
                        if ($log->status !== 'bounced') {
                            $log->update(['status' => 'bounced', 'error_message' => $event['reason'] ?? 'Bounced']);
                            if ($campaign) {
                                $campaign->decrement('sent_count');
                                $campaign->increment('bounce_count');
                            }
                        }
                        break;

                    case 'spamreport':
                        $log->update(['status' => 'complaint']);
                        Unsubscribe::firstOrCreate([
                            'email_id' => $log->email_id,
                            'campaign_id' => $log->campaign_id,
                        ], ['reason' => 'Spam Complaint (SendGrid)']);
                        break;

                    case 'unsubscribe':
                        Unsubscribe::firstOrCreate([
                            'email_id' => $log->email_id,
                            'campaign_id' => $log->campaign_id,
                        ], ['reason' => 'Unsubscribed via SendGrid']);
                        break;
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
