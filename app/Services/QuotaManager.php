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
        // Redis::throttle requires 'seconds' as interval.
        // If rate is 14/sec, interval is 1s, limit is 14.
        return Redis::throttle($key)
            ->allow($ratePerSecond)
            ->every(1)
            ->then(function () {
                return true;
            }, function () {
                return false;
            });
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
