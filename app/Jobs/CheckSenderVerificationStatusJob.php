<?php

namespace App\Jobs;

use App\Models\Sender;
use App\Services\SESService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSenderVerificationStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(SESService $sesService): void
    {
        $pendingSenders = Sender::pending()->get();

        foreach ($pendingSenders as $sender) {
            $status = $sesService->checkVerificationStatus($sender->email);

            if ($status === 'Success') {
                $sender->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                ]);
            } elseif ($status === 'Failed') {
                $sender->update([
                    'status' => 'failed',
                ]);
            }
            // If status is 'Pending' or null, we do nothing and wait for next tick
        }
    }
}
