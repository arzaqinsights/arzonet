<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckSenderVerificationStatusJob;
use App\Jobs\UpdateContactSegmentsJob;

Schedule::job(new CheckSenderVerificationStatusJob)->everyTenSeconds();
Schedule::job(new UpdateContactSegmentsJob)->everyTenMinutes();

Schedule::call(function () {
    $expiredIds = \Illuminate\Support\Facades\DB::table('emails')
        ->where('subscription_status', 'unsubscribed')
        ->whereNotNull('unsubscribe_expires_at')
        ->where('unsubscribe_expires_at', '<=', now())
        ->pluck('id');

    if ($expiredIds->isNotEmpty()) {
        \Illuminate\Support\Facades\DB::table('emails')
            ->whereIn('id', $expiredIds)
            ->update([
                'subscription_status' => 'subscribed',
                'unsubscribe_expires_at' => null,
                'unsubscribed_at' => null
            ]);

        foreach ($expiredIds as $id) {
            \App\Jobs\UpdateContactSegmentsJob::dispatch(emailId: $id);
        }
    }
})->everyMinute();

