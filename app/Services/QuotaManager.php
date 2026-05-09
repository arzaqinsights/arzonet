<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class QuotaManager
{
    public function __construct(protected SESService $ses) {}

    /**
     * Get the current SES quota, with caching.
     */
    public function getQuota(): array
    {
        if (empty(config('services.ses.key')) || empty(config('services.ses.secret'))) {
            return [
                'Max24HourSend' => 200,
                'MaxSendRate' => 14, // Reasonable default
                'SentLast24Hours' => 0
            ];
        }

        return Cache::remember('ses_quota', 300, function () {
            return $this->ses->getSendQuota();
        });
    }

    /**
     * Check if we can send an email based on the current rate.
     * Uses Redis for distributed rate limiting.
     */
    public function throttle(string $key, float $ratePerSecond)
    {
        try {
            // Redis::throttle requires 'seconds' as interval.
            return Redis::throttle($key)
                ->allow($ratePerSecond)
                ->every(1)
                ->then(function () {
                    return true;
                }, function () {
                    return false;
                });
        } catch (\Exception $e) {
            // Fallback: If Redis fails, just allow and log warning
            Log::warning("Redis Throttling Failed: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Calculate a safe sending rate.
     */
    public function getSafeRate(): float
    {
        $quota = $this->getQuota();
        $maxRate = $quota['MaxSendRate'];

        // Use 90% of max rate to be safe
        return max(1, $maxRate * 0.9);
    }
}
