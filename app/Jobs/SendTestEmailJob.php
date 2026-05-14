<?php

namespace App\Jobs;

use App\Models\Template;
use App\Models\Sender;
use App\Services\SESService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTestEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public int $templateId,
        public ?int $senderId = null
    ) {}

    public function handle(MailService $mailService): void
    {
        $template = Template::find($this->templateId);
        
        if (!$template) {
            return;
        }

        $sender = null;
        if ($this->senderId) {
            $sender = Sender::find($this->senderId);
        } else {
            // Fallback to first verified sender if not provided (legacy support)
            $sender = Sender::verified()->first();
        }

        if (!$sender) {
            Log::error("Test email aborted: No verified sender available.");
            return;
        }

        // Dummy data for personalization
        $dummyData = [
            'name'  => 'Test User',
            'email' => $this->email,
            'meta'  => [],
        ];

        $personalizedHtml = $mailService->replaceVariables($template->html_content, $dummyData);

        try {
            $mailService->send(
                sender: $sender,
                to: $this->email,
                subject: "[TEST] " . $template->name,
                html: $personalizedHtml
            );
            
            Log::info("Test email sent via MailService to {$this->email}");
        } catch (\Exception $e) {
            Log::warning("Test email failed via MailService to {$this->email}: {$e->getMessage()}");
        }
    }
}
