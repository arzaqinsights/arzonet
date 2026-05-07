<?php

namespace App\Services;

use App\Models\BlacklistedEmail;
use App\Models\Email;
use Illuminate\Support\Facades\Cache;

class EmailValidationService
{
    /**
     * Validate a batch of email data and categorize them.
     */
    public function validateBatch(array $emailData, ?int $currentListId = null, bool $skipDns = false): array
    {
        $valid = [];
        $invalid = [];
        $duplicates = [];
        $toRestore = [];
        $toValid = []; // Records currently 'duplicate' that should be promoted to 'valid'
        $seen = [];
        
        // ── Step 1: Prepare Cross-Batch Lookups ──
        
        // Blacklist lookup
        $blacklisted = array_flip($this->getBlacklistedEmails());

        // List-specific DB lookup (STRICTLY scoped to this list only)
        $batchEmails = collect($emailData)->pluck('email')->map(fn($e) => strtolower(trim($e)))->filter()->toArray();
        
        if (!$currentListId) {
            // If no list ID, we can't check duplicates safely. 
            // In the context of this app, imports MUST be scoped.
            $existingRecords = collect();
        } else {
            $existingRecords = Email::where('email_list_id', $currentListId)
                ->whereIn('email', $batchEmails)
                ->get(['email', 'status', 'is_archived'])
                ->keyBy(fn($item) => strtolower($item->email));
        }

        // Massively Expanded Trusted domains to skip DNS check (The "Fast-Lane" list)
        $trustedDomains = array_flip([
            // Global Majors
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com', 'aol.com', 'live.com', 'msn.com', 
            'me.com', 'mac.com', 'ymail.com', 'rocketmail.com', 'googlemail.com', 'protonmail.com', 'proton.me', 
            'zoho.com', 'zoho.in', 'mail.com', 'hushmail.com', 'tutanota.com', 'fastmail.com', 'yandex.com', 'mail.ru',
            
            // Indian Majors & Government
            'rediffmail.com', 'indiatimes.com', 'sify.com', 'bsnl.in', 'vsnl.com', 'mtnl.net.in', 'nic.in', 'gov.in',
            'reliance.com', 'tcs.com', 'infosys.com', 'wipro.com', 'airtelmail.in', 'jio.com',
            
            // Regional Variations
            'gmail.co.in', 'yahoo.co.in', 'outlook.in', 'hotmail.co.in', 'live.in', 'yahoo.com.in',
            'hotmail.co.uk', 'yahoo.co.uk', 'btinternet.com', 'virginmedia.com', 'blueyonder.co.uk', 'ntlworld.com',
            'talktalk.net', 'orange.fr', 'wanadoo.fr', 'free.fr', 'laposte.net', 'sfr.fr', 'neuf.fr', 'aliceadsl.fr',
            'gmx.de', 'web.de', 't-online.de', 'freenet.de', 'arcor.de', 'gmx.net', 'libero.it', 'virgilio.it', 
            'uol.com.br', 'bol.com.br', 'terra.com.br', 'ig.com.br', 'globo.com'
        ]);

        foreach ($emailData as $entry) {
            $email = strtolower(trim($entry['email'] ?? ''));

            if (empty($email)) continue;

            // 1. Basic format check (Fastest)
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $entry['reason'] = 'Invalid email format';
                $entry['status'] = 'invalid';
                $invalid[] = $entry;
                continue;
            }

            // 2. Blacklist check
            if (isset($blacklisted[$email])) {
                $entry['reason'] = 'Blacklisted email';
                $entry['status'] = 'invalid';
                $invalid[] = $entry;
                continue;
            }

            // 3. Duplicate check (Already in THIS list)
            if (isset($existingRecords[$email])) {
                $record = $existingRecords[$email];
                
                // If permanently deleted from THIS list, mark as banned
                if ($record->status === 'permanent_delete') {
                    $entry['reason'] = 'Banishment active (Permanently deleted from this list)';
                    $entry['status'] = 'invalid';
                    $invalid[] = $entry;
                    continue;
                }

                // If archived, mark for restoration/update (Top Priority)
                if ($record->is_archived) {
                    $entry['status'] = 'to_restore';
                    $toRestore[] = $entry;
                    continue;
                }

                // If existing record is 'duplicate', mark for promotion to 'valid'
                if ($record->status === 'duplicate') {
                    $entry['status'] = 'to_valid';
                    $toValid[] = $entry;
                    continue;
                }

                $entry['reason'] = 'Email already exists and is active/valid in this list';
                $entry['status'] = 'duplicate';
                $duplicates[] = $entry;
                continue;
            }

            // 4. Local Duplicate check (Within this batch/list)
            if (isset($seen[$email])) {
                $entry['reason'] = 'Duplicate in list';
                $entry['status'] = 'duplicate';
                $duplicates[] = $entry;
                continue;
            }

            // 5. DNS Check (Optional & Cached)
            $domain = substr(strrchr($email, "@"), 1);
            
            if (!$skipDns && !isset($trustedDomains[$domain])) {
                $isDomainValid = Cache::remember("dns_mx_{$domain}", 86400, function() use ($domain) {
                    return @checkdnsrr($domain, "MX");
                });

                if (!$isDomainValid) {
                    $entry['reason'] = 'No MX record found';
                    $entry['status'] = 'invalid';
                    $invalid[] = $entry;
                    continue;
                }
            }

            $seen[$email] = true;
            $entry['status'] = 'valid';
            $entry['reason'] = null;
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

    /**
     * Get all blacklisted emails as a flat array.
     */
    protected function getBlacklistedEmails(): array
    {
        return BlacklistedEmail::pluck('email')->map(fn($e) => strtolower($e))->toArray();
    }
}
