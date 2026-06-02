<?php

namespace App\Services;

use App\Models\Email;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SegmentService
{
    /**
     * Predefined automatic segments list.
     */
    public static function getAutoSegmentsList(): array
    {
        return [
            'Auto: Never Sent',
            'Auto: Never Opened',
            'Auto: Never Clicked',
            'Auto: Active Opener (30d)',
            'Auto: Inactive Opener',
            'Auto: Recent Opener (7d)',
            'Auto: Recent Clicker (7d)',
            'Auto: Active Clicker (30d)',
            'Auto: Highly Engaged',
            'Auto: Recently Sent (7d)',
            'Auto: Recently Sent (30d)',
            'Auto: Bounced Contact',
            'Auto: Hard Bounce',
            'Auto: Soft Bounce',
            'Auto: Unsubscribed Contact',
            'Auto: Spam Reporter',
            'Auto: Risky Contact',
            'Auto: Disposable Email',
            'Auto: Role-Based Email',
            'Auto: Catch-All Email',
            'Auto: Typo Email',
            'Auto: Valid Email',
            'Auto: Invalid Email',
            'Auto: High Quality Email',
            'Auto: Low Quality Email',
            'Auto: Has WhatsApp',
            'Auto: WhatsApp Opted-In',
            'Auto: WhatsApp Opted-Out',
            'Auto: Active WhatsApp (30d)',
            'Auto: Never Messaged on WhatsApp',
        ];
    }

    /**
     * Recalculate dynamic segments for emails.
     * Can filter by a single email ID or a specific list ID, or globally.
     */
    public function recalculateSegments(?int $emailId = null, ?int $listId = null): void
    {
        $query = DB::table('emails')
            ->leftJoin('email_logs', 'emails.id', '=', 'email_logs.email_id')
            ->select([
                'emails.id',
                'emails.email',
                'emails.status',
                'emails.email_status',
                'emails.email_score',
                'emails.email_risk_level',
                'emails.is_role_based',
                'emails.is_disposable',
                'emails.is_catch_all',
                'emails.has_typo',
                'emails.subscription_status',
                'emails.bounce_count',
                'emails.complaint_count',
                'emails.whatsapp_number',
                'emails.whatsapp_opt_in',
                'emails.whatsapp_subscription_status',
                'emails.whatsapp_last_message_at',
                DB::raw('COUNT(email_logs.id) as logs_count'),
                DB::raw('SUM(COALESCE(email_logs.open_count, 0)) as total_opens'),
                DB::raw('SUM(COALESCE(email_logs.click_count, 0)) as total_clicks'),
                DB::raw('MAX(email_logs.sent_at) as last_sent'),
                DB::raw('MAX(CASE WHEN email_logs.open_count > 0 OR email_logs.click_count > 0 THEN email_logs.last_open_at END) as last_opened'),
                DB::raw('MAX(CASE WHEN email_logs.click_count > 0 THEN email_logs.clicked_at END) as last_clicked'),
            ]);

        if ($emailId) {
            $query->where('emails.id', $emailId);
        }

        if ($listId) {
            $query->where('emails.email_list_id', $listId);
        }

        $query->groupBy('emails.id')
            ->orderBy('emails.id');

        $query->chunk(500, function ($contacts) {
            $now = Carbon::now();
            $sevenDaysAgo = Carbon::now()->subDays(7);
            $thirtyDaysAgo = Carbon::now()->subDays(30);

            foreach ($contacts as $contact) {
                $segments = [];

                // 1. Sent Status
                if (!empty($contact->email)) {
                    if ((int)$contact->logs_count === 0) {
                        $segments[] = 'Auto: Never Sent';
                    } else {
                        if ($contact->last_sent) {
                            $lastSent = Carbon::parse($contact->last_sent);
                            if ($lastSent->greaterThanOrEqualTo($sevenDaysAgo)) {
                                $segments[] = 'Auto: Recently Sent (7d)';
                            }
                            if ($lastSent->greaterThanOrEqualTo($thirtyDaysAgo)) {
                                $segments[] = 'Auto: Recently Sent (30d)';
                            }
                        }
                    }

                    // 2. Open Status
                    if ((int)$contact->logs_count > 0 && (int)$contact->total_opens === 0) {
                        $segments[] = 'Auto: Never Opened';
                    } else if ((int)$contact->total_opens > 0) {
                        if ($contact->last_opened) {
                            $lastOpened = Carbon::parse($contact->last_opened);
                            if ($lastOpened->greaterThanOrEqualTo($sevenDaysAgo)) {
                                $segments[] = 'Auto: Recent Opener (7d)';
                            }
                            if ($lastOpened->greaterThanOrEqualTo($thirtyDaysAgo)) {
                                $segments[] = 'Auto: Active Opener (30d)';
                            } else {
                                $segments[] = 'Auto: Inactive Opener';
                            }
                        }
                    }

                    // 3. Click Status
                    if ((int)$contact->logs_count > 0 && (int)$contact->total_clicks === 0) {
                        $segments[] = 'Auto: Never Clicked';
                    } else if ((int)$contact->total_clicks > 0) {
                        if ($contact->last_clicked) {
                            $lastClicked = Carbon::parse($contact->last_clicked);
                            if ($lastClicked->greaterThanOrEqualTo($sevenDaysAgo)) {
                                $segments[] = 'Auto: Recent Clicker (7d)';
                            }
                            if ($lastClicked->greaterThanOrEqualTo($thirtyDaysAgo)) {
                                $segments[] = 'Auto: Active Clicker (30d)';
                            }
                        }
                    }

                    // Highly Engaged
                    if ((int)$contact->total_opens >= 5 || (int)$contact->total_clicks >= 2) {
                        $segments[] = 'Auto: Highly Engaged';
                    }

                    // Health/Validation
                    if (in_array($contact->email_status, ['hard_bounce', 'soft_bounce']) || (int)$contact->bounce_count > 0) {
                        $segments[] = 'Auto: Bounced Contact';
                    }
                    if ($contact->email_status === 'hard_bounce') {
                        $segments[] = 'Auto: Hard Bounce';
                    }
                    if ($contact->email_status === 'soft_bounce') {
                        $segments[] = 'Auto: Soft Bounce';
                    }
                    if ($contact->subscription_status === 'unsubscribed') {
                        $segments[] = 'Auto: Unsubscribed Contact';
                    }
                    if ($contact->email_status === 'complaint' || (int)$contact->complaint_count > 0) {
                        $segments[] = 'Auto: Spam Reporter';
                    }
                    if ($contact->email_status === 'risky' || $contact->email_risk_level === 'high') {
                        $segments[] = 'Auto: Risky Contact';
                    }
                    if ($contact->is_disposable) {
                        $segments[] = 'Auto: Disposable Email';
                    }
                    if ($contact->is_role_based) {
                        $segments[] = 'Auto: Role-Based Email';
                    }
                    if ($contact->is_catch_all) {
                        $segments[] = 'Auto: Catch-All Email';
                    }
                    if ($contact->has_typo) {
                        $segments[] = 'Auto: Typo Email';
                    }
                    if ($contact->status === 'valid') {
                        $segments[] = 'Auto: Valid Email';
                    }
                    if ($contact->status === 'invalid') {
                        $segments[] = 'Auto: Invalid Email';
                    }
                    if ($contact->email_score >= 4) {
                        $segments[] = 'Auto: High Quality Email';
                    }
                    if ($contact->email_score <= 2) {
                        $segments[] = 'Auto: Low Quality Email';
                    }
                }

                // WhatsApp
                if ($contact->whatsapp_number) {
                    $segments[] = 'Auto: Has WhatsApp';
                    if ($contact->whatsapp_opt_in || $contact->whatsapp_subscription_status === 'subscribed') {
                        $segments[] = 'Auto: WhatsApp Opted-In';
                    }
                    if ($contact->whatsapp_subscription_status === 'unsubscribed') {
                        $segments[] = 'Auto: WhatsApp Opted-Out';
                    }
                    if ($contact->whatsapp_last_message_at) {
                        $waLast = Carbon::parse($contact->whatsapp_last_message_at);
                        if ($waLast->greaterThanOrEqualTo($thirtyDaysAgo)) {
                            $segments[] = 'Auto: Active WhatsApp (30d)';
                        }
                    } else {
                        $segments[] = 'Auto: Never Messaged on WhatsApp';
                    }
                }

                DB::table('emails')
                    ->where('id', $contact->id)
                    ->update(['auto_segments' => json_encode($segments)]);
            }
        });
    }
}
