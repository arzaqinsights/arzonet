<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\EmailEvent;
use App\Models\Unsubscribe;
use App\Models\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ProcessWebhookBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Drain raw webhook payloads from Redis
        $payloads = [];
        $maxItems = 50; // Drain up to 50 raw batches
        $count = 0;

        while ($count < $maxItems && $payload = Redis::lpop('webhook:sendgrid:buffer')) {
            $payloads[] = $payload;
            $count++;
        }

        if (empty($payloads)) {
            return;
        }

        // 2. Flatten events
        $events = [];
        foreach ($payloads as $payload) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                foreach ($decoded as $event) {
                    if (is_array($event) && isset($event['event'])) {
                        $events[] = $event;
                    }
                }
            }
        }

        if (empty($events)) {
            return;
        }

        // 3. Preload all referenced EmailLog records
        $logIds = [];
        $messageIds = [];
        foreach ($events as $event) {
            if (isset($event['log_id'])) {
                $logIds[] = (int) $event['log_id'];
            }
            if (isset($event['sg_message_id'])) {
                $messageIds[] = $event['sg_message_id'];
            }
        }

        $logIds = array_unique(array_filter($logIds));
        $messageIds = array_unique(array_filter($messageIds));

        $logsById = collect();
        if (!empty($logIds)) {
            $logsById = EmailLog::with('email')->whereIn('id', $logIds)->get()->keyBy('id');
        }

        $logsByMessageId = collect();
        if (!empty($messageIds)) {
            $baseIds = array_unique(array_map(fn($id) => explode('.', $id)[0], $messageIds));
            
            $query = EmailLog::with('email');
            $query->where(function($q) use ($baseIds) {
                foreach ($baseIds as $baseId) {
                    $q->orWhere('message_id', 'LIKE', $baseId . '%');
                }
            });
            $logsByMessageId = $query->get();
        }

        // Merge all loaded logs
        $allLogs = $logsById->merge($logsByMessageId)->keyBy('id');

        $findLog = function($event) use ($logsById, $allLogs) {
            $logId = $event['log_id'] ?? null;
            if ($logId && $logsById->has($logId)) {
                return $logsById->get($logId);
            }
            
            $messageId = $event['sg_message_id'] ?? null;
            if ($messageId) {
                $baseId = explode('.', $messageId)[0];
                return $allLogs->first(fn($l) => str_starts_with($l->message_id, $baseId));
            }
            
            return null;
        };

        // Track data we need to insert/update in bulk
        $eventsToInsert = [];
        $unsubscribesToCreate = [];
        $emailUpdates = []; // email_id => [attributes]
        $logUpdates = []; // log_id => [attributes]
        $contactSegmentJobsToDispatch = [];

        // Track idempotency to avoid duplicate event insertions
        $sgEventIds = array_filter(array_column($events, 'sg_event_id'));
        $existingEventIds = [];
        if (!empty($sgEventIds)) {
            $existingEventIds = EmailEvent::whereIn('metadata->sg_event_id', $sgEventIds)
                ->pluck('metadata->sg_event_id')
                ->toArray();
        }

        foreach ($events as $event) {
            $log = $findLog($event);
            if (!$log) {
                continue;
            }

            $type = $event['event'];
            $eventId = $event['sg_event_id'] ?? null;

            // Track unique contact segment updates
            if ($log->email_id) {
                $contactSegmentJobsToDispatch[] = $log->email_id;
            }

            // Init log record tracked state in logUpdates
            if (!isset($logUpdates[$log->id])) {
                $logUpdates[$log->id] = [
                    'status' => $log->status,
                    'delivered_at' => $log->delivered_at,
                    'error_message' => $log->error_message,
                    'open_count' => $log->open_count,
                    'click_count' => $log->click_count,
                    'first_open_at' => $log->first_open_at,
                    'last_open_at' => $log->last_open_at,
                    'clicked_at' => $log->clicked_at,
                ];
            }

            $state = &$logUpdates[$log->id];

            switch ($type) {
                case 'processed':
                    if (in_array($state['status'], ['pending', 'sent'])) {
                        $state['status'] = 'processed';
                    }
                    break;

                case 'delivered':
                    if (in_array($state['status'], ['pending', 'sent', 'processed'])) {
                        $state['status'] = 'delivered';
                        $state['delivered_at'] = now();
                        $state['error_message'] = null;
                    }
                    break;

                case 'open':
                    if ($eventId && in_array($eventId, $existingEventIds)) {
                        break;
                    }
                    $existingEventIds[] = $eventId; // prevent duplicate opens in same run

                    $state['open_count']++;
                    $state['last_open_at'] = now();
                    if (!$state['first_open_at']) {
                        $state['first_open_at'] = now();
                    }
                    
                    if (in_array($state['status'], ['sent', 'processed', 'pending'])) {
                        $state['status'] = 'delivered';
                        $state['delivered_at'] = $state['delivered_at'] ?? now();
                    }

                    $eventsToInsert[] = [
                        'email_log_id' => $log->id,
                        'type'         => 'open',
                        'ip_address'   => $event['ip'] ?? null,
                        'user_agent'   => $event['useragent'] ?? null,
                        'metadata'     => json_encode([
                            'sg_event_id' => $eventId,
                            'sg_machine_open' => $event['sg_machine_open'] ?? false,
                        ]),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                    break;

                case 'click':
                    if ($eventId && in_array($eventId, $existingEventIds)) {
                        break;
                    }
                    $existingEventIds[] = $eventId; // prevent duplicate clicks in same run

                    $state['click_count']++;
                    if (!$state['clicked_at']) {
                        $state['clicked_at'] = now();
                    }

                    if (in_array($state['status'], ['sent', 'processed', 'pending'])) {
                        $state['status'] = 'delivered';
                        $state['delivered_at'] = $state['delivered_at'] ?? now();
                    }

                    $eventsToInsert[] = [
                        'email_log_id' => $log->id,
                        'type'         => 'click',
                        'url'          => $event['url'] ?? null,
                        'ip_address'   => $event['ip'] ?? null,
                        'user_agent'   => $event['useragent'] ?? null,
                        'metadata'     => json_encode([
                            'sg_event_id' => $eventId,
                        ]),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                    break;

                case 'bounce':
                    if ($state['status'] !== 'bounced') {
                        $reason = $event['reason'] ?? 'Bounced';
                        $state['status'] = 'bounced';
                        $state['error_message'] = $reason;

                        if ($log->email) {
                            $isSoftBounce = stripos($reason, 'full') !== false || 
                                            stripos($reason, 'quota') !== false || 
                                            stripos($reason, 'temporary') !== false ||
                                            stripos($reason, 'limit') !== false ||
                                            stripos($reason, 'over') !== false;

                            if ($isSoftBounce) {
                                $emailUpdates[$log->email_id] = [
                                    'email_status'      => 'soft_bounce',
                                    'email_score'       => max(1, $log->email->email_score - 1),
                                    'validation_reason' => 'Soft Bounce: ' . $reason
                                ];
                            } else {
                                $emailUpdates[$log->email_id] = [
                                    'status'              => 'invalid', 
                                    'email_status'        => 'hard_bounce',
                                    'subscription_status' => 'unsubscribed',
                                    'unsubscribed_at'     => now(),
                                    'email_score'         => 1,
                                    'validation_reason'   => 'Hard Bounce: ' . $reason
                                ];
                            }
                        }
                    }
                    break;

                case 'dropped':
                    $reason = $event['reason'] ?? 'Dropped by SendGrid';
                    $state['status'] = 'dropped';
                    $state['error_message'] = $reason;

                    if ($log->email) {
                        if (stripos($reason, 'Unsubscribed') !== false) {
                            $emailUpdates[$log->email_id] = [
                                'subscription_status' => 'unsubscribed', 
                                'unsubscribed_at' => now(),
                                'email_status' => 'invalid'
                            ];
                        } elseif (stripos($reason, 'Bounced') !== false || stripos($reason, 'Invalid') !== false) {
                            $emailUpdates[$log->email_id] = [
                                'status' => 'invalid', 
                                'email_status' => 'hard_bounce',
                                'subscription_status' => 'unsubscribed',
                                'unsubscribed_at' => now(),
                                'email_score' => 1,
                                'validation_reason' => $reason
                            ];
                        }
                    }
                    break;

                case 'blocked':
                    $state['status'] = 'blocked';
                    $state['error_message'] = $event['reason'] ?? 'Blocked by Receiving MTA';
                    break;

                case 'spamreport':
                    $state['status'] = 'spamreport';
                    $state['error_message'] = 'Spam Complaint';

                    $unsubscribesToCreate[] = [
                        'email'           => $log->email_address,
                        'campaign_id'     => $log->campaign_id,
                        'unsubscribed_at' => now(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    if ($log->email_id) {
                        $emailUpdates[$log->email_id] = [
                            'subscription_status' => 'unsubscribed'
                        ];
                    }
                    break;

                case 'unsubscribe':
                    $state['status'] = 'unsubscribed';

                    $unsubscribesToCreate[] = [
                        'email'           => $log->email_address,
                        'campaign_id'     => $log->campaign_id,
                        'unsubscribed_at' => now(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];

                    if ($log->email_id) {
                        $emailUpdates[$log->email_id] = [
                            'subscription_status' => 'unsubscribed',
                            'unsubscribed_at'     => now()
                        ];
                    }
                    break;

                case 'deferred':
                    $state['status'] = 'deferred';
                    $state['error_message'] = 'Deferred: ' . ($event['reason'] ?? 'Temporary failure');
                    break;
            }
        }

        // 4. Perform database operations in bulk inside a transaction
        DB::transaction(function () use ($logUpdates, $emailUpdates, $eventsToInsert, $unsubscribesToCreate) {
            // Bulk update email_logs
            foreach ($logUpdates as $logId => $state) {
                DB::table('email_logs')
                    ->where('id', $logId)
                    ->update($state);
            }

            // Bulk update emails
            foreach ($emailUpdates as $emailId => $fields) {
                DB::table('emails')
                    ->where('id', $emailId)
                    ->update($fields);
            }

            // Bulk insert email_events
            if (!empty($eventsToInsert)) {
                DB::table('email_events')->insert($eventsToInsert);
            }

            // Bulk insert unsubscribes
            if (!empty($unsubscribesToCreate)) {
                foreach ($unsubscribesToCreate as $unsub) {
                    DB::table('unsubscribes')->insertOrIgnore($unsub);
                }
            }
        });

        // 5. Debounce Segment Recalculation using a Redis lock (60 seconds)
        $uniqueEmailIds = array_unique($contactSegmentJobsToDispatch);
        foreach ($uniqueEmailIds as $emailId) {
            $lockKey = "webhook:segment_lock:{$emailId}";
            if (Redis::set($lockKey, '1', 'EX', 60, 'NX')) {
                \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $emailId)->onQueue('segments');
            }
        }
    }
}
