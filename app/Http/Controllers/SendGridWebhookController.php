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
            $type = $event['event'] ?? null;
            $logId = $event['log_id'] ?? null;
            $log = null;

            if (!$type) continue;

            if ($logId) {
                $log = EmailLog::find($logId);
            }

            if (!$log && $messageId) {
                // Extract the base message ID (SendGrid appends .filter stuff)
                $baseId = explode('.', $messageId)[0];
                $log = EmailLog::where('message_id', 'LIKE', $baseId . '%')->first();
            }

            if (!$log) {
                Log::warning("SendGrid Webhook: No log found for message_id: {$messageId} and log_id: {$logId}");
                continue;
            }

            $campaign = $log->campaign;

            switch ($type) {
                case 'processed':
                    if (in_array($log->status, ['pending', 'sent'])) {
                        $log->update(['status' => 'processed']);
                    }
                    break;

                case 'delivered':
                    if (in_array($log->status, ['pending', 'sent', 'processed'])) {
                        $log->update(['status' => 'delivered', 'delivered_at' => now(), 'error_message' => null]);
                    }
                    break;

                case 'open':
                    // Prevent duplicate counting from SendGrid (idempotency)
                    $eventId = $event['sg_event_id'] ?? null;
                    if ($eventId && EmailEvent::where('metadata->sg_event_id', $eventId)->exists()) {
                        break;
                    }

                    $log->increment('open_count');
                    
                    $updateData = ['last_open_at' => now()];
                    if (!$log->first_open_at) {
                        $updateData['first_open_at'] = now();
                    }
                    
                    // Auto-promote to delivered if it's currently processed/sent
                    if (in_array($log->status, ['sent', 'processed', 'pending'])) {
                        $updateData['status'] = 'delivered';
                        $updateData['delivered_at'] = $log->delivered_at ?? now();
                    }
                    
                    $log->update($updateData);

                    // Log event for activity feed
                    EmailEvent::create([
                        'email_log_id' => $log->id,
                        'type' => 'open',
                        'ip_address' => $event['ip'] ?? null,
                        'user_agent' => $event['useragent'] ?? null,
                        'metadata' => [
                            'sg_event_id' => $eventId,
                            'sg_machine_open' => $event['sg_machine_open'] ?? false,
                        ],
                        'created_at' => now(),
                    ]);
                    break;

                case 'click':
                    // Prevent duplicate counting
                    $eventId = $event['sg_event_id'] ?? null;
                    if ($eventId && EmailEvent::where('metadata->sg_event_id', $eventId)->exists()) {
                        break;
                    }

                    $log->increment('click_count');
                    
                    $updateData = [];
                    if (!$log->clicked_at) {
                        $updateData['clicked_at'] = now();
                    }

                    // Auto-promote to delivered if it's currently processed/sent
                    if (in_array($log->status, ['sent', 'processed', 'pending'])) {
                        $updateData['status'] = 'delivered';
                        $updateData['delivered_at'] = $log->delivered_at ?? now();
                    }

                    if (!empty($updateData)) {
                        $log->update($updateData);
                    }

                    EmailEvent::create([
                        'email_log_id' => $log->id,
                        'type' => 'click',
                        'url' => $event['url'] ?? null,
                        'ip_address' => $event['ip'] ?? null,
                        'user_agent' => $event['useragent'] ?? null,
                        'metadata' => [
                            'sg_event_id' => $eventId,
                        ],
                        'created_at' => now(),
                    ]);
                    break;

                case 'bounce':
                    if ($log->status !== 'bounced') {
                        $reason = $event['reason'] ?? 'Bounced';
                        $log->update(['status' => 'bounced', 'error_message' => $reason]);
                        
                        // Smart Bounce Handling: Distinguish Soft vs Hard
                        if ($log->email) {
                            $isSoftBounce = stripos($reason, 'full') !== false || 
                                            stripos($reason, 'quota') !== false || 
                                            stripos($reason, 'temporary') !== false ||
                                            stripos($reason, 'limit') !== false ||
                                            stripos($reason, 'over') !== false;

                            if ($isSoftBounce) {
                                // Soft Bounce: Keep subscribed, just lower score
                                $log->email->update([
                                    'email_status'      => 'soft_bounce',
                                    'email_score'       => max(1, $log->email->email_score - 1), // Lower score by 1
                                    'validation_reason' => 'Soft Bounce: ' . $reason
                                ]);
                            } else {
                                // Hard Bounce: Invalid and Unsubscribe
                                $log->email->update([
                                    'status'              => 'invalid', 
                                    'email_status'        => 'hard_bounce',
                                    'subscription_status' => 'unsubscribed',
                                    'unsubscribed_at'     => now(),
                                    'email_score'         => 1,
                                    'validation_reason'   => 'Hard Bounce: ' . $reason
                                ]);
                            }
                        }
                    }
                    break;

                case 'dropped':
                    $reason = $event['reason'] ?? 'Dropped by SendGrid';
                    $log->update(['status' => 'dropped', 'error_message' => $reason]);
                    
                    if ($log->email) {
                        if (stripos($reason, 'Unsubscribed') !== false) {
                            $log->email->update([
                                'subscription_status' => 'unsubscribed', 
                                'unsubscribed_at' => now(),
                                'email_status' => 'invalid'
                            ]);
                        } elseif (stripos($reason, 'Bounced') !== false || stripos($reason, 'Invalid') !== false) {
                            $log->email->update([
                                'status' => 'invalid', 
                                'email_status' => 'hard_bounce',
                                'subscription_status' => 'unsubscribed',
                                'unsubscribed_at' => now(),
                                'email_score' => 1,
                                'validation_reason' => $reason
                            ]);
                        }
                    }
                    break;

                case 'blocked':
                    $log->update(['status' => 'blocked', 'error_message' => $event['reason'] ?? 'Blocked by Receiving MTA']);
                    break;

                case 'spamreport':
                    $log->update(['status' => 'spamreport', 'error_message' => 'Spam Complaint']);
                    
                    // Record unsubscribe for analytics
                    Unsubscribe::firstOrCreate([
                        'email' => $log->email_address,
                        'campaign_id' => $log->campaign_id,
                    ], ['unsubscribed_at' => now()]);

                    // Update list-specific email status
                    if ($log->email) {
                        $log->email->update(['subscription_status' => 'unsubscribed']);
                    }
                    break;

                case 'unsubscribe':
                    $log->update(['status' => 'unsubscribed']);
                    
                    Unsubscribe::firstOrCreate([
                        'email' => $log->email_address,
                        'campaign_id' => $log->campaign_id,
                    ], ['unsubscribed_at' => now()]);

                    // Update list-specific email status
                    if ($log->email) {
                        $log->email->update(['subscription_status' => 'unsubscribed', 'unsubscribed_at' => now()]);
                    }
                    break;

                case 'deferred':
                    $log->update(['status' => 'deferred', 'error_message' => 'Deferred: ' . ($event['reason'] ?? 'Temporary failure')]);
                    break;
            }

            if ($log && $log->email_id) {
                \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $log->email_id);
            }
        }

        return response()->json(['status' => 'success']);
    }
}

