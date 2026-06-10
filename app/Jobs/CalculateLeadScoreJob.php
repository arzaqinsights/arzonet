<?php

namespace App\Jobs;

use App\Models\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateLeadScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $emailId)
    {
    }

    public function handle(): void
    {
        $contact = Email::withoutGlobalScopes()->find($this->emailId);
        if (!$contact) return;

        // ──────────────────────────────────────────────────
        // 1. EMAIL LEAD SCORE (1-10 scale)
        // ──────────────────────────────────────────────────
        $emailScore = 1;

        // Opens count
        $opens = $contact->activities()->where('type', 'opened')->count();
        if ($opens >= 6) {
            $emailScore += 3;
        } elseif ($opens >= 3) {
            $emailScore += 2;
        } elseif ($opens >= 1) {
            $emailScore += 1;
        }

        // Clicks count
        $clicks = $contact->activities()->where('type', 'clicked')->count();
        if ($clicks >= 3) {
            $emailScore += 4;
        } elseif ($clicks >= 1) {
            $emailScore += 2;
        }

        // Subscription status (Double opt-in verification bonus)
        if ($contact->subscription_status === 'subscribed') {
            $emailScore += 1;
        }

        // Active deals
        if ($contact->deals()->where('status', 'open')->exists()) {
            $emailScore += 2;
        }

        // Recency
        if ($contact->last_engaged_at) {
            $lastEng = \Carbon\Carbon::parse($contact->last_engaged_at);
            $diffDays = $lastEng->diffInDays(now());
            if ($diffDays <= 7) {
                $emailScore += 2;
            } elseif ($diffDays <= 30) {
                $emailScore += 1;
            }
        }

        // Penalties
        if ($contact->email_status === 'risky') {
            $emailScore -= 2;
        }
        if ($contact->is_role_based || $contact->is_disposable) {
            $emailScore -= 2;
        }
        if (in_array($contact->email_status, ['hard_bounce', 'soft_bounce', 'bounced', 'complaint'])) {
            $emailScore -= 5;
        }

        $emailScore = max(1, min(10, $emailScore));

        // ──────────────────────────────────────────────────
        // 2. WHATSAPP LEAD SCORE (1-10 scale)
        // ──────────────────────────────────────────────────
        $waScore = 1;

        // Opt-in / Subscribed status
        if ($contact->whatsapp_subscription_status === 'subscribed' || $contact->whatsapp_opt_in) {
            $waScore += 1;
        }

        // Inbound message replies
        $inbound = \App\Models\WhatsAppMessage::where('contact_id', $contact->id)
            ->where('direction', 'inbound')
            ->count();
        if ($inbound >= 5) {
            $waScore += 6;
        } elseif ($inbound >= 2) {
            $waScore += 4;
        } elseif ($inbound >= 1) {
            $waScore += 2;
        }

        // Outbound messages sent/delivered
        $outbound = \App\Models\WhatsAppMessage::where('contact_id', $contact->id)
            ->where('direction', 'outbound')
            ->count();
        if ($outbound >= 6) {
            $waScore += 2;
        } elseif ($outbound >= 1) {
            $waScore += 1;
        }

        // Recency
        if ($contact->whatsapp_last_message_at) {
            $lastWa = \Carbon\Carbon::parse($contact->whatsapp_last_message_at);
            $diffDays = $lastWa->diffInDays(now());
            if ($diffDays <= 7) {
                $waScore += 2;
            } elseif ($diffDays <= 30) {
                $waScore += 1;
            }
        }

        // Penalties
        if ($contact->whatsapp_subscription_status === 'unsubscribed') {
            $waScore -= 5;
        }

        $waScore = max(1, min(10, $waScore));

        // ──────────────────────────────────────────────────
        // 3. OVERALL ENGAGEMENT SCORE (0-100 scale)
        // ──────────────────────────────────────────────────
        $overallScore = (int)(($emailScore + $waScore) * 5);

        // Update contact metrics
        $contact->update([
            'email_lead_score' => $emailScore,
            'whatsapp_lead_score' => $waScore,
            'engagement_score' => $overallScore,
        ]);
    }
}
