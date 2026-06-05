<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Email;
use App\Models\EmailLog;
use App\Services\SESService;
use App\Services\UsageTrackingService;
use App\Services\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; 
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Aws\Exception\AwsException;
use App\Services\MailService;
use Exception;

class SendEmailBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout = 120;

    // Retry with exponential backoff (e.g., 10s, 30s, 90s, 270s, etc.)
    public function backoff()
    {
        return [10, 30, 90, 270];
    }


    public function __construct(
        public int $campaignId,
        public array $emailIds
    ) {
        $this->tries = config('email.retry_attempts', 10); // Increased tries for smarter recovery
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Circuit Breaker: If 10 failures occur in 5 mins, pause for 5 mins
            (new \Illuminate\Queue\Middleware\ThrottlesExceptions(10, 5)),
            
            // Rate Limiter: Prevent sending too fast globally
            new \Illuminate\Queue\Middleware\RateLimited('sendgrid'),
        ];
    }

    public function handle(MailService $mailService, UsageTrackingService $usageTracker, TrackingService $analyticsTracker, \App\Services\QuotaManager $quotaManager): void
    {
        \Log::info("Job Started: CampaignID={$this->campaignId}, Emails=" . count($this->emailIds));

        $campaign = Campaign::with(['template', 'sender'])->find($this->campaignId);

        if (!$campaign || in_array($campaign->status, ['paused', 'cancelled', 'completed'])) {
            return;
        }

        // ── 1. Safety Check: Bounce/Complaint Rates ──
        if ($this->shouldAutoPause($campaign)) {
            $campaign->update([
                'status' => 'paused',
                'error_message' => 'Auto-paused: High bounce/complaint rate detected. Please check your list hygiene.'
            ]);
            Log::warning("Campaign {$campaign->id} auto-paused due to poor performance metrics.");
            return;
        }

        $template = $campaign->template;
        
        // ── 2. PRELOAD LOGS & EMAILS (Zero DB inside loop) ──
        $emails = Email::whereIn('id', $this->emailIds)
            ->subscribed()
            ->select('id', 'email', 'name', 'first_name', 'full_name', 'meta')
            ->get()
            ->keyBy('id');

        $logs = EmailLog::where('campaign_id', $campaign->id)
            ->whereIn('email_id', $this->emailIds)
            ->select('id', 'campaign_id', 'email_id', 'email_address', 'tracking_token', 'status', 'open_count', 'click_count')
            ->get()
            ->keyBy('email_id');

        $senders = $campaign->sender ? collect([$campaign->sender]) : \App\Models\Sender::where('status', 'verified')->get();
        if ($senders->isEmpty()) {
            $campaign->update(['status' => 'cancelled', 'error_message' => "No verified senders available."]);
            return;
        }

        $providerType = $senders->first()->type ?? 'ses';

        if ($providerType === 'ses') {
            $safeRate = $quotaManager->getSafeRate();
            $campaignRate = (float) ($campaign->emails_per_minute / 60.0);
            $effectiveRate = min($safeRate, $campaignRate);
        }

        $senderIndex = 0;
        $senderCount = $senders->count();
        $results = [
            'sent' => [],
            'failed' => [],
            'sent_count' => 0,
            'failed_count' => 0
        ];

        foreach ($this->emailIds as $emailId) {
            $email = $emails->get($emailId);
            $log = $logs->get($emailId);

            if (!$email || !$log) continue;
            
            // Skip if already processed successfully or permanently failed
            if (in_array($log->status, ['sent', 'delivered', 'failed', 'bounced', 'complaint', 'spamreport', 'dropped'])) {
                continue;
            }

            // ── 3. Distributed Throttling (SES ONLY) ──
            if ($providerType === 'ses' && !$quotaManager->throttle('ses_global_limit', $effectiveRate)) {
                $this->saveBatchResults($results, $campaign, $usageTracker);
                
                // Re-dispatch remaining emails instead of releasing the whole job
                $remainingIds = array_slice($this->emailIds, array_search($emailId, $this->emailIds));
                if (!empty($remainingIds)) {
                    self::dispatch($this->campaignId, $remainingIds)
                        ->delay(now()->addSeconds(2))
                        ->onQueue($this->queue);
                }
                return;
            }

            try {
                $recipientData = $email->toArray();
                
                // Priority: Model Name > full_name > first_name > Fallback
                $recipientData['name'] = $email->name 
                                        ?? $recipientData['full_name'] 
                                        ?? $recipientData['first_name'] 
                                        ?? 'Contact';

                \Log::info("Final Personalization Data for {$email->email}: Name=" . $recipientData['name']);

                $html = $mailService->replaceVariables($template->html_content, $recipientData);
                
                if ($senderIndex === 0) {
                    \Log::info("Sample Processed HTML Snippet: " . substr($html, 0, 200));
                }

                $subjectSource = $campaign->subject;
                $subject = $mailService->replaceVariables($subjectSource, $recipientData, false);
                $trackedHtml = $analyticsTracker->injectTracking($html, $log);

                $currentSender = $senders[$senderIndex % $senderCount];
                $senderIndex++;

                // ── CENTRALIZED GLOBAL RATE LIMITER (Non-Blocking) ──
                $limitKey = "sender_rate_limit:{$currentSender->id}";
                $maxPerMinute = $currentSender->emails_per_minute ?: ($providerType === 'sendgrid' ? 6000 : 60);
                
                if (!\Illuminate\Support\Facades\RateLimiter::attempt($limitKey, $maxPerMinute, function() {}, 60)) {
                    $this->saveBatchResults($results, $campaign, $usageTracker);

                    // Release remaining emails back to queue and stop this job
                    $remainingIds = array_slice($this->emailIds, array_search($emailId, $this->emailIds));
                    if (!empty($remainingIds)) {
                        self::dispatch($this->campaignId, $remainingIds)
                            ->delay(now()->addSeconds(10))
                            ->onQueue($this->queue);
                    }
                    return;
                }

                $messageId = $mailService->send(
                    sender: $currentSender,
                    to: $email->email,
                    subject: $subject,
                    html: $trackedHtml,
                    emailRecord: $email,
                    logId: $log->id
                );

                $results['sent'][] = [
                    'id' => $log->id,
                    'message_id' => $messageId,
                ];
                $results['sent_count']++;

                // Track speed via Redis (No DB write)
                $this->trackSendingSpeed($campaign);

            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() === 'Throttling') {
                    $this->release(2);
                    return;
                }
                $results['failed'][] = ['id' => $log->id, 'error' => $e->getAwsErrorMessage() ?: $e->getMessage()];
                $results['failed_count']++;
            } catch (Exception $e) {
                $results['failed'][] = ['id' => $log->id, 'error' => $e->getMessage()];
                $results['failed_count']++;
            }
        }

        $this->saveBatchResults($results, $campaign, $usageTracker);
        $this->checkCompletion($campaign);
    }

    /**
     * Save batch results to the database using bulk updates
     */
    protected function saveBatchResults(array $results, Campaign $campaign, UsageTrackingService $usageTracker)
    {
        if ($results['sent_count'] === 0 && $results['failed_count'] === 0) {
            return;
        }

        DB::transaction(function() use ($results, $campaign, $usageTracker) {
            if (!empty($results['sent'])) {
                $sentIds = array_column($results['sent'], 'id');
                $cases = [];
                $bindings = [];
                foreach ($results['sent'] as $s) {
                    $cases[] = "WHEN id = ? THEN ?";
                    $bindings[] = $s['id'];
                    $bindings[] = $s['message_id'];
                }
                $caseStr = implode(' ', $cases);
                $idPlaceholders = implode(',', array_fill(0, count($sentIds), '?'));
                DB::update(
                    "UPDATE email_logs SET status='sent', sent_at=NOW(), updated_at=NOW(), 
                     message_id = CASE {$caseStr} END 
                     WHERE id IN ({$idPlaceholders})",
                    array_merge($bindings, $sentIds)
                );
            }

            if (!empty($results['failed'])) {
                $failedIds = array_column($results['failed'], 'id');
                $cases = [];
                $bindings = [];
                foreach ($results['failed'] as $f) {
                    $cases[] = "WHEN id = ? THEN ?";
                    $bindings[] = $f['id'];
                    $bindings[] = substr($f['error'], 0, 255);
                }
                $caseStr = implode(' ', $cases);
                $idPlaceholders = implode(',', array_fill(0, count($failedIds), '?'));
                DB::update(
                    "UPDATE email_logs SET status='failed', updated_at=NOW(),
                     error_message = CASE {$caseStr} END
                     WHERE id IN ({$idPlaceholders})",
                    array_merge($bindings, $failedIds)
                );
            }

            if ($results['sent_count'] > 0) {
                $campaign->increment('sent_count', $results['sent_count']);
                $usageTracker->incrementSent($results['sent_count'], $campaign->user_id);
            }
            if ($results['failed_count'] > 0) {
                $campaign->increment('failed_count', $results['failed_count']);
                $usageTracker->incrementFailed($results['failed_count'], $campaign->user_id);
            }
        });
    }

    protected function shouldAutoPause(Campaign $campaign): bool
    {
        if ($campaign->sent_count < 50) return false;
        return ($campaign->bounceRate() > 15.0) || ($campaign->complaintRate() > 0.1);
    }

    protected function trackSendingSpeed(Campaign $campaign)
    {
        try {
            $key = "campaign_{$campaign->id}_speed";
            Redis::lpush($key, microtime(true));
            Redis::ltrim($key, 0, 19); 
            Redis::expire($key, 60);
        } catch (\Exception $e) {
            // Redis not available, ignore speed tracking
        }
    }

    protected function checkCompletion(Campaign $campaign)
    {
        $key = "campaign_{$campaign->id}_jobs_count";
        $remainingJobs = Redis::decr($key);

        if ($remainingJobs <= 0) {
            // Check if there are any remaining pending logs
            $pendingCount = DB::table('email_logs')
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->count();

            if ($pendingCount === 0) {
                $campaign->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
            } else {
                // Stalled or partially failed
                $campaign->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                    'error_message' => 'Campaign finished with pending/stalled emails.'
                ]);
            }
            Redis::del($key); // Cleanup
        }
    }
}
