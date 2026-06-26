<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Email;
use App\Models\EmailLog;
use App\Jobs\SendEmailBatchJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PrepareCampaignDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    protected $campaignId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);

        if (!$campaign) {
            \Illuminate\Support\Facades\Log::warning("PrepareCampaignDispatchJob: Campaign ID {$this->campaignId} not found. Skipping execution.");
            return;
        }

        // Check if the campaign is in a state that allows sending
        if ($campaign->status === 'cancelled' || $campaign->status === 'completed') {
            return;
        }

        $query = $campaign->getAudienceQueryBuilder();

        if (!$query) {
            $campaign->update(['status' => 'failed']);
            return;
        }

        $totalRecipients = $campaign->getEstimatedRecipientCount();
        
        if ($totalRecipients === 0) {
            $campaign->update([
                'total_recipients' => 0,
                'status'           => 'completed',
                'completed_at'     => now(),
            ]);
            return;
        }

        $limit = isset($campaign->audience_config['limit']) && (int) $campaign->audience_config['limit'] > 0 
            ? (int) $campaign->audience_config['limit'] 
            : null;

        $campaign->update([
            'total_recipients' => $totalRecipients,
            'status'           => 'sending',
            'started_at'       => now(),
        ]);

        // Determine sending provider and settings
        $providerType = $campaign->sender?->type ?? 'ses';
        $batchSize = match($providerType) {
            'smtp' => 5,
            'sendgrid' => 50,
            'ses' => 200,
            default => 25
        };
        
        $queueName = "bulk-{$providerType}";
        $jobCount = 0;
        $emailsProcessed = 0;
 
        $redisKey = "campaign_{$campaign->id}_jobs_count";
        Redis::set($redisKey, 999999); // Padding to prevent premature completion
 
        // Use chunkById on emails to process without heavy memory overhead
        $query->chunkById(1000, function ($emails) use ($campaign, $batchSize, $queueName, &$jobCount, $providerType, $limit, &$emailsProcessed) {
            $logEntries = [];
            $emailIds = [];
 
            foreach ($emails as $email) {
                if ($limit && $emailsProcessed >= $limit) {
                    break;
                }
 
                $logEntries[] = [
                    'user_id'        => $campaign->user_id,
                    'campaign_id'    => $campaign->id,
                    'email_id'       => $email->id,
                    'email_address'  => $email->email,
                    'tracking_token' => Str::random(32),
                    'status'         => 'pending',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
                $emailIds[] = $email->id;
                $emailsProcessed++;
            }
 
            if (!empty($logEntries)) {
                // Raw Bulk Insert for speed and lower memory
                DB::table('email_logs')->insert($logEntries);
            }
 
            // Chunk email IDs to dispatch SendEmailBatchJob jobs
            foreach (array_chunk($emailIds, $batchSize) as $chunk) {
                // Adaptive delay for SMTP
                $delay = ($providerType === 'smtp') 
                    ? $this->calculateDelay($jobCount, $batchSize, $campaign->emails_per_minute)
                    : 0;
 
                SendEmailBatchJob::dispatch($campaign->id, $chunk)
                    ->onQueue($queueName)
                    ->delay(now()->addSeconds($delay));
 
                $jobCount++;
            }
 
            if ($limit && $emailsProcessed >= $limit) {
                return false; // Stop chunking early
            }
        });
 
        // Remove padding and check if already completed
        $remaining = Redis::decrBy($redisKey, 999999 - $jobCount);
        Redis::expire($redisKey, 86400); // 24-hour TTL
 
        if ($remaining <= 0 && $jobCount > 0) {
            $pendingCount = DB::table('email_logs')
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->count();
 
            if ($pendingCount === 0) {
                if (in_array($campaign->status, ['sending', 'preparing'])) {
                    $campaign->update(['status' => 'completed', 'completed_at' => now()]);
                }
            }
            // If pending emails still exist, don't force-complete — let jobs finish naturally
            Redis::del($redisKey);
        }
    }

    /**
     * Calculate delay for a batch based on rate limiting.
     */
    protected function calculateDelay(int $batchIndex, int $batchSize, ?int $emailsPerMinute): int
    {
        if (!$emailsPerMinute || $emailsPerMinute <= 0) {
            return 0;
        }

        $secondsPerBatch = ($batchSize / $emailsPerMinute) * 60;
        return (int) ($batchIndex * $secondsPerBatch);
    }
}
