<?php

namespace App\Jobs;

use App\Services\SegmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateContactSegmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $emailId = null,
        public ?int $listId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SegmentService $service): void
    {
        $service->recalculateSegments($this->emailId, $this->listId);
    }
}
