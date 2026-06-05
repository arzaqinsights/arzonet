<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\EmailEvent;
use App\Services\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ProcessTrackingEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $logId,
        public string $type,
        public array $data
    ) {}

    public function handle(TrackingService $tracker): void
    {
        $log = EmailLog::with('email')->find($this->logId);
        if (!$log) return;

        $metadata = $tracker->parseMetadata($this->data['ua'] ?? '');
        
        // 1. Log Granular Event
        $eventRecord = EmailEvent::create([
            'email_log_id' => $log->id,
            'type'         => $this->type,
            'url'          => $this->data['url'] ?? null,
            'ip_address'   => $this->data['ip'] ?? null,
            'user_agent'   => $this->data['ua'] ?? null,
            'metadata'     => $metadata,
            'created_at'   => now(),
        ]);

        // 2. Update Log Stats (Deduplicated)
        $updates = [];
        $recentEvent = EmailEvent::where('email_log_id', $log->id)
            ->where('type', $this->type)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->where('id', '!=', $eventRecord->id) // Exclude the one we just created
            ->exists();

        if (!$recentEvent) {
            if ($this->type === 'open') {
                $updates['open_count'] = $log->open_count + 1;
                if (!$log->first_open_at) $updates['first_open_at'] = now();
                $updates['last_open_at'] = now();
            } elseif ($this->type === 'click') {
                $updates['click_count'] = $log->click_count + 1;
                if (!$log->clicked_at) $updates['clicked_at'] = now();
            }
        }

        if (!empty($updates)) {
            $log->update($updates);
        }

        // 3. Update Engagement Score for Recipient
        if ($log->email) {
            $scoreDelta = match($this->type) {
                'open' => 5,
                'click' => 20,
                'unsubscribe' => -100,
                default => 0
            };

            $log->email->update([
                'engagement_score' => max(0, $log->email->engagement_score + $scoreDelta),
                'last_engaged_at' => now()
            ]);

            // Dispatch segment updates for this contact (debounced)
            $lockKey = "webhook:segment_lock:{$log->email_id}";
            if (Redis::set($lockKey, '1', 'EX', 60, 'NX')) {
                \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $log->email_id)->onQueue('segments');
            }
        }
    }
}
