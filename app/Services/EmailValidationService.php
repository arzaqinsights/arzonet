<?php

namespace App\Services;

use App\Models\BlacklistedEmail;
use App\Models\Email;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EmailValidationService
{
    protected $rolePrefixes = [
        'admin', 'support', 'sales', 'billing', 'info', 'contact', 'hello', 
        'help', 'enquiry', 'office', 'marketing', 'team', 'jobs', 'careers',
        'accounting', 'hr', 'webmaster', 'hostmaster', 'postmaster', 'no-reply', 'noreply'
    ];

    protected $suspiciousTlds = [
        '.xyz', '.click', '.top', '.gq', '.ml', '.ga', '.cf', '.tk', '.men', '.icu', '.bid', '.win', '.date', '.loan', '.stream'
    ];

    protected $disposableDomains = [
        'mailinator.com', 'guerrillamail.com', 'tempmail.com', '10minutemail.com', 'throwawaymail.com', 
        'getnada.com', 'mail.tm', 'mail.gw', 'sharklasers.com', 'guerrillamail.biz', 'guerrillamail.de', 
        'guerrillamail.net', 'guerrillamail.org', 'guerrillamailblock.com', 'pokemail.net', 'spam4.me', 
        'grr.la', 'guerrillamail.com', 'dispostable.com', 'yopmail.com', 'maildrop.cc'
    ];

    /**
     * Validate a batch of email data and categorize them with health metrics.
     */
    public function validateBatch(array $emailData, ?int $currentListId = null, bool $skipDns = false): array
    {
        $valid = [];
        $invalid = [];
        $duplicates = [];
        $toRestore = [];
        $toValid = [];
        $seen = [];
        
        $blacklisted = array_flip($this->getBlacklistedEmails());
        
        $batchEmails = collect($emailData)->pluck('email')->filter()->map(fn($e) => $this->normalizeEmail($e))->unique();
        $batchPhones = collect($emailData)->pluck('whatsapp_number')->filter()->unique();

        if (!$currentListId) {
            $existingRecords = collect();
        } else {
            $existingRecords = Email::where('email_list_id', $currentListId)
                ->where(function($q) use ($batchEmails, $batchPhones) {
                    if ($batchEmails->isNotEmpty()) $q->orWhereIn('email', $batchEmails);
                    if ($batchPhones->isNotEmpty()) $q->orWhereIn('whatsapp_number', $batchPhones);
                })
                ->get(['id', 'email', 'whatsapp_number', 'status', 'is_archived'])
                ->keyBy(fn($item) => !empty($item->email) ? $item->email : ('wa:' . $item->whatsapp_number));
        }

        $trustedDomains = array_flip([
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'aol.com', 'live.com', 'msn.com', 
            'me.com', 'mac.com', 'ymail.com', 'rocketmail.com', 'googlemail.com', 'protonmail.com', 'proton.me', 
            'zoho.com', 'zoho.in', 'mail.com', 'hushmail.com', 'tutanota.com', 'fastmail.com', 'yandex.com', 'mail.ru',
            'rediffmail.com', 'indiatimes.com', 'sify.com', 'bsnl.in', 'vsnl.com', 'mtnl.net.in', 'nic.in', 'gov.in'
        ]);

        foreach ($emailData as $entry) {
            $rawEmail = preg_replace('/[\x00-\x1F\x7F\xA0\x{FEFF}\x{200B}-\x{200D}]/u', '', $entry['email'] ?? '');
            $rawEmail = trim($rawEmail);
            $whatsappNumber = $entry['whatsapp_number'] ?? null;

            // Must have at least one communication channel
            if (empty($rawEmail) && empty($whatsappNumber)) continue;

            $email = !empty($rawEmail) ? $this->normalizeEmail($rawEmail) : null;
            $entry['email'] = !empty($rawEmail) ? $rawEmail : null;
            $entry['whatsapp_number'] = $whatsappNumber;
            
            // ── Health Tracking Initialization ──
            $entry['email_status'] = 'valid';
            $entry['email_score'] = 5;
            $entry['email_risk_level'] = 'low';
            $entry['validation_reason'] = [];
            $entry['is_role_based'] = false;
            $entry['is_disposable'] = false;
            $entry['is_catch_all'] = false;
            $entry['has_typo'] = false;
            $entry['last_validation_at'] = now();

            // 1. Basic format check (Only if email is provided)
            if (!empty($rawEmail) && !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                $entry['status'] = 'invalid';
                $entry['email_status'] = 'invalid';
                $entry['email_score'] = 1;
                $entry['validation_reason'][] = 'Invalid email syntax';
                $entry['validation_reason'] = implode(', ', $entry['validation_reason']);
                $invalid[] = $entry;
                continue;
            }

            // 2. Blacklist check
            if (!empty($email) && isset($blacklisted[$email])) {
                $entry['status'] = 'invalid';
                $entry['email_status'] = 'blocked';
                $entry['email_score'] = 1;
                $entry['validation_reason'][] = 'Blacklisted email';
                $entry['validation_reason'] = implode(', ', $entry['validation_reason']);
                $invalid[] = $entry;
                continue;
            }

            // 3. Domain validation (Only if email exists)
            $domain = null;
            if ($email) {
                $parts = explode('@', $email);
                if (count($parts) !== 2) {
                    $entry['status'] = 'invalid';
                    $entry['email_status'] = 'invalid';
                    $entry['validation_reason'][] = 'Incomplete email structure';
                    $entry['validation_reason'] = implode(', ', $entry['validation_reason']);
                    $invalid[] = $entry;
                    continue;
                }
                $domain = $parts[1];
            }

            // 4. Duplicate check (Already in THIS list)
            $lookupKey = $email ?? ('wa:' . $whatsappNumber);
            if (isset($existingRecords[$lookupKey])) {
                $record = $existingRecords[$lookupKey];
                if ($record->status === 'permanent_delete') {
                    $entry['status'] = 'invalid';
                    $entry['email_status'] = 'blocked';
                    $entry['email_score'] = 1;
                    $entry['validation_reason'][] = 'Permanently deleted from this list';
                    $entry['validation_reason'] = implode(', ', $entry['validation_reason']);
                    $invalid[] = $entry;
                    continue;
                }
                if ($record->is_archived) {
                    $entry['id'] = $record->id;
                    $entry['status'] = 'to_restore';
                    $entry['validation_reason'] = is_array($entry['validation_reason']) ? implode(', ', $entry['validation_reason']) : $entry['validation_reason'];
                    $toRestore[] = $entry;
                    continue;
                }
                if ($record->status === 'duplicate') {
                    $entry['id'] = $record->id;
                    $entry['status'] = 'to_valid';
                    $entry['validation_reason'] = is_array($entry['validation_reason']) ? implode(', ', $entry['validation_reason']) : $entry['validation_reason'];
                    $toValid[] = $entry;
                    continue;
                }
                $entry['status'] = 'duplicate';
                $duplicates[] = $entry;
                continue;
            }

            // 5. Local Duplicate check
            if (isset($seen[$lookupKey])) {
                $entry['status'] = 'duplicate';
                $entry['validation_reason'] = is_array($entry['validation_reason']) ? implode(', ', $entry['validation_reason']) : $entry['validation_reason'];
                $duplicates[] = $entry;
                continue;
            }
            $seen[$lookupKey] = true;

            // ── Advanced Validations (Only if email exists) ──
            if (empty($email)) {
                $entry['status'] = 'valid';
                $entry['email_status'] = 'valid';
                $entry['email_score'] = 5;
                $entry['validation_reason'] = '';
                $valid[] = $entry;
                continue;
            }

            // A. Disposable detection
            if (in_array($domain, $this->disposableDomains)) {
                $entry['is_disposable'] = true;
                $entry['email_status'] = 'disposable';
                $entry['email_score'] = 2;
                $entry['validation_reason'][] = 'Disposable email provider';
            }

            // B. Role-based detection
            $localPart = $parts[0];
            if (in_array($localPart, $this->rolePrefixes)) {
                $entry['is_role_based'] = true;
                $entry['email_status'] = 'role_based';
                $entry['email_score'] -= 2;
                $entry['validation_reason'][] = 'Role-based email address';
            }

            // C. Suspicious TLD detection
            foreach ($this->suspiciousTlds as $tld) {
                if (Str::endsWith($domain, $tld)) {
                    $entry['email_status'] = 'risky';
                    $entry['email_score'] -= 1;
                    $entry['validation_reason'][] = "Suspicious TLD ($tld)";
                    break;
                }
            }

            // D. Randomness/Entropy detection
            if ($this->isSuspiciousPattern($localPart)) {
                $entry['email_status'] = 'suspicious';
                $entry['email_score'] -= 2;
                $entry['validation_reason'][] = 'Suspicious local part pattern';
            }

            // E. DNS Check (Mark as Invalid if domain is non-existent to prevent 100% bounce)
            if (!$skipDns && !isset($trustedDomains[$domain])) {
                $isDomainValid = Cache::remember("dns_mx_{$domain}", 86400, function() use ($domain) {
                    // Domain MUST have either an MX record or at least an A record to be deliverable
                    return @checkdnsrr($domain, "MX") || @checkdnsrr($domain, "A");
                });

                if (!$isDomainValid) {
                    $entry['status'] = 'invalid';
                    $entry['email_status'] = 'invalid';
                    $entry['email_score'] = 1;
                    $entry['validation_reason'][] = 'Invalid domain: No MX/A records found (will bounce 100%)';
                    $entry['validation_reason'] = implode(', ', $entry['validation_reason']);
                    $invalid[] = $entry;
                    continue;
                }
            }

            // Final Score Clamp
            $entry['email_score'] = max(1, min(5, $entry['email_score']));
            if ($entry['email_score'] <= 2) $entry['email_risk_level'] = 'high';
            elseif ($entry['email_score'] <= 3) $entry['email_risk_level'] = 'medium';
            
            $entry['status'] = 'valid';
            $entry['validation_reason'] = is_array($entry['validation_reason']) ? implode(', ', $entry['validation_reason']) : $entry['validation_reason'];
            $valid[] = $entry;
        }

        return [
            'valid'     => $valid,
            'invalid'   => $invalid,
            'duplicate' => $duplicates,
            'to_restore'=> $toRestore,
            'to_valid'  => $toValid,
        ];
    }

    public function normalizeEmail(?string $email): ?string
    {
        if (empty($email)) return null;
        $email = strtolower(trim($email));
        if (!str_contains($email, '@')) return $email;

        [$local, $domain] = explode('@', $email);
        
        // Gmail Dot and Plus normalization
        if (in_array($domain, ['gmail.com', 'googlemail.com'])) {
            $local = str_replace('.', '', $local);
            $local = explode('+', $local)[0];
            return $local . '@' . $domain;
        }

        // Plus normalization for other providers (Outlook, iCloud, etc)
        if (in_array($domain, ['outlook.com', 'hotmail.com', 'icloud.com'])) {
            $local = explode('+', $local)[0];
            return $local . '@' . $domain;
        }

        return $email;
    }

    protected function isSuspiciousPattern(string $local): bool
    {
        // 1. Excessive numbers (more than 5 digits in a row)
        if (preg_match('/[0-9]{6,}/', $local)) return true;

        // 2. Repeated characters (more than 4 times)
        if (preg_match('/(.)\1{4,}/', $local)) return true;

        // 3. Random string heuristic (no vowels and long)
        if (strlen($local) > 8 && !preg_match('/[aeiouy]/i', $local)) return true;

        // 4. Consecutive random consonants
        if (preg_match('/[^aeiouy]{6,}/i', $local)) return true;

        return false;
    }

    protected function getBlacklistedEmails(): array
    {
        return BlacklistedEmail::pluck('email')->map(fn($e) => $this->normalizeEmail($e))->toArray();
    }
}
