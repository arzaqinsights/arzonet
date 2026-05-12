<?php

namespace App\Jobs;

use App\Services\WhatsApp\WebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $payload)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookProcessor $processor): void
    {
        $processor->process($this->payload);
    }
}
