<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Email;
use App\Models\EmailLog;
use App\Models\Unsubscribe;
use App\Jobs\SendEmailBatchJob;
use Illuminate\Support\Str;

class CampaignService
{
    /**
     * Prepare and dispatch a campaign for sending.
     */
    public function dispatch(Campaign $campaign): void
    {
        // Check Limits
        $usage = $campaign->user->getEmailsUsage();
        if ($usage->is_exceeded) {
            throw new \Exception("Campaign dispatch blocked: Email sending limit exceeded. Please upgrade your plan.");
        }

        $query = $campaign->emailList->emails()
            ->valid()
            ->subscribed();

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
                    $query->where('segment_name', $value);
                }
            }
        }

        $validEmails = $query->get();

        // Update total recipients
        $campaign->update([
            'total_recipients' => $validEmails->count(),
            'status'           => 'sending',
            'started_at'       => now(),
        ]);

        // Create pending log entries with tracking tokens
        $logEntries = $validEmails->map(fn(Email $email) => [
            'user_id'        => $campaign->user_id,
            'campaign_id'    => $campaign->id,
            'email_id'       => $email->id,
            'email_address'  => $email->email,
            'tracking_token' => Str::random(32),
            'status'         => 'pending',
            'created_at'     => now(),
            'updated_at'     => now(),
        ])->toArray();

        // Bulk insert log entries in chunks
        foreach (array_chunk($logEntries, 500) as $chunk) {
            EmailLog::insert($chunk);
        }

        // ── PROVIDER-AWARE DISPATCH ENGINE ──
        $providerType = $campaign->sender?->type ?? 'ses';
        $batchSize = match($providerType) {
            'smtp' => 5,
            'sendgrid' => 50,
            'ses' => 200, // Hyperscale support
            default => 25
        };
        
        $queueName = "bulk-{$providerType}";
        $emailIds = $validEmails->pluck('id')->toArray();
        $chunks = array_chunk($emailIds, $batchSize);
        $totalJobs = count($chunks);

        // Track how many jobs are dispatched in Redis
        \Illuminate\Support\Facades\Redis::set("campaign_{$campaign->id}_jobs_count", $totalJobs);
        \Illuminate\Support\Facades\Redis::expire("campaign_{$campaign->id}_jobs_count", 86400); // 24 hours safety

        foreach ($chunks as $index => $chunk) {
            // Adaptive delay for SMTP, zero delay for API providers
            $delay = ($providerType === 'smtp') 
                ? $this->calculateDelay($index, $batchSize, $campaign->emails_per_minute)
                : 0;

            SendEmailBatchJob::dispatch($campaign->id, $chunk)
                ->onQueue($queueName)
                ->delay(now()->addSeconds($delay));
        }
    }

    /**
     * Pause a running campaign.
     */
    public function pause(Campaign $campaign): void
    {
        $campaign->update(['status' => 'paused']);
    }

    /**
     * Resume a paused campaign by re-dispatching pending emails.
     */
    public function resume(Campaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        $pendingLogIds = $campaign->logs()
            ->whereNotIn('status', ['sent', 'delivered', 'bounced', 'complaint', 'spamreport', 'dropped'])
            ->pluck('email_id')
            ->toArray();

        if (empty($pendingLogIds)) {
            if ($campaign->logs()->count() > 0) {
                $campaign->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
            } else {
                $campaign->update(['status' => 'paused']);
            }
            return;
        }

        $sender = $campaign->sender;
        $providerType = $sender ? strtolower($sender->type) : 'ses';
        
        $batchSize = match($providerType) {
            'smtp' => 5,
            'sendgrid' => 50,
            'ses' => 200,
            default => 25
        };
        
        $queueName = "bulk-{$providerType}";
        $chunks = array_chunk($pendingLogIds, $batchSize);

        foreach ($chunks as $index => $chunk) {
            $delay = ($providerType === 'smtp') 
                ? $this->calculateDelay($index, $batchSize, $campaign->emails_per_minute)
                : 0;

            SendEmailBatchJob::dispatch($campaign->id, $chunk)
                ->onQueue($queueName)
                ->delay(now()->addSeconds($delay));
        }
    }

    /**
     * Cancel a campaign.
     */
    public function cancel(Campaign $campaign): void
    {
        $campaign->update(['status' => 'cancelled']);

        // Mark remaining pending logs as failed
        $campaign->logs()
            ->where('status', 'pending')
            ->update([
                'status'        => 'failed',
                'error_message' => 'Campaign cancelled',
            ]);
    }

    /**
     * Calculate delay for a batch based on rate limiting.
     */
    protected function calculateDelay(int $batchIndex, int $batchSize, int $emailsPerMinute): int
    {
        if ($emailsPerMinute <= 0) return 0;

        $secondsPerBatch = ($batchSize / $emailsPerMinute) * 60;
        return (int) ($batchIndex * $secondsPerBatch);
    }

    /**
     * Get campaign statistics.
     */
    public function getStats(Campaign $campaign): array
    {
        $logStats = $campaign->logs()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $sentCount = ($logStats['sent'] ?? 0) + ($logStats['delivered'] ?? 0);
        $failedCount = ($logStats['failed'] ?? 0);
        $bounceCount = ($logStats['bounced'] ?? 0);
        $pendingCount = ($logStats['pending'] ?? 0);

        return [
            'total'      => $campaign->total_recipients,
            'sent'       => $sentCount,
            'failed'     => $failedCount,
            'pending'    => $pendingCount,
            'bounced'    => $bounceCount,
            'opens'        => $campaign->logs()->sum('open_count'),
            'unique_opens' => $campaign->logs()->where('open_count', '>', 0)->count(),
            'clicks'       => $campaign->logs()->sum('click_count'),
            'unique_clicks'=> $campaign->logs()->where('click_count', '>', 0)->count(),
            'unsubscribed' => $campaign->unsubscribes()->count(),
            'success_rate' => $campaign->successRate(),
            'progress'     => $campaign->progress(),
        ];
    }

    public function retryFailed(Campaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        $failedEmailIds = $campaign->logs()
            ->whereNotIn('status', ['sent', 'delivered'])
            ->whereNotNull('email_id')
            ->pluck('email_id')
            ->toArray();

        \Illuminate\Support\Facades\Log::info("Retry Failed triggered for Campaign {$campaign->id}. Emails found: " . count($failedEmailIds));

        if (empty($failedEmailIds)) {
            \Illuminate\Support\Facades\Log::warning("Retry Failed: No failed/bounced/pending emails found for Campaign {$campaign->id}. Current statuses in DB: " . json_encode($campaign->logs()->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('status')->pluck('count', 'status')));
            return;
        }

        // Reset logs to pending
        $campaign->logs()
            ->whereNotIn('status', ['sent', 'delivered'])
            ->update([
                'status'        => 'pending',
                'error_message' => null,
            ]);
        
        $sender = $campaign->sender;
        $providerType = $sender ? strtolower($sender->type) : 'ses';
        
        $batchSize = match($providerType) {
            'smtp' => 5,
            'sendgrid' => 50,
            'ses' => 200,
            default => 25
        };

        $chunks = array_chunk($failedEmailIds, $batchSize);
        $totalJobs = count($chunks);

        // Track jobs in Redis
        \Illuminate\Support\Facades\Redis::set("campaign_{$campaign->id}_jobs_count", $totalJobs);
        \Illuminate\Support\Facades\Redis::expire("campaign_{$campaign->id}_jobs_count", 86400);

        $queueName = "bulk-{$providerType}";

        foreach ($chunks as $index => $chunk) {
            $delay = $this->calculateDelay($index, $batchSize, $campaign->emails_per_minute);

            SendEmailBatchJob::dispatch($campaign->id, $chunk)
                ->onQueue($queueName)
                ->delay(now()->addSeconds($delay));
        }
    }

    public function sendTestEmail(Campaign $campaign, string $testEmail): void
    {
        \App\Jobs\SendTestEmailJob::dispatch(
            $testEmail,
            $campaign->template_id,
            $campaign->sender_id
        )->onQueue('high');
    }
}
