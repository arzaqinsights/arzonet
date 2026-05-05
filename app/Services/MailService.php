<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MailService
{
    protected SESService $sesService;
    protected PersonalizationService $personalizer;

    public function __construct(SESService $sesService, PersonalizationService $personalizer)
    {
        $this->sesService = $sesService;
        $this->personalizer = $personalizer;
    }

    public function send(Sender $sender, string $to, string $subject, string $html, ?object $emailRecord = null, ?int $logId = null): ?string
    {
        $headers = [];
        // Add automatic unsubscribe link if email record is a valid model
        if ($emailRecord instanceof \App\Models\Email) {
            $unsubUrl = $emailRecord->getUnsubscribeUrl($logId);
            $footer = '<br><br><hr><p style="font-size: 12px; color: #666;">You are receiving this because you subscribed via our list. <a href="' . $unsubUrl . '">Unsubscribe from this list</a></p>';
            $html .= $footer;

            // Prepare native headers for email clients (Gmail/Apple Mail)
            $headers['List-Unsubscribe'] = '<' . $unsubUrl . '>';
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        if ($sender->type === 'ses') {
            return $this->sesService->sendSingleEmail($to, $subject, $html, $sender->email, $headers);
        }

        // SMTP Logic
        try {
            Mail::purge('dynamic_smtp');
            $this->configureSmtp($sender);

            Mail::mailer('dynamic_smtp')->html($html, function ($message) use ($to, $subject, $sender, $headers) {
                $message->to($to)
                    ->subject($subject)
                    ->from($sender->email, $sender->from_name);
                
                // Add List-Unsubscribe headers for native email client buttons
                if (!empty($headers)) {
                    $msgHeaders = $message->getHeaders();
                    foreach ($headers as $key => $value) {
                        $msgHeaders->addTextHeader($key, $value);
                    }
                }
            });

            return "smtp_success_" . uniqid();
        } catch (\Exception $e) {
            Log::error("SMTP Send Error: " . $e->getMessage());
            throw $e;
        }
    }

    protected function configureSmtp(Sender $sender): void
    {
        Config::set('mail.mailers.dynamic_smtp', [
            'transport' => 'smtp',
            'host' => $sender->smtp_host,
            'port' => $sender->smtp_port,
            'encryption' => $sender->smtp_encryption === 'none' ? null : $sender->smtp_encryption,
            'username' => $sender->smtp_username,
            'password' => $sender->smtp_password,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);
    }

    public function replaceVariables(string $content, array $data, bool $escapeHtml = true): string
    {
        return $this->personalizer->personalize($content, $data, $escapeHtml);
    }
}
