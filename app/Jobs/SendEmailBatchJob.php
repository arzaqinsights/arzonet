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
    public int $timeout = 600;

    // Retry with exponential backoff (e.g., 10s, 30s, 90s, 270s, etc.)
    public function backoff()
    {
        return [10, 30, 90, 270];
    }


    public function __construct(
        public int $campaignId,
        public array $emailIds
    ) {
        $this->tries = config('email.retry_attempts', 3);
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
        $emails = Email::whereIn('id', $this->emailIds)->subscribed()->get()->keyBy('id');
        $logs = EmailLog::where('campaign_id', $campaign->id)
            ->whereIn('email_id', $this->emailIds)
            ->get()
            ->keyBy('email_id');

        $safeRate = $quotaManager->getSafeRate();
        $campaignRate = (float) ($campaign->emails_per_minute / 60.0);
        $effectiveRate = min($safeRate, $campaignRate);

        $senders = $campaign->sender ? collect([$campaign->sender]) : \App\Models\Sender::where('status', 'verified')->get();
        if ($senders->isEmpty()) {
            $campaign->update(['status' => 'cancelled', 'error_message' => "No verified senders available."]);
            return;
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

            if (!$email || !$log || $log->status === 'sent') continue;

            // ── 3. Distributed Throttling ──
            if (!$quotaManager->throttle('ses_global_limit', $effectiveRate)) {
                $this->saveBatchResults($results, $campaign, $usageTracker);
                
                // Re-dispatch remaining emails instead of releasing the whole job
                // because we've already processed and saved some emails in this chunk.
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
                // ── PRE-FLIGHT DOMAIN VALIDATION (SMTP Safety) ──
                $domain = substr(strrchr($email->email, "@"), 1);
                if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                    $log->update([
                        'status' => 'failed',
                        'error_message' => "Invalid Domain: {$domain} (DNS check failed)",
                        'sent_at' => now()
                    ]);
                    $campaign->increment('failed_count');
                    continue;
                }

                $subjectSource = $campaign->subject ?: $template->subject;
                $subject = $mailService->replaceVariables($subjectSource, $recipientData, false);
                $trackedHtml = $analyticsTracker->injectTracking($html, $log);

                $currentSender = $senders[$senderIndex % $senderCount];
                $senderIndex++;

                // ── CENTRALIZED GLOBAL RATE LIMITER (Non-Blocking) ──
                $limitKey = "sender_rate_limit:{$currentSender->id}";
                $maxPerMinute = $currentSender->emails_per_minute ?: 60;
                
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
     * Save batch results to the database
     */
    protected function saveBatchResults(array $results, Campaign $campaign, UsageTrackingService $usageTracker)
    {
        if ($results['sent_count'] === 0 && $results['failed_count'] === 0) {
            return;
        }

        DB::transaction(function() use ($results, $campaign, $usageTracker) {
            foreach ($results['sent'] as $sent) {
                EmailLog::where('id', $sent['id'])->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'message_id' => $sent['message_id']
                ]);
            }

            foreach ($results['failed'] as $failed) {
                EmailLog::where('id', $failed['id'])->update([
                    'status' => 'failed',
                    'error_message' => substr($failed['error'], 0, 255)
                ]);
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
        return ($campaign->bounceRate() > 3.0) || ($campaign->complaintRate() > 0.1);
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
        $campaign->refresh();
        if (($campaign->sent_count + $campaign->failed_count) >= $campaign->total_recipients) {
            $campaign->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
