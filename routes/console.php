<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckSenderVerificationStatusJob;
use App\Jobs\UpdateContactSegmentsJob;
use App\Jobs\ProcessWebhookBatchJob;
use App\Jobs\ProcessAutomationWorkflowsJob;
use App\Jobs\ProcessSequenceEnrollmentsJob;

Schedule::job(new CheckSenderVerificationStatusJob)->everyTenSeconds();
Schedule::job(new UpdateContactSegmentsJob)->everyTenMinutes();
Schedule::job(new ProcessWebhookBatchJob)->everyFiveSeconds();
Schedule::job(new ProcessAutomationWorkflowsJob)->everyMinute();
Schedule::job(new ProcessSequenceEnrollmentsJob)->everyMinute();

Schedule::call(function () {
    $scheduledCampaigns = \App\Models\Campaign::where('status', 'scheduled')
        ->where('scheduled_at', '<=', now())
        ->get();

    foreach ($scheduledCampaigns as $campaign) {
        $campaign->update(['status' => 'preparing']);
        \App\Jobs\PrepareCampaignDispatchJob::dispatch($campaign->id)->onQueue('high');
    }
})->everyMinute();

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

