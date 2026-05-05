<?php

namespace App\Services;

use App\Models\UsageStat;
use App\Models\Campaign;
use App\Models\EmailLog;
use Carbon\Carbon;

class CostEstimationService
{
    /**
     * Get cost per email from config.
     */
    public function costPerEmail(): float
    {
        return (float) config('emailplatform.cost_per_email', 0.0001);
    }

    /**
     * Estimate cost for a campaign.
     */
    public function campaignCost(int $recipientCount): float
    {
        return round($recipientCount * $this->costPerEmail(), 4);
    }

    /**
     * Calculate total cost for a date range.
     */
    public function totalCost(Carbon $from, Carbon $to): float
    {
        return (float) UsageStat::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->sum('cost');
    }

    /**
     * Get monthly cost for current month.
     */
    public function monthlyCost(): float
    {
        return $this->totalCost(
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    /**
     * Get cost breakdown for dashboard.
     */
    public function getCostBreakdown(): array
    {
        $costPerEmail = $this->costPerEmail();

        $dailySent = UsageStat::where('date', today()->toDateString())->value('emails_sent') ?? 0;
        $weeklySent = UsageStat::whereBetween('date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ])->sum('emails_sent');
        $monthlySent = UsageStat::whereBetween('date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ])->sum('emails_sent');

        return [
            'cost_per_email' => $costPerEmail,
            'daily_cost'     => round($dailySent * $costPerEmail, 4),
            'weekly_cost'    => round($weeklySent * $costPerEmail, 4),
            'monthly_cost'   => round($monthlySent * $costPerEmail, 4),
            'daily_sent'     => $dailySent,
            'weekly_sent'    => $weeklySent,
            'monthly_sent'   => $monthlySent,
        ];
    }
}
