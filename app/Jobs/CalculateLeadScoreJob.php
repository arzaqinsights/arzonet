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

        $score = 0;

        // 1. Email opens (+20)
        $opensCount = $contact->activities()->where('type', 'opened')->count();
        if ($opensCount > 0) $score += 20;

        // 2. Link clicks (+20)
        $clicksCount = $contact->activities()->where('type', 'clicked')->count();
        if ($clicksCount > 0) $score += 20;

        // 3. Phone/WhatsApp present (+10)
        if (!empty($contact->whatsapp_number)) $score += 10;

        // 4. Has metadata (company, etc.) (+10)
        if (!empty($contact->meta) && is_array($contact->meta) && count($contact->meta) > 0) {
            $score += 10;
        }

        // 5. Active deal (open status) (+15)
        $hasOpenDeal = $contact->deals()->where('status', 'open')->exists();
        if ($hasOpenDeal) $score += 15;

        // 6. Not bounced (+10)
        if (!in_array($contact->email_status, ['hard_bounce', 'soft_bounce', 'bounced'])) {
            $score += 10;
        }

        // 7. Not complained (+10)
        if ($contact->email_status !== 'complaint') {
            $score += 10;
        }

        // 8. Recency bonus — engaged within 30 days (+5)
        if ($contact->last_engaged_at && $contact->last_engaged_at->diffInDays(now()) <= 30) {
            $score += 5;
        }

        // Clamp 0-100
        $score = max(0, min(100, $score));

        $contact->update(['engagement_score' => $score]);
    }
}
