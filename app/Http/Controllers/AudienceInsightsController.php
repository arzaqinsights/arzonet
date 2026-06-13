<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AudienceInsightsController extends Controller
{
    public function index()
    {
        $listId = session('last_opened_list_id');
        $emailList = null;
        if ($listId) {
            $emailList = EmailList::find($listId);
        }
        if (!$emailList) {
            $emailList = EmailList::orderBy('name')->first();
            if ($emailList) {
                session(['last_opened_list_id' => $emailList->id]);
            } else {
                return redirect()->route('admin.email-lists.create')
                    ->with('error', 'Please create a list first.');
            }
        }

        // 1. Growth Trend (Last 30 Days)
        $growthData = DB::table('emails')
            ->where('email_list_id', $emailList->id)
            ->where('is_archived', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'date' => $date,
                'count' => $growthData[$date] ?? 0
            ];
        }

        // 2. Verification Status Breakdown
        $verificationStats = DB::table('emails')
            ->where('email_list_id', $emailList->id)
            ->where('is_archived', false)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 3. Opt-in Status Distribution
        $subscriptionStats = DB::table('emails')
            ->where('email_list_id', $emailList->id)
            ->where('is_archived', false)
            ->selectRaw('subscription_status, count(*) as count')
            ->groupBy('subscription_status')
            ->pluck('count', 'subscription_status')
            ->toArray();

        // 4. Geographic Distribution (using IP address from email events)
        $geoData = DB::table('email_events')
            ->join('email_logs', 'email_events.email_log_id', '=', 'email_logs.id')
            ->join('emails', 'email_logs.email_id', '=', 'emails.id')
            ->where('emails.email_list_id', $emailList->id)
            ->whereNotNull('email_events.ip_address')
            ->where('email_events.ip_address', '!=', '')
            ->selectRaw('email_events.ip_address, count(*) as event_count')
            ->groupBy('email_events.ip_address')
            ->orderByDesc('event_count')
            ->take(10)
            ->get();

        // 5. Top Engaged Contacts (sorted by Lead Score)
        $topLeads = $emailList->emails()
            ->where('is_archived', false)
            ->orderByDesc('email_lead_score')
            ->take(10)
            ->get();

        // 6. Churn Rate (Unsubscribes per month - Last 6 Months)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthExpr = $isSqlite ? "strftime('%Y-%m', unsubscribed_at)" : "DATE_FORMAT(unsubscribed_at, '%Y-%m')";
        
        $churnData = DB::table('emails')
            ->where('email_list_id', $emailList->id)
            ->where('subscription_status', 'unsubscribed')
            ->whereNotNull('unsubscribed_at')
            ->where('unsubscribed_at', '>=', now()->subMonths(6))
            ->selectRaw("{$monthExpr} as month, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Generate last 6 months array
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $churnTrend = [];
        foreach ($months as $month) {
            $formattedMonth = \Carbon\Carbon::parse($month . '-01')->format('M Y');
            $churnTrend[] = [
                'month' => $formattedMonth,
                'count' => $churnData[$month] ?? 0
            ];
        }

        // 7. Top Sources Breakdown
        $topSources = DB::table('emails')
            ->where('email_list_id', $emailList->id)
            ->where('is_archived', false)
            ->selectRaw("CASE WHEN signup_source IS NULL OR signup_source = '' THEN 'Direct / Import' ELSE signup_source END as source, count(*) as count")
            ->groupBy('source')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->toArray();

        // 8. Bounce/Complaint Trend (Last 6 Months)
        $activityMonthExpr = $isSqlite ? "strftime('%Y-%m', contact_activities.created_at)" : "DATE_FORMAT(contact_activities.created_at, '%Y-%m')";
        
        $bounceComplaintData = DB::table('contact_activities')
            ->join('emails', 'contact_activities.email_id', '=', 'emails.id')
            ->where('emails.email_list_id', $emailList->id)
            ->whereIn('contact_activities.type', ['bounced', 'complaint'])
            ->where('contact_activities.created_at', '>=', now()->subMonths(6))
            ->selectRaw("contact_activities.type as type, {$activityMonthExpr} as month, count(*) as count")
            ->groupBy('type', 'month')
            ->orderBy('month')
            ->get();

        $monthlyBounces = [];
        $monthlyComplaints = [];
        foreach ($months as $month) {
            $formattedMonth = \Carbon\Carbon::parse($month . '-01')->format('M Y');
            $bounceCount = 0;
            $complaintCount = 0;
            foreach ($bounceComplaintData as $item) {
                if ($item->month === $month) {
                    if ($item->type === 'bounced') $bounceCount = $item->count;
                    if ($item->type === 'complaint') $complaintCount = $item->count;
                }
            }
            $monthlyBounces[] = [
                'month' => $formattedMonth,
                'count' => $bounceCount
            ];
            $monthlyComplaints[] = [
                'month' => $formattedMonth,
                'count' => $complaintCount
            ];
        }

        // 9. Monthly New Subscribers Acquisition (Last 6 Months)
        $createdMonthExpr = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        
        $newSubsData = DB::table('emails')
            ->where('email_list_id', $emailList->id)
            ->where('is_archived', false)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("{$createdMonthExpr} as month, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $monthlyNewSubscribers = [];
        foreach ($months as $month) {
            $formattedMonth = \Carbon\Carbon::parse($month . '-01')->format('M Y');
            $monthlyNewSubscribers[] = [
                'month' => $formattedMonth,
                'count' => $newSubsData[$month] ?? 0
            ];
        }

        // 10. Engagement Rate (Percentage of contacts active in the last 30 days)
        $totalContacts = $emailList->emails()->where('is_archived', false)->count();
        $engagedContacts = $emailList->emails()
            ->where('is_archived', false)
            ->where(function($q) {
                $q->where('last_engaged_at', '>=', now()->subDays(30))
                  ->orWhere('last_active_at', '>=', now()->subDays(30));
            })
            ->count();
        $engagementRate = $totalContacts > 0 ? round(($engagedContacts / $totalContacts) * 100, 1) : 0;

        return view('crm.insights.index', compact(
            'emailList',
            'trend',
            'verificationStats',
            'subscriptionStats',
            'geoData',
            'topLeads',
            'churnTrend',
            'topSources',
            'monthlyBounces',
            'monthlyComplaints',
            'monthlyNewSubscribers',
            'engagementRate'
        ));
    }
}
