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

    public function handle(MailService $mailService, UsageTrackingService $usageTracker, TrackingService $analyticsTracker): void
    {
        $campaign = Campaign::with(['template', 'sender'])->find($this->campaignId);

        if (!$campaign || in_array($campaign->status, ['paused', 'cancelled'])) {
            return;
        }

        $template = $campaign->template;
        $emails = Email::whereIn('id', $this->emailIds)->subscribed()->get();

        // Round-robin sender setup
        $senders = null;
        $specificSender = $campaign->sender;
        
        if ($specificSender) {
            if ($specificSender->status !== 'verified') {
                Log::error("Campaign {$campaign->id} aborted: Sender {$specificSender->email} is not verified.");
                $campaign->update(['status' => 'cancelled']);
                return;
            }
            $senders = collect([$specificSender]);
        } else {
            $senders = \App\Models\Sender::where('status', 'verified')->get();
            if ($senders->isEmpty()) {
                Log::error("Campaign {$campaign->id} aborted: No verified senders available for round-robin.");
                $campaign->update(['status' => 'cancelled']);
                return;
            }
        }

        $emailsPerMinute = $campaign->emails_per_minute ?: config('email.sending_rate_per_minute', 60);
        $delayBetweenEmails = 60.0 / $emailsPerMinute;
        $senderIndex = 0;
        $senderCount = $senders->count();


        foreach ($emails as $email) {
            $campaign->refresh();
            if (in_array($campaign->status, ['paused', 'cancelled'])) return;

            $log = EmailLog::where('campaign_id', $campaign->id)
                ->where('email_id', $email->id)
                ->first();

            if (!$log || $log->status === 'sent') continue;

            try {
                $recipientData = [
                    'name'  => $email->name,
                    'email' => $email->email,
                    'meta'  => $email->meta ?? [],
                ];

                // 1. Personalize Content
                $html = $mailService->replaceVariables($template->html_content, $recipientData);
                $subject = $mailService->replaceVariables($template->subject, $recipientData, false);

                // 2. Inject Tracking (Pixel + Link Rewriting)
                $trackedHtml = $analyticsTracker->injectTracking($html, $log);

                // 3. Round-robin sender selection
                $currentSender = $senders[$senderIndex % $senderCount];
                $senderIndex++;

                // 4. Send Email
                $messageId = $mailService->send(
                    sender: $currentSender,
                    to: $email->email,
                    subject: $subject,
                    html: $trackedHtml,
                    emailRecord: $email,
                    logId: $log->id
                );

                // 5. Update Log with Success

                $log->update([
                    'status'     => 'sent',
                    'sent_at'    => now(),
                    'message_id' => $messageId,
                ]);

                $campaign->increment('sent_count');
                $usageTracker->incrementSent();

            } catch (AwsException $e) {
                $this->handleFailure($log, $campaign, $usageTracker, $e->getAwsErrorMessage() ?: $e->getMessage());
            } catch (Exception $e) {
                $this->handleFailure($log, $campaign, $usageTracker, $e->getMessage());
            }

            if ($delayBetweenEmails > 0) {
                usleep((int) ($delayBetweenEmails * 1000000));
            }
        }

        $this->checkCompletion($campaign);
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
