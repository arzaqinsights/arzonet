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
        $emails = Email::whereIn('id', $this->emailIds)->subscribed()->get();

        // ── 2. Determine Sending Rate ──
        $safeRate = $quotaManager->getSafeRate();
        $campaignRate = (float) ($campaign->emails_per_minute / 60.0);
        $effectiveRate = min($safeRate, $campaignRate);

        $senders = null;
        $specificSender = $campaign->sender;
        
        if ($specificSender) {
            if ($specificSender->status !== 'verified') {
                $campaign->update(['status' => 'cancelled', 'error_message' => "Sender {$specificSender->email} not verified."]);
                return;
            }
            $senders = collect([$specificSender]);
        } else {
            $senders = \App\Models\Sender::where('status', 'verified')->get();
            if ($senders->isEmpty()) {
                $campaign->update(['status' => 'cancelled', 'error_message' => "No verified senders available."]);
                return;
            }
        }

        $senderIndex = 0;
        $senderCount = $senders->count();

        foreach ($emails as $email) {
            $campaign->refresh();
            if (in_array($campaign->status, ['paused', 'cancelled'])) return;

            $log = EmailLog::where('campaign_id', $campaign->id)
                ->where('email_id', $email->id)
                ->first();

            if (!$log || $log->status === 'sent') continue;

            // ── 3. Distributed Throttling ──
            $throttled = !$quotaManager->throttle('ses_global_limit', $effectiveRate);
            
            if ($throttled) {
                // If throttled, release back to queue with delay
                $this->release(1); // Try again in 1 second
                return;
            }

            try {
                $recipientData = [
                    'name'  => $email->name,
                    'email' => $email->email,
                    'meta'  => $email->meta ?? [],
                ];

                $html = $mailService->replaceVariables($template->html_content, $recipientData);
                $subject = $mailService->replaceVariables($template->subject, $recipientData, false);
                $trackedHtml = $analyticsTracker->injectTracking($html, $log);

                $currentSender = $senders[$senderIndex % $senderCount];
                $senderIndex++;

                $startTime = microtime(true);
                $messageId = $mailService->send(
                    sender: $currentSender,
                    to: $email->email,
                    subject: $subject,
                    html: $trackedHtml,
                    emailRecord: $email,
                    logId: $log->id
                );

                $log->update([
                    'status'     => 'sent',
                    'sent_at'    => now(),
                    'message_id' => $messageId,
                ]);

                $campaign->increment('sent_count');
                $usageTracker->incrementSent();

                // Track live sending speed
                $this->trackSendingSpeed($campaign, microtime(true) - $startTime);

            } catch (AwsException $e) {
                if ($e->getAwsErrorCode() === 'Throttling') {
                    $this->release(2); // AWS Throttling, back off
                    return;
                }
                $this->handleFailure($log, $campaign, $usageTracker, $e->getAwsErrorMessage() ?: $e->getMessage());
            } catch (Exception $e) {
                $this->handleFailure($log, $campaign, $usageTracker, $e->getMessage());
            }
        }

        $this->checkCompletion($campaign);
    }

    protected function shouldAutoPause(Campaign $campaign): bool
    {
        // Only check if we've sent at least 50 emails to avoid early false positives
        if ($campaign->sent_count < 50) return false;

        $bounceRate = $campaign->bounceRate();
        $complaintRate = $campaign->complaintRate();

        return ($bounceRate > 3.0) || ($complaintRate > 0.1);
    }

    protected function trackSendingSpeed(Campaign $campaign, float $duration)
    {
        $key = "campaign_{$campaign->id}_speed";
        Redis::lpush($key, microtime(true));
        Redis::ltrim($key, 0, 9); // Keep last 10 sends
        Redis::expire($key, 60);
    }

    protected function handleFailure(EmailLog $log, Campaign $campaign, UsageTrackingService $usageTracker, string $message)
    {
        Log::warning("Error sending email to {$log->email_address}: $message");
        
        $log->update([
            'status' => 'failed',
            'error_message' => substr($message, 0, 500),
        ]);

        $campaign->increment('failed_count');
        $usageTracker->incrementFailed();
    }

    protected function checkCompletion(Campaign $campaign)
    {
        $campaign->refresh();
        $totalProcessed = $campaign->sent_count + $campaign->failed_count;
        if ($totalProcessed >= $campaign->total_recipients) {
            $campaign->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
