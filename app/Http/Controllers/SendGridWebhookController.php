<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\Campaign;
use App\Models\Unsubscribe;
use App\Models\EmailEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SendGridWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $events = $request->all();

        foreach ($events as $event) {
            if (!is_array($event)) continue;

            $messageId = $event['sg_message_id'] ?? null;
            $email = $event['email'] ?? null;
            $type = $event['event'] ?? null;

            if (!$messageId || !$type) continue;

            // Extract the base message ID (SendGrid appends .filter stuff)
            $baseId = explode('.', $messageId)[0];

            $log = EmailLog::where('message_id', 'LIKE', $baseId . '%')->first();

            if (!$log) continue;

            $campaign = $log->campaign;

            switch ($type) {
                case 'delivered':
                    $log->update(['status' => 'delivered', 'delivered_at' => now()]);
                    break;

                case 'open':
                    $log->increment('open_count');
                    if (!$log->first_open_at) {
                        $log->update(['first_open_at' => now(), 'last_open_at' => now()]);
                    } else {
                        $log->update(['last_open_at' => now()]);
                    }

                    // Log event for activity feed
                    EmailEvent::create([
                        'email_log_id' => $log->id,
                        'type' => 'open',
                        'ip_address' => $event['ip'] ?? null,
                        'user_agent' => $event['useragent'] ?? null,
                        'metadata' => [
                            'sg_event_id' => $event['sg_event_id'] ?? null,
                            'sg_machine_open' => $event['sg_machine_open'] ?? false,
                        ],
                    ]);
                    break;

                case 'click':
                    $log->increment('click_count');
                    if (!$log->clicked_at) {
                        $log->update(['clicked_at' => now()]);
                    }

                    EmailEvent::create([
                        'email_log_id' => $log->id,
                        'type' => 'click',
                        'url' => $event['url'] ?? null,
                        'ip_address' => $event['ip'] ?? null,
                        'user_agent' => $event['useragent'] ?? null,
                        'metadata' => [
                            'sg_event_id' => $event['sg_event_id'] ?? null,
                        ],
                    ]);
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

                case 'deferred':
                    $log->update(['error_message' => 'Deferred: ' . ($event['reason'] ?? 'Temporary failure')]);
                    break;
            }
        }

        return response()->json(['status' => 'success']);
    }
}

