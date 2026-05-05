<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Email;
use App\Models\EmailLog;
use App\Services\SESService;
use App\Services\UsageTrackingService;
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
    public int $timeout = 300;

    public function __construct(
        public int $campaignId,
        public array $emailIds
    ) {
        $this->tries = config('email.retry_attempts', 3);
    }

    public function handle(MailService $mailService, UsageTrackingService $tracker): void
    {
        $campaign = Campaign::with(['template', 'sender'])->find($this->campaignId);

        if (!$campaign || in_array($campaign->status, ['paused', 'cancelled'])) {
            return;
        }

        $template = $campaign->template;
        $sender = $campaign->sender;
        $emails = Email::whereIn('id', $this->emailIds)->subscribed()->get();

        // Ensure Sender is verified
        if (!$sender || $sender->status !== 'verified') {
            Log::error("Campaign {$campaign->id} aborted: Sender {$sender->email} is not verified.");
            $campaign->update(['status' => 'cancelled']);
            return;
        }

        $sentCount = 0;
        $failedCount = 0;
        
        $emailsPerMinute = $campaign->emails_per_minute ?: config('email.sending_rate_per_minute', 60);
        $delayBetweenEmails = 60.0 / $emailsPerMinute;

        foreach ($emails as $email) {
            // Check if campaign is still active dynamically
            $campaign->refresh();
            if (in_array($campaign->status, ['paused', 'cancelled'])) {
                return;
            }

            $log = EmailLog::where('campaign_id', $campaign->id)
                ->where('email_id', $email->id)
                ->first();

            if (!$log || $log->status === 'sent') {
                continue;
            }

            // Final safety check: Has the user unsubscribed since the campaign started?
            $isUnsubscribed = $email->subscription_status !== 'subscribed' || 
                             \App\Models\Unsubscribe::where('email', $email->email)->exists();

            if ($isUnsubscribed) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => 'Skipped: User Unsubscribed'
                ]);
                $failedCount++;
                continue;
            }

            try {
                // Personalize payload
                $recipientData = [
                    'name'  => $email->name,
                    'email' => $email->email,
                    'meta'  => $email->meta ?? [],
                ];
                $personalizedHtml = $mailService->replaceVariables($template->html_content, $recipientData);
                $personalizedSubject = $mailService->replaceVariables($template->subject, $recipientData, false);

                // ── CRM: Inject Tracking Engine ──
                
                // 1. Open Tracking (Invisible Pixel)
                $pixelUrl = route('track.open', ['logId' => $log->id]);
                $personalizedHtml .= "<img src='{$pixelUrl}' width='1' height='1' style='display:none' alt='' />";

                // 2. Link Tracking (Rewrite <a> tags)
                $personalizedHtml = preg_replace_callback('/<a\b[^>]*href=["\'](https?:\/\/[^"\']+)["\'][^>]*>/i', function($matches) use ($log) {
                    $originalUrl = $matches[1];
                    // Skip unsubscribe links if they are already processed or sensitive
                    if (str_contains($originalUrl, 'unsubscribe')) return $matches[0];
                    
                    $trackingUrl = route('track.click', ['logId' => $log->id, 'u' => $originalUrl]);
                    return str_replace($originalUrl, $trackingUrl, $matches[0]);
                }, $personalizedHtml);

                // Send Email via MailService (SES or SMTP)
                $messageId = $mailService->send(
                    sender: $sender,
                    to: $email->email,
                    subject: $personalizedSubject,
                    html: $personalizedHtml,
                    emailRecord: $email,
                    logId: $log->id
                );

                // Log Success
                $log->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                    'error_message' => "MessageId: " . $messageId, // Store Message ID for tracing
                ]);

                $campaign->increment('sent_count');
                $tracker->incrementSent();

            } catch (AwsException $e) {
                // Specific AWS SES Exceptions
                $errorMsg = $e->getAwsErrorMessage() ?: $e->getMessage();
                $errorCode = $e->getAwsErrorCode();
                
                Log::warning("AWS SES Error ($errorCode) for {$email->email}: {$errorMsg}");

                $log->update([
                    'status'        => 'failed',
                    'error_message' => substr("[$errorCode] $errorMsg", 0, 500),
                ]);

                $campaign->increment('failed_count');
                $tracker->incrementFailed();

            } catch (Exception $e) {
                // General Exceptions
                Log::warning("General Error sending to {$email->email}: {$e->getMessage()}");

                $log->update([
                    'status'        => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);

                $campaign->increment('failed_count');
                $tracker->incrementFailed();
            }

            // Rate limiting delay
            if ($delayBetweenEmails > 0) {
                usleep((int) ($delayBetweenEmails * 1000000));
            }
        }

        // Update campaign counters
        $campaign->increment('sent_count', $sentCount);
        $campaign->increment('failed_count', $failedCount);

        // Check if campaign is complete
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
