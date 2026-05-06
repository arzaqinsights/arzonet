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
        $totalLists = EmailList::count();
        $totalTemplates = Template::count();
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where('status', 'sending')->count();

        // CRM Tracking Stats
        $totalContacts = \App\Models\Email::count();
        $totalOpens = ContactActivity::where('type', 'opened')->count();
        $totalClicks = ContactActivity::where('type', 'clicked')->count();
        $totalSent = EmailLog::count(); // Total logs represent total attempts
        $totalDelivered = EmailLog::where('status', 'sent')->count(); // Sent status usually means delivered in this system
        $totalBounced = EmailLog::where('status', 'bounced')->count();
        $totalUnsubscribed = \App\Models\Email::whereNotNull('unsubscribed_at')->count();
        
        $globalOpenRate = $totalDelivered > 0 ? round(($totalOpens / $totalDelivered) * 100, 1) : 0;
        $globalClickRate = $totalOpens > 0 ? round(($totalClicks / $totalOpens) * 100, 1) : 0;

        $recentCampaigns = Campaign::with(['emailList', 'template'])
            ->latest()
            ->take(5)
            ->get();

        $recentLists = EmailList::latest()->take(5)->get();

        $usageStats = $usageService->getUsageStats();
        $chartData = $usageService->getLast30DaysData();

        // New Analytics
        $topLinks = ContactActivity::where('type', 'clicked')
            ->whereNotNull('url')
            ->select('url', \DB::raw('count(*) as clicks'))
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->take(5)
            ->get();

        $bounceRate = $totalSent > 0 ? round(($totalBounced / $totalSent) * 100, 1) : 0;

        // Contact breakdown (Real Data)
        $contactsThisWeek = \App\Models\Email::where('created_at', '>=', now()->startOfWeek())->count();
        $validContacts = \App\Models\Email::where('status', 'valid')->count();
        $invalidContacts = \App\Models\Email::where('status', 'invalid')->count();
        $validPercent = $totalContacts > 0 ? round(($validContacts / $totalContacts) * 100) : 0;
        $invalidPercent = $totalContacts > 0 ? round(($invalidContacts / $totalContacts) * 100) : 0;

        $audienceGrowth = \App\Models\Email::where('created_at', '>=', now()->subDays(15))
            ->select(\DB::raw('DATE(created_at) as date'), \DB::raw('count(*) as count'))
            ->groupBy('date')
            ->get()
            ->pluck('count', 'date');

        // Activity Breakdown
        $activityStats = ContactActivity::select('type', \DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return view('dashboard.index', compact(
            'totalLists',
            'totalTemplates',
            'totalCampaigns',
            'activeCampaigns',
            'recentCampaigns',
            'recentLists',
            'usageStats',
            'chartData',
            'totalContacts',
            'totalOpens',
            'totalClicks',
            'totalSent',
            'totalDelivered',
            'totalBounced',
            'totalUnsubscribed',
            'globalOpenRate',
            'globalClickRate',
            'topLinks',
            'bounceRate',
            'contactsThisWeek',
            'validPercent',
            'invalidPercent',
            'audienceGrowth',
            'activityStats'
        ));
    }
}
