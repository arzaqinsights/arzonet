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
        // Get valid emails excluding unsubscribed (Global Check)
        $unsubscribedEmails = Unsubscribe::pluck('email')->toArray();
        $suppressedEmails = \App\Models\EmailStatus::whereIn('status', ['bounced', 'complaint'])->pluck('email')->toArray();
        $allExclusions = array_merge($unsubscribedEmails, $suppressedEmails);

        $validEmails = $campaign->emailList
            ->emails()
            ->valid()
            ->subscribed() // Respect individual subscription status
            ->whereNotIn('email', $allExclusions)
            ->get();

        // Update total recipients
        $campaign->update([
            'total_recipients' => $validEmails->count(),
            'status'           => 'sending',
            'started_at'       => now(),
        ]);

        // Create pending log entries with tracking tokens
        $logEntries = $validEmails->map(fn(Email $email) => [
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

        // Dispatch batch jobs
        $batchSize = $campaign->batch_size ?: 25;
        $emailIds = $validEmails->pluck('id')->toArray();
        $chunks = array_chunk($emailIds, $batchSize);

        foreach ($chunks as $index => $chunk) {
            // High-speed dispatch: reduce artificial delays
            $delay = $this->calculateDelay($index, $batchSize, $campaign->emails_per_minute);

            SendEmailBatchJob::dispatch($campaign->id, $chunk)
                ->onQueue('bulk')
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
            ->where('status', 'pending')
            ->pluck('email_id')
            ->toArray();

        if (empty($pendingLogIds)) {
            $campaign->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
            return;
        }

        $batchSize = $campaign->batch_size ?: config('emailplatform.batch_size', 50);
        $chunks = array_chunk($pendingLogIds, $batchSize);

        foreach ($chunks as $index => $chunk) {
            $delay = $this->calculateDelay($index, $batchSize, $campaign->emails_per_minute);

            SendEmailBatchJob::dispatch($campaign->id, $chunk)
                ->onQueue('bulk')
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
        return [
            'total'      => $campaign->total_recipients,
            'sent'       => $campaign->logs()->where('status', 'sent')->count(),
            'failed'     => $campaign->logs()->whereIn('status', ['failed', 'bounced'])->count(),
            'pending'    => $campaign->logs()->where('status', 'pending')->count(),
            'bounced'    => $campaign->logs()->where('status', 'bounced')->count(),
            'opened'     => $campaign->logs()->where('open_count', '>', 0)->count(),
            'clicked'    => $campaign->logs()->where('click_count', '>', 0)->count(),
            'success_rate' => $campaign->successRate(),
            'progress'     => $campaign->progress(),
        ];
    }

    public function retryFailed(Campaign $campaign): void
    {
        $campaign->update(['status' => 'sending']);

        $failedEmailIds = $campaign->logs()
            ->whereIn('status', ['failed', 'bounced'])
            ->pluck('email_id')
            ->toArray();

        if (empty($failedEmailIds)) return;

        // Reset logs to pending
        $campaign->logs()
            ->whereIn('status', ['failed', 'bounced'])
            ->update([
                'status'        => 'pending',
                'error_message' => null,
            ]);

        $batchSize = $campaign->batch_size ?: config('emailplatform.batch_size', 50);
        $chunks = array_chunk($failedEmailIds, $batchSize);

        foreach ($chunks as $index => $chunk) {
            $delay = $this->calculateDelay($index, $batchSize, $campaign->emails_per_minute);

            SendEmailBatchJob::dispatch($campaign->id, $chunk)
                ->onQueue('bulk')
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
