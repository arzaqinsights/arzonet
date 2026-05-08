<?php

namespace App\Services;

use App\Models\UsageStat;
use Carbon\Carbon;

class UsageTrackingService
{
    /**
     * Increment the sent counter for today.
     */
    public function incrementSent(int $count = 1): void
    {
        $stat = UsageStat::firstOrCreate(
            ['date' => today()->toDateString()],
            ['emails_sent' => 0, 'emails_failed' => 0, 'cost' => 0]
        );

        $stat->increment('emails_sent', $count);
        $stat->increment('cost', $count * config('emailplatform.cost_per_email', 0.0001));
    }

    /**
     * Increment the failed counter for today.
     */
    public function incrementFailed(int $count = 1): void
    {
        $stat = UsageStat::firstOrCreate(
            ['date' => today()->toDateString()],
            ['emails_sent' => 0, 'emails_failed' => 0, 'cost' => 0]
        );

        $stat->increment('emails_failed', $count);
    }

    /**
     * Get usage stats for dashboard.
     */
    public function getUsageStats(): array
    {
        $limits = config('emailplatform.limits') ?? [
            'daily' => 0,
            'weekly' => 0,
            'monthly' => 0,
        ];

        $daily = UsageStat::where('date', today()->toDateString())->first();
        $dailySent = $daily->emails_sent ?? 0;

        $weeklySent = (int) UsageStat::whereBetween('date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ])->sum('emails_sent');

        $monthlySent = (int) UsageStat::whereBetween('date', [
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        ])->sum('emails_sent');

        return [
            'daily' => [
                'sent'      => $dailySent,
                'limit'     => $limits['daily'],
                'remaining' => max(0, $limits['daily'] - $dailySent),
                'percent'   => $limits['daily'] > 0 ? round(($dailySent / $limits['daily']) * 100, 1) : 0,
            ],
            'weekly' => [
                'sent'      => $weeklySent,
                'limit'     => $limits['weekly'],
                'remaining' => max(0, $limits['weekly'] - $weeklySent),
                'percent'   => $limits['weekly'] > 0 ? round(($weeklySent / $limits['weekly']) * 100, 1) : 0,
            ],
            'monthly' => [
                'sent'      => $monthlySent,
                'limit'     => $limits['monthly'],
                'remaining' => max(0, $limits['monthly'] - $monthlySent),
                'percent'   => $limits['monthly'] > 0 ? round(($monthlySent / $limits['monthly']) * 100, 1) : 0,
            ],
        ];
    }

    /**
     * Check if daily limit has been reached.
     */
    public function isDailyLimitReached(): bool
    {
        $dailySent = UsageStat::where('date', today()->toDateString())->value('emails_sent') ?? 0;
        return $dailySent >= config('emailplatform.limits.daily', 10000);
    }

    /**
     * Get the last 30 days of usage data for charts.
     */
    public function getLast30DaysData(): array
    {
        $stats = UsageStat::where('date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('date')
            ->get();

        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $stat = $stats->firstWhere('date', Carbon::parse($date)->toDateString());
            $data[] = [
                'date'   => $date,
                'sent'   => $stat->emails_sent ?? 0,
                'failed' => $stat->emails_failed ?? 0,
                'cost'   => (float) ($stat->cost ?? 0),
            ];
        }

        return $data;
    }
}
