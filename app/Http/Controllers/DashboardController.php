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
        // ── 1. CORE PERFORMANCE METRICS ──
        $totalContacts = \App\Models\Email::count();
        $totalSent = EmailLog::count();
        $totalDelivered = EmailLog::where('status', 'sent')->count();
        $totalBounced = EmailLog::where('status', 'bounced')->count();
        $totalOpens = ContactActivity::where('type', 'opened')->count();
        $totalClicks = ContactActivity::where('type', 'clicked')->count();
        $totalUnsubscribed = \App\Models\Email::whereNotNull('unsubscribed_at')->count();

        $globalOpenRate = $totalDelivered > 0 ? round(($totalOpens / $totalDelivered) * 100, 1) : 0;
        $globalClickRate = $totalOpens > 0 ? round(($totalClicks / $totalOpens) * 100, 1) : 0;
        $bounceRate = $totalSent > 0 ? round(($totalBounced / $totalSent) * 100, 1) : 0;

        // ── 2. HYBRID PERFORMANCE TRENDS (Fall-back to Logs if Stats empty) ──
        $chartData = $usageService->getLast30DaysData();
        $hasStatsData = collect($chartData)->sum('sent') > 0;
        
        if (!$hasStatsData) {
            // Generate from logs if UsageStat table is empty (Migration/Legacy data)
            $logTrends = EmailLog::where('created_at', '>=', now()->subDays(30))
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
        $ispPerformance = EmailLog::select(\DB::raw('SUBSTRING_INDEX(email_address, "@", -1) as domain'), 
                                \DB::raw('count(*) as total'),
                                \DB::raw('count(CASE WHEN status = "sent" THEN 1 END) as delivered'))
            ->groupBy('domain')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function($item) {
                $item->delivery_rate = $item->total > 0 ? round(($item->delivered / $item->total) * 100, 1) : 0;
                return $item;
            });

        // ── 4. HOURLY ENGAGEMENT HEATMAP ──
        $hourlyStats = ContactActivity::where('type', 'opened')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(\DB::raw('HOUR(created_at) as hour'), \DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour');

        // ── 5. RECENT CAMPAIGNS & LISTS ──
        $recentCampaigns = Campaign::with(['emailList', 'template'])
            ->latest()
            ->take(5)
            ->get();

        $topLinks = ContactActivity::where('type', 'clicked')
            ->whereNotNull('url')
            ->select('url', \DB::raw('count(*) as clicks'))
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->take(5)
            ->get();

        $usageStats = $usageService->getUsageStats();

        // ── 6. AUDIENCE HEALTH ──
        $validContacts = \App\Models\Email::where('status', 'valid')->count();
        $validPercent = $totalContacts > 0 ? round(($validContacts / $totalContacts) * 100) : 0;
        $invalidPercent = 100 - $validPercent;

        return view('dashboard.index', compact(
            'totalSent', 'totalDelivered', 'totalBounced', 'totalOpens', 'totalClicks',
            'totalContacts', 'totalUnsubscribed', 'globalOpenRate', 'globalClickRate',
            'bounceRate', 'chartData', 'ispPerformance', 'hourlyStats', 'recentCampaigns',
            'topLinks', 'usageStats', 'validPercent', 'invalidPercent'
        ));
    }
}
