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
        $campaign = Campaign::findOrFail($this->campaignId);

        // Check if the campaign is in a state that allows sending
        if ($campaign->status === 'cancelled' || $campaign->status === 'completed') {
            return;
        }

        $emailList = $campaign->emailList;
        if (!$emailList) {
            $campaign->update(['status' => 'failed']);
            return;
        }

        $query = $emailList->emails()
            ->valid()
            ->subscribed()
            ->whereNotNull('email')
            ->where('email', '!=', '');

        // Apply Advanced Audience Config (Segments/Tags/Health)
        if ($campaign->audience_config) {
            $config = $campaign->audience_config;
            
            // Exclude unhealthy emails
            if (isset($config['exclude_unhealthy']) && $config['exclude_unhealthy']) {
                $query->where(function($q) {
                    $q->whereNotIn('email_status', ['hard_bounce', 'complaint', 'invalid', 'blocked'])
                      ->orWhereNull('email_status');
                });
                $query->where('email_score', '>', 1);
            }

            // Optional health filters
            if (isset($config['exclude_risky']) && $config['exclude_risky']) {
                $query->where('email_status', '!=', 'risky')
                      ->orWhereNull('email_status');
                $query->where('email_score', '>', 2);
            }
            if (isset($config['exclude_disposable']) && $config['exclude_disposable']) {
                $query->where('is_disposable', false);
            }
            if (isset($config['exclude_role_based']) && $config['exclude_role_based']) {
                $query->where('is_role_based', false);
            }

            // Segments and Tags
            if (isset($config['type']) && $config['type'] === 'segment' && !empty($config['tag'])) {
                [$type, $value] = explode(':', $config['tag'], 2);
                if ($type === 'tag') {
                    $query->where('tags', 'LIKE', "%\"{$value}\"%")
                          ->orWhere('tags', 'LIKE', "%{$value}%");
                } elseif ($type === 'segment') {
                    $query->where(function($q) use ($value) {
                        $q->where('segment_name', $value)
                          ->orWhereJsonContains('auto_segments', $value);
                    });
                }
            }
        }

        $totalRecipients = $query->count();
        if ($totalRecipients === 0) {
            $campaign->update([
                'total_recipients' => 0,
                'status'           => 'completed',
                'completed_at'     => now(),
            ]);
            return;
        }

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

        // Use chunkById on emails to process without heavy memory overhead
        $query->chunkById(1000, function ($emails) use ($campaign, $batchSize, $queueName, &$jobCount) {
            $logEntries = [];
            $emailIds = [];

            foreach ($emails as $email) {
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
            }

            // Raw Bulk Insert for speed and lower memory
            DB::table('email_logs')->insert($logEntries);

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
        });

        // Set Redis counter for monitoring campaign progress/completion
        Redis::set("campaign_{$campaign->id}_jobs_count", $jobCount);
        Redis::expire("campaign_{$campaign->id}_jobs_count", 86400); // 24-hour TTL
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
