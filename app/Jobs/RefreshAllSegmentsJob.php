<?php

namespace App\Jobs;

use App\Models\Segment;
use App\Models\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshAllSegmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    public function handle(): void
    {
        Log::info("Starting RefreshAllSegmentsJob...");

        Segment::chunk(100, function ($segments) {
            foreach ($segments as $segment) {
                try {
                    $query = Email::where('email_list_id', $segment->email_list_id);
                    $query = Segment::applyRulesToQuery($query, $segment->rules ?? []);

                    $segment->update([
                        'contact_count' => $query->count(),
                        'last_refreshed_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to refresh segment #{$segment->id} ({$segment->name}): " . $e->getMessage());
                }
            }
        });

        Log::info("RefreshAllSegmentsJob completed.");
    }
}
