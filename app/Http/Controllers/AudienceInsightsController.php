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

        return view('crm.insights.index', compact(
            'emailList',
            'trend',
            'verificationStats',
            'subscriptionStats',
            'geoData',
            'topLeads'
        ));
    }
}
