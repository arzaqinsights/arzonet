<?php

namespace App\Jobs;

use App\Models\SequenceEnrollment;
use App\Models\SequenceStep;
use App\Models\EmailLog;
use App\Models\Sender;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSequenceEnrollmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(MailService $mailService): void
    {
        $now = now();
        $enrollments = SequenceEnrollment::where('status', 'active')
            ->where('scheduled_at', '<=', $now)
            ->get();

        foreach ($enrollments as $enrollment) {
            try {
                $contact = $enrollment->contact;
                $sequence = $enrollment->sequence;

                if (!$sequence || !$contact || $contact->is_archived) {
                    $enrollment->update(['status' => 'cancelled', 'scheduled_at' => null]);
                    continue;
                }

                // Sequence Unsubscribe / Bounce Termination Check
                $unsubscribed = $contact->subscription_status === 'unsubscribed';
                $bouncedOrComplaint = in_array($contact->email_status, ['hard_bounce', 'soft_bounce', 'bounce', 'complaint']);
                if ($unsubscribed || $bouncedOrComplaint) {
                    $enrollment->update(['status' => 'cancelled', 'scheduled_at' => null]);
                    continue;
                }

                $step = SequenceStep::where('sequence_id', $sequence->id)
                    ->where('step_number', $enrollment->current_step_number)
                    ->first();

                if (!$step) {
                    // Sequence completed
                    $enrollment->update(['status' => 'completed', 'scheduled_at' => null]);
                    continue;
                }

                $template = $step->template;
                $sender = Sender::where('user_id', $sequence->user_id)->first();

                if ($template && $sender) {
                    $recipientData = $contact->toArray();
                    $recipientData['name'] = $contact->name ?? 'Contact';

                    $html = $mailService->replaceVariables($template->html_content, $recipientData);
                    $subject = $mailService->replaceVariables($step->subject, $recipientData, false);

                    // Create email tracking log
                    $log = EmailLog::create([
                        'user_id' => $sequence->user_id,
                        'email_id' => $contact->id,
                        'email_address' => $contact->email,
                        'status' => 'pending',
                        'tracking_token' => \Illuminate\Support\Str::random(32),
                    ]);

                    try {
                        $messageId = $mailService->send($sender, $contact->email, $subject, $html, $contact, $log->id);
                        $log->update([
                            'status' => 'sent',
                            'message_id' => $messageId,
                            'sent_at' => now(),
                            'delivered_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        $log->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage()
                        ]);
                        Log::error("Sequence email delivery failed for enrollment {$enrollment->id}: " . $e->getMessage());
                    }
                }

                // Check for next step
                $nextStep = SequenceStep::where('sequence_id', $sequence->id)
                    ->where('step_number', $step->step_number + 1)
                    ->first();

                if ($nextStep) {
                    $enrollment->update([
                        'current_step_number' => $nextStep->step_number,
                        'scheduled_at' => now()->addDays($nextStep->delay_days),
                        'last_sent_at' => now(),
                    ]);
                } else {
                    $enrollment->update([
                        'status' => 'completed',
                        'scheduled_at' => null,
                        'last_sent_at' => now(),
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Failed to process sequence enrollment {$enrollment->id}: " . $e->getMessage());
            }
        }
    }
}
