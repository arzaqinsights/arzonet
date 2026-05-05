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
    public function index(UsageTrackingService $usageService, CostEstimationService $costService)
    {
        $totalLists = EmailList::count();
        $totalTemplates = Template::count();
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where('status', 'sending')->count();

        // CRM Tracking Stats
        $totalContacts = \App\Models\Email::count();
        $totalOpens = ContactActivity::where('type', 'opened')->count();
        $totalClicks = ContactActivity::where('type', 'clicked')->count();
        $totalSent = EmailLog::where('status', 'sent')->count();
        $globalOpenRate = $totalSent > 0 ? round(($totalOpens / $totalSent) * 100, 1) : 0;

        $recentCampaigns = Campaign::with(['emailList', 'template'])
            ->latest()
            ->take(5)
            ->get();

        $recentLists = EmailList::latest()->take(5)->get();

        $usageStats = $usageService->getUsageStats();
        $costBreakdown = $costService->getCostBreakdown();
        $chartData = $usageService->getLast30DaysData();

        return view('dashboard.index', compact(
            'totalLists',
            'totalTemplates',
            'totalCampaigns',
            'activeCampaigns',
            'recentCampaigns',
            'recentLists',
            'usageStats',
            'costBreakdown',
            'chartData',
            'totalContacts',
            'totalOpens',
            'totalClicks',
            'totalSent',
            'globalOpenRate'
        ));
    }
}
