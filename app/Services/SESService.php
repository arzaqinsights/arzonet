<?php

namespace App\Services;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use App\Models\Sender;
use App\Models\Template;
use Illuminate\Support\Facades\Log;

class SESService
{
    protected ?SesClient $client;

    public function __construct()
    {
        $key = config('services.ses.key');
        $secret = config('services.ses.secret');

        if (empty($key) || empty($secret)) {
            $this->client = null;
            Log::warning('SESService: AWS Credentials not found. SES functionality will be disabled.');
            return;
        }

        $this->client = new SesClient([
            'version' => 'latest',
            'region'  => config('services.ses.region', 'us-east-1'),
            'credentials' => [
                'key'    => $key,
                'secret' => $secret,
            ],
        ]);
    }

    public function verifyEmail(string $email)
    {
        return $this->client->verifyEmailIdentity([
            'EmailAddress' => $email,
        ]);
    }

    public function getVerificationStatus(string $email)
    {
        $response = $this->client->getIdentityVerificationAttributes([
            'Identities' => [$email],
        ]);

        return $response['VerificationAttributes'][$email]['VerificationStatus'] ?? 'Pending';
    }

    /**
     * Replaces variable tags in a template with actual data.
     *
     * @param string $templateHtml
     * @param array $data
     * @return string
     */
    public function replaceVariables(string $content, array $data): string
    {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';

        $replacements = [
            '{{name}}' => $name,
            '{{ name }}' => $name,
            '@{{name}}' => $name,
            '@{{ name }}' => $name,
            '{{email}}' => $email,
            '{{ email }}' => $email,
            '@{{email}}' => $email,
            '@{{ email }}' => $email,
        ];

        // Include any custom fields specified in meta
        if (isset($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                $val = current((array)$value) ?? '';
                $replacements['{{' . $key . '}}'] = $val;
                $replacements['{{ ' . $key . ' }}'] = $val;
                $replacements['@{{' . $key . '}}'] = $val;
                $replacements['@{{ ' . $key . ' }}'] = $val;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Sends a single email using SES SendEmail API.
     *
     * @param string $to
     * @param string $subject
     * @param string $html
     * @param string $from
     * @return string|null Returns MessageId on success, null on failure.
     * @throws AwsException
     */
    public function sendSingleEmail(string $to, string $subject, string $html, string $from, array $headers = []): ?string
    {
        if (!$this->client) {
            throw new \Exception('SES client not configured. Cannot send email.');
        }

        // If no custom headers, use the simple SendEmail API
        if (empty($headers)) {
            $result = $this->client->sendEmail([
                'Destination' => ['ToAddresses' => [$to]],
                'Message' => [
                    'Body' => [
                        'Html' => ['Charset' => 'UTF-8', 'Data' => $html],
                        'Text' => ['Charset' => 'UTF-8', 'Data' => strip_tags($html)],
                    ],
                    'Subject' => ['Charset' => 'UTF-8', 'Data' => $subject],
                ],
                'Source' => $from,
            ]);
            return $result->get('MessageId');
        }

        // For custom headers (like List-Unsubscribe), we must use SendRawEmail
        $boundary = uniqid('np');
        $rawMessage = "From: {$from}\r\n";
        $rawMessage .= "To: {$to}\r\n";
        $rawMessage .= "Subject: {$subject}\r\n";
        
        foreach ($headers as $key => $value) {
            $rawMessage .= "{$key}: {$value}\r\n";
        }

        $rawMessage .= "MIME-Version: 1.0\r\n";
        $rawMessage .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n";
        
        $rawMessage .= "--{$boundary}\r\n";
        $rawMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $rawMessage .= strip_tags($html) . "\r\n\r\n";
        
        $rawMessage .= "--{$boundary}\r\n";
        $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
        $rawMessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $rawMessage .= $html . "\r\n\r\n";
        $rawMessage .= "--{$boundary}--";

        $result = $this->client->sendRawEmail([
            'RawMessage' => [
                'Data' => $rawMessage,
            ],
        ]);

        return $result->get('MessageId');
    }

    /**
     * Process bulk emails locally via the SES SDK.
     * (We loop locally and sleep, so it conforms to standard single HTTP SendEmail rate limit).
     *
     * @param array $emails Contains array of mapped email data models.
     * @param \App\Models\Template $template
     * @param \App\Models\Sender $sender
     * @return array Contains results [success => count, failed => count]
     */
    public function sendBulkEmails(array $emails, Template $template, Sender $sender): array
    {
        $success = 0;
        $failed = 0;
        $fromEmail = $sender->status === 'verified' 
            ? $sender->email 
            : (env('SES_FROM_EMAIL') ?: 'hello@arzaqinsights.com');

        foreach ($emails as $emailData) {
            $data = [
                'name' => $emailData->name,
                'email' => $emailData->email,
                'meta' => $emailData->meta ?? [],
            ];
            
            $html = $this->replaceVariables($template->html_content, $data);
            $subject = $this->replaceVariables($template->subject, $data);

            try {
                $this->sendSingleEmail(
                    $emailData->email,
                    $subject,
                    $html,
                    $fromEmail
                );
                $success++;
            } catch (\Exception $e) {
                Log::error("Bulk Send Failed to {$emailData->email}: " . $e->getMessage());
                $failed++;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Sends a verification email to the specified address.
     */
    public function sendVerificationEmail(string $email): bool
    {
        if (!$this->client) return false;

        try {
            $this->client->verifyEmailIdentity([
                'EmailAddress' => $email,
            ]);
            return true;
        } catch (AwsException $e) {
            Log::error('SES VerifyEmailIdentity Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks the verification status of an email address.
     */
    public function checkVerificationStatus(string $email): ?string
    {
        if (!$this->client) return null;

        try {
            $result = $this->client->getIdentityVerificationAttributes([
                'Identities' => [$email],
            ]);

            $attributes = $result->get('VerificationAttributes');

            if (isset($attributes[$email])) {
                return $attributes[$email]['VerificationStatus'];
            }

            return null;
        } catch (AwsException $e) {
            Log::error('SES GetIdentityVerificationAttributes Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch the SES sending quota for the account.
     * 
     * @return array [Max24HourSend, MaxSendRate, SentLast24Hours]
     */
    public function getSendQuota(): array
    {
        if (!$this->client) {
            return [
                'Max24HourSend' => 200,
                'MaxSendRate' => 1,
                'SentLast24Hours' => 0
            ];
        }

        try {
            $result = $this->client->getSendQuota();
            return [
                'Max24HourSend' => (float) $result->get('Max24HourSend'),
                'MaxSendRate' => (float) $result->get('MaxSendRate'),
                'SentLast24Hours' => (float) $result->get('SentLast24Hours'),
            ];
        } catch (AwsException $e) {
            Log::error('SES GetSendQuota Error: ' . $e->getMessage());
            return [
                'Max24HourSend' => 200,
                'MaxSendRate' => 1,
                'SentLast24Hours' => 0
            ];
        }
    }
}
