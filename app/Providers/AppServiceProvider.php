<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('sendgrid', function (object $job) {
            // High Performance: Allow 100 batches (5,000 emails) per minute globally
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(100);
        });

        \Illuminate\Support\Facades\Blade::if('permission', function (string $permission) {
            return \App\Models\User::canAccess($permission);
        });
    }
}
