<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Email;
use App\Models\EmailLog;
use App\Models\Unsubscribe;
use App\Jobs\SendEmailBatchJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class CampaignService
{
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

        $providerType = $campaign->sender?->type ?? 'ses';
        $batchSize = match($providerType) {
            'smtp' => 5,
            'sendgrid' => 50,
            'ses' => 200,
            default => 25
        };
        
        $queueName = "bulk-{$providerType}";
        $jobCount = 0;

        // Use cursor-based chunking to dispatch in bounded memory
        $campaign->logs()
            ->whereNotIn('status', ['sent', 'delivered', 'bounced', 'complaint', 'spamreport', 'dropped'])
            ->chunkById(1000, function ($logs) use ($campaign, $batchSize, $queueName, &$jobCount, $providerType) {
                $emailIds = $logs->pluck('email_id')->filter()->toArray();
                if (empty($emailIds)) {
                    return;
                }
                foreach (array_chunk($emailIds, $batchSize) as $chunk) {
                    $delay = ($providerType === 'smtp') 
                        ? $this->calculateDelay($jobCount, $batchSize, $campaign->emails_per_minute)
                        : 0;

                    SendEmailBatchJob::dispatch($campaign->id, $chunk)
                        ->onQueue($queueName)
                        ->delay(now()->addSeconds($delay));

                    $jobCount++;
                }
            });

        if ($jobCount === 0) {
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

        Redis::set("campaign_{$campaign->id}_jobs_count", $jobCount);
        Redis::expire("campaign_{$campaign->id}_jobs_count", 86400);
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
    protected function calculateDelay(int $batchIndex, int $batchSize, ?int $emailsPerMinute): int
    {
        if (!$emailsPerMinute || $emailsPerMinute <= 0) return 0;

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
        $blockedCount = ($logStats['blocked'] ?? 0);
        $droppedCount = ($logStats['dropped'] ?? 0) + $blockedCount;
        $spamCount = ($logStats['spamreport'] ?? 0) + ($logStats['complaint'] ?? 0);

        return [
            'total'      => $campaign->total_recipients,
            'sent'       => $sentCount,
            'failed'     => $failedCount,
            'pending'    => $pendingCount,
            'bounced'    => $bounceCount,
            'dropped'    => $droppedCount,
            'blocked'    => $blockedCount,
            'spam'       => $spamCount,
            'opens'        => $campaign->logs()->sum('open_count'),
            'unique_opens' => $campaign->logs()->where(function($q) {
                $q->where('open_count', '>', 0)->orWhere('click_count', '>', 0);
            })->count(),
            'clicks'       => $campaign->logs()->sum('click_count'),
            'unique_clicks'=> $campaign->logs()->where('click_count', '>', 0)->count(),
            'unsubscribed' => $campaign->unsubscribes()->count(),
            'success_rate' => $campaign->successRate(),
            'progress'     => $campaign->progress(),
        ];
    }

    /**
     * Audit and retry failed emails.
     */
    public function retryFailed(Campaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        if (!$campaign->emailList) {
            throw new \Exception("Campaign retry failed: The linked Email List has been deleted.");
        }

        // 1. Audit and Recreate MISSING logs
        $campaign->emailList->emails()
            ->valid()
            ->subscribed()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->leftJoin('email_logs', function ($join) use ($campaign) {
                $join->on('emails.id', '=', 'email_logs.email_id')
                     ->where('email_logs.campaign_id', '=', $campaign->id);
            })
            ->whereNull('email_logs.id')
            ->select('emails.id', 'emails.email', 'emails.user_id')
            ->chunkById(1000, function ($emails) use ($campaign) {
                $newLogs = $emails->map(fn($email) => [
                    'user_id'        => $campaign->user_id,
                    'campaign_id'    => $campaign->id,
                    'email_id'       => $email->id,
                    'email_address'  => $email->email,
                    'tracking_token' => Str::random(32),
                    'status'         => 'pending',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ])->toArray();

                DB::table('email_logs')->insert($newLogs);
            }, 'emails.id');

        // 2. Reset status to pending for the ones we are about to retry
        $campaign->logs()
            ->whereIn('status', ['pending', 'failed'])
            ->whereNotNull('email_id')
            ->update([
                'status' => 'pending',
                'error_message' => null
            ]);

        // Recalculate and update the campaign's sent_count and failed_count
        $actualSentCount = $campaign->logs()->whereIn('status', ['sent', 'delivered'])->count();
        $actualFailedCount = 0; // they are all reset to pending now

        $campaign->update([
            'sent_count' => $actualSentCount,
            'failed_count' => $actualFailedCount
        ]);
        
        $sender = $campaign->sender;
        $providerType = $sender ? strtolower($sender->type) : 'ses';
        
        $batchSize = match($providerType) {
            'smtp' => 5,
            'sendgrid' => 50,
            'ses' => 200,
            default => 25
        };

        $queueName = "bulk-{$providerType}";
        $jobCount = 0;

        // 3. Chunk and dispatch the retry jobs
        $campaign->logs()
            ->where('status', 'pending')
            ->whereNotNull('email_id')
            ->chunkById(1000, function ($logs) use ($campaign, $batchSize, $queueName, &$jobCount, $providerType) {
                $emailIds = $logs->pluck('email_id')->toArray();
                foreach (array_chunk($emailIds, $batchSize) as $chunk) {
                    $delay = ($providerType === 'smtp')
                        ? $this->calculateDelay($jobCount, $batchSize, $campaign->emails_per_minute)
                        : 0;

                    SendEmailBatchJob::dispatch($campaign->id, $chunk)
                        ->onQueue($queueName)
                        ->delay(now()->addSeconds($delay));

                    $jobCount++;
                }
            });

        if ($jobCount === 0) {
            $campaign->refresh();
            $processedCount = $campaign->logs()
                ->whereIn('status', ['sent', 'delivered', 'failed', 'bounced', 'complaint', 'spamreport', 'dropped'])
                ->count();
            if ($processedCount >= $campaign->total_recipients) {
                $campaign->update(['status' => 'completed', 'completed_at' => now()]);
            }
            return;
        }

        // Track jobs in Redis
        Redis::set("campaign_{$campaign->id}_jobs_count", $jobCount);
        Redis::expire("campaign_{$campaign->id}_jobs_count", 86400);
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
