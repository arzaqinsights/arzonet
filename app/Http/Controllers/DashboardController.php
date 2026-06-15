<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\EmailList;
use App\Models\Template;
use App\Models\EmailLog;
use App\Services\CostEstimationService;
use App\Services\UsageTrackingService;
use App\Models\ContactActivity;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(UsageTrackingService $usageService)
    {
        $activeListId = session('last_opened_list_id');

        // ── 1. CORE PERFORMANCE METRICS (Consolidated Queries) ──
        $logQuery = \App\Models\EmailLog::query();
        if ($activeListId) {
            $logQuery->whereHas('campaign', function($q) use ($activeListId) {
                $q->where('email_list_id', $activeListId);
            });
        }
        $logStats = $logQuery->selectRaw("
            SUM(CASE WHEN status NOT IN ('pending') THEN 1 ELSE 0 END) as total_sent,
            SUM(CASE WHEN status IN ('sent','delivered','processed','opened','clicked') THEN 1 ELSE 0 END) as total_delivered,
            SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as total_bounced,
            SUM(CASE WHEN status = 'complaint' THEN 1 ELSE 0 END) as total_complaints,
            SUM(CASE WHEN open_count > 0 OR click_count > 0 THEN 1 ELSE 0 END) as total_opens,
            SUM(CASE WHEN click_count > 0 THEN 1 ELSE 0 END) as total_clicks
        ")->first();

        $contactQuery = \App\Models\Email::query();
        if ($activeListId) {
            $contactQuery->where('email_list_id', $activeListId);
        }
        $contactStats = $contactQuery->selectRaw("
            COUNT(*) as total_contacts,
            SUM(CASE WHEN unsubscribed_at IS NOT NULL THEN 1 ELSE 0 END) as total_unsubscribed,
            SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) as valid_contacts,
            SUM(CASE WHEN whatsapp_number IS NOT NULL THEN 1 ELSE 0 END) as wa_total,
            SUM(CASE WHEN whatsapp_number IS NOT NULL AND whatsapp_subscription_status = 'subscribed' THEN 1 ELSE 0 END) as wa_subscribed
        ")->first();

        $totalContacts = (int) $contactStats->total_contacts;
        $totalSent = (int) $logStats->total_sent;
        $totalDelivered = (int) $logStats->total_delivered;
        $totalBounced = (int) $logStats->total_bounced;
        $totalOpens = (int) $logStats->total_opens;
        $totalClicks = (int) $logStats->total_clicks;
        $totalUnsubscribed = (int) $contactStats->total_unsubscribed;

        $globalOpenRate = $totalDelivered > 0 ? round(($totalOpens / max($totalDelivered, $totalOpens)) * 100, 1) : 0;
        $globalClickRate = $totalDelivered > 0 ? round(($totalClicks / max($totalDelivered, $totalClicks)) * 100, 1) : 0;
        $bounceRate = $totalSent > 0 ? round(($totalBounced / $totalSent) * 100, 1) : 0;

        // ── 2. HYBRID PERFORMANCE TRENDS (Fall-back to Logs if Stats empty) ──
        $chartData = $usageService->getLast30DaysData();
        $hasStatsData = collect($chartData)->sum('sent') > 0;
        
        if (!$hasStatsData) {
            // Generate from logs if UsageStat table is empty (Migration/Legacy data)
            $logTrendsQuery = \App\Models\EmailLog::where('created_at', '>=', now()->subDays(30));
            if ($activeListId) {
                $logTrendsQuery->whereHas('campaign', function($q) use ($activeListId) {
                    $q->where('email_list_id', $activeListId);
                });
            }
            $logTrends = $logTrendsQuery
                ->select(\DB::raw('DATE(created_at) as date'), 
                         \DB::raw('count(*) as sent'),
                         \DB::raw('count(CASE WHEN status = "failed" OR status = "bounced" THEN 1 END) as failed'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $chartData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $dayLog = $logTrends->get($date);
                $chartData[] = [
                    'date' => $date,
                    'sent' => $dayLog->sent ?? 0,
                    'failed' => $dayLog->failed ?? 0,
                    'cost' => ($dayLog->sent ?? 0) * 0.0001
                ];
            }
        }

        // ── 3. ISP HEALTH & DOMAIN PERFORMANCE ──
        $ispQuery = \App\Models\EmailLog::query();
        if ($activeListId) {
            $ispQuery->whereHas('campaign', function($q) use ($activeListId) {
                $q->where('email_list_id', $activeListId);
            });
        }
        $ispPerformance = $ispQuery->select(\DB::raw('SUBSTRING_INDEX(email_address, "@", -1) as domain'), 
                                \DB::raw('count(*) as total'),
                                \DB::raw('count(CASE WHEN status IN ("sent", "delivered", "processed", "opened", "clicked") THEN 1 END) as delivered'))
            ->groupBy('domain')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function($item) {
                $item->delivery_rate = $item->total > 0 ? round(($item->delivered / $item->total) * 100, 1) : 0;
                return $item;
            });

        // ── 4. HOURLY ENGAGEMENT HEATMAP ──
        $activityQuery = \App\Models\ContactActivity::where('type', 'opened')
            ->where('created_at', '>=', now()->subDays(7));
        if ($activeListId) {
            $activityQuery->whereHas('email', function($q) use ($activeListId) {
                $q->where('email_list_id', $activeListId);
            });
        }
        $hourlyStats = $activityQuery
            ->select(\DB::raw('HOUR(created_at) as hour'), \DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->get()
            ->pluck('count', 'hour');
            
        if ($hourlyStats->isEmpty()) {
            // Fallback to EmailLog first_open_at for historical engagement timing
            $logHourlyQuery = \App\Models\EmailLog::whereNotNull('first_open_at')
                ->where('first_open_at', '>=', now()->subDays(7));
            if ($activeListId) {
                $logHourlyQuery->whereHas('campaign', function($q) use ($activeListId) {
                    $q->where('email_list_id', $activeListId);
                });
            }
            $hourlyStats = $logHourlyQuery
                ->select(\DB::raw('HOUR(first_open_at) as hour'), \DB::raw('count(*) as count'))
                ->groupBy('hour')
                ->get()
                ->pluck('count', 'hour');
        }

        // ── 5. RECENT CAMPAIGNS & LISTS ──
        $campaignQuery = \App\Models\Campaign::with(['emailList', 'template']);
        if ($activeListId) {
            $campaignQuery->where('email_list_id', $activeListId);
        }
        $recentCampaigns = $campaignQuery
            ->latest()
            ->take(5)
            ->get();

        $topLinksQuery = \App\Models\ContactActivity::where('type', 'clicked')
            ->whereNotNull('url');
        if ($activeListId) {
            $topLinksQuery->whereHas('email', function($q) use ($activeListId) {
                $q->where('email_list_id', $activeListId);
            });
        }
        $topLinks = $topLinksQuery
            ->select('url', \DB::raw('count(*) as clicks'))
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->take(5)
            ->get();

        $usageStats = $usageService->getUsageStats();

        // ── 6. AUDIENCE HEALTH ──
        $validContacts = (int) $contactStats->valid_contacts;
        $validPercent = $totalContacts > 0 ? round(($validContacts / $totalContacts) * 100) : 0;
        $invalidPercent = 100 - $validPercent;

        // ── 7. ADVANCED STATS (WHATSAPP & TEAM) ──
        $waTotalContacts = (int) $contactStats->wa_total;
        $waSubscribed = (int) $contactStats->wa_subscribed;
        $ownerId = auth()->user()->getOwnerId();
        $teamMembersCount = \App\Models\User::where('parent_id', $ownerId)->count();
        $owner = \App\Models\User::find($ownerId);
        $teamLimit = $owner ? $owner->getTeamLimit() : 0;

        // ── 8. SENDER REPUTATION & COMPLAINTS ──
        $totalComplaints = (int) $logStats->total_complaints;
        $complaintRate = $totalSent > 0 ? ($totalComplaints / $totalSent) * 100 : 0;
        // Formula: 100 - (BounceRate * 2) - (ComplaintRate * 5)
        $emailReputation = max(0, min(100, round(100 - ($bounceRate * 2) - ($complaintRate * 5))));

        // WhatsApp Reputation
        $waSentQuery = \App\Models\WhatsAppMessage::where('direction', 'outbound');
        $waFailedQuery = \App\Models\WhatsAppMessage::where('direction', 'outbound')->whereIn('status', ['failed', 'undelivered']);
        if ($activeListId) {
            $waSentQuery->whereHas('contact', function($q) use ($activeListId) {
                $q->where('email_list_id', $activeListId);
            });
            $waFailedQuery->whereHas('contact', function($q) use ($activeListId) {
                $q->where('email_list_id', $activeListId);
            });
        }
        $waTotalSent = $waSentQuery->count();
        $waFailed = $waFailedQuery->count();
        $waBounceRate = $waTotalSent > 0 ? ($waFailed / $waTotalSent) * 100 : 0;
        $waReputation = max(0, min(100, round(100 - ($waBounceRate * 2))));

        return view('dashboard.index', compact(
            'totalSent', 'totalDelivered', 'totalBounced', 'totalOpens', 'totalClicks',
            'totalContacts', 'totalUnsubscribed', 'globalOpenRate', 'globalClickRate',
            'bounceRate', 'chartData', 'ispPerformance', 'hourlyStats', 'recentCampaigns',
            'topLinks', 'usageStats', 'validPercent', 'invalidPercent',
            'waTotalContacts', 'waSubscribed', 'teamMembersCount', 'teamLimit',
            'totalComplaints', 'complaintRate', 'emailReputation', 'waTotalSent', 'waFailed', 'waBounceRate', 'waReputation'
        ));
    }
}