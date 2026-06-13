<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

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
        if ($emailRecord instanceof \App\Models\Email) {
            $unsubUrl = $emailRecord->getUnsubscribeUrl($logId);
            $preferencesUrl = route('unsubscribe.show', ['id' => $emailRecord->id, 'token' => hash_hmac('sha256', $emailRecord->id . $emailRecord->email, config('app.key')), 'lid' => $logId]);
            $footer = '<br><br>'
                    . '<div style="margin-top: 40px; padding: 24px 16px; border-top: 1px solid #e5e7eb; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; text-align: center; color: #6b7280; font-size: 12px; line-height: 1.6;">'
                    . '  <p style="margin: 0 0 8px 0; color: #374151; font-weight: 600;">Subscription Preferences</p>'
                    . '  <p style="margin: 0 0 16px 0; color: #9ca3af; font-size: 11px;">This email was sent to <strong style="color: #4b5563;">' . htmlspecialchars($to) . '</strong> because you subscribed to our mailing list.</p>'
                    . '  <p style="margin: 0;">'
                    . '    <a href="' . $unsubUrl . '" style="color: #4f46e5; text-decoration: underline; font-weight: 500; margin: 0 8px;">Unsubscribe</a>'
                    . '    <span style="color: #d1d5db;">|</span>'
                    . '    <a href="' . $preferencesUrl . '" style="color: #4f46e5; text-decoration: underline; font-weight: 500; margin: 0 8px;">Manage Preferences</a>'
                    . '  </p>'
                    . '</div>';
            $html .= $footer;

            $headers['List-Unsubscribe'] = '<' . $unsubUrl . '>';
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        // 1. AWS SES (Identity Specific)
        if ($sender->type === 'ses') {
            // Use sender specific credentials if available
            if ($sender->ses_key && $sender->ses_secret) {
                $customSes = new SESService([
                    'key' => $sender->ses_key,
                    'secret' => $sender->ses_secret,
                    'region' => $sender->ses_region ?? 'us-east-1'
                ]);
                return $customSes->sendSingleEmail($to, $subject, $html, $sender->email, $headers);
            }
            return $this->sesService->sendSingleEmail($to, $subject, $html, $sender->email, $headers);
        }

        // 2. SendGrid (API Based)
        if ($sender->type === 'sendgrid') {
            return $this->sendViaSendGrid($sender, $to, $subject, $html, $headers, $logId);
        }

        // 3. SMTP (Standard)
        try {
            $this->configureSmtp($sender);

            Mail::mailer('dynamic_smtp')->html($html, function ($message) use ($to, $subject, $sender, $headers) {
                $message->to($to)
                        ->subject($subject)
                        ->from($sender->email, $sender->from_name)
                        ->replyTo($sender->email, $sender->from_name);
                
                if (!empty($headers)) {
                    $msgHeaders = $message->getHeaders();
                    foreach ($headers as $key => $value) {
                        $msgHeaders->addTextHeader($key, $value);
                    }
                }
            });

            return "smtp_success_" . bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            Log::error("SMTP Error: " . $e->getMessage());
            throw $e;
        } finally {
            Mail::purge('dynamic_smtp');
        }
    }

    protected function sendViaSendGrid(Sender $sender, string $to, string $subject, string $html, array $headers, ?int $logId = null): ?string
    {
        $apiKey = $sender->sendgrid_api_key ?: config('services.sendgrid.key');
        
        \Log::info("SendGrid Attempt: Sender={$sender->email}, KeyMask=" . substr($apiKey, 0, 10) . "...");

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [[
                    'to' => [['email' => $to]],
                    'subject' => $subject,
                    'headers' => (object) $headers,
                ]],
                'from' => [
                    'email' => $sender->email,
                    'name' => $sender->from_name
                ],
                'reply_to' => [
                    'email' => $sender->email,
                    'name' => $sender->from_name
                ],
                'content' => [[
                    'type' => 'text/html',
                    'value' => $html
                ]],
                'custom_args' => [
                    'log_id' => (string) $logId,
                ],
                'mail_settings' => [
                    'bypass_list_management' => [
                        'enable' => true
                    ]
                ]
            ]);

        if ($response->successful()) {
            return $response->header('X-Message-Id') ?: 'sg_success_' . bin2hex(random_bytes(8));
        }

        $errorBody = $response->body();
        \Log::error("SendGrid API Failure: " . $errorBody, [
            'to' => $to,
            'sender' => $sender->email,
            'status' => $response->status()
        ]);

        throw new \Exception("SendGrid Error: " . $errorBody);
    }

    protected function configureSmtp(Sender $sender): void
    {
        // Purge old instance first
        Mail::purge('dynamic_smtp');

        Config::set('mail.mailers.dynamic_smtp', [
            'transport' => 'smtp',
            'host' => $sender->smtp_host,
            'port' => $sender->smtp_port,
            'encryption' => ($sender->smtp_encryption === 'none' || empty($sender->smtp_encryption)) ? null : $sender->smtp_encryption,
            'username' => $sender->smtp_username,
            'password' => $sender->smtp_password,
            'timeout' => 30,
            'auth_mode' => null,
        ]);
    }

    public function replaceVariables(string $content, array $data, bool $escapeHtml = true): string
    {
        return $this->personalizer->personalize($content, $data, $escapeHtml);
    }
}
