<?php

namespace App\Http\Controllers;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CRMReportController extends Controller
{
    private function getActiveListId()
    {
        return session('last_opened_list_id') ?? \App\Models\EmailList::orderBy('id', 'asc')->first()->id ?? 1;
    }

    public function index(Request $request)
    {
        $activeWorkspaceId = $this->getActiveListId();

        $pipelinesQuery = Pipeline::where('email_list_id', $activeWorkspaceId);
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            $pipelinesQuery->where(function ($q) use ($teamUserId) {
                $q->where('is_public', true)
                  ->orWhere('created_by_id', $teamUserId);
            });
        }
        $pipelines = $pipelinesQuery->latest()->get();

        $pipelineId = $request->input('pipeline_id');
        $pipeline = null;
        if ($pipelineId) {
            $pipeline = $pipelines->firstWhere('id', $pipelineId);
        }
        if (!$pipeline) {
            $pipeline = $pipelines->first();
        }

        if (!$pipeline) {
            return view('crm.reports.index', [
                'pipelines' => $pipelines,
                'pipeline' => null,
            ]);
        }

        $pipeline->load(['stages.deals.contact', 'stages.deals.assignee']);
        $allDeals = $pipeline->deals;

        // General stats
        $totalDeals = $allDeals->count();
        $wonDeals = $allDeals->where('status', 'won');
        $lostDeals = $allDeals->where('status', 'lost');
        $openDeals = $allDeals->where('status', 'open');

        $wonCount = $wonDeals->count();
        $lostCount = $lostDeals->count();
        $openCount = $openDeals->count();

        $wonValue = (float) $wonDeals->sum('value');
        $lostValue = (float) $lostDeals->sum('value');
        $openValue = (float) $openDeals->sum('value');
        $totalValue = (float) $allDeals->sum('value');

        // Win rate (won / won+lost)
        $closedDealsCount = $wonCount + $lostCount;
        $winRate = $closedDealsCount > 0 ? round(($wonCount / $closedDealsCount) * 100, 1) : 0;
        $lossRate = $closedDealsCount > 0 ? round(($lostCount / $closedDealsCount) * 100, 1) : 0;

        // Average Deal Value
        $avgDealValue = $totalDeals > 0 ? round($allDeals->avg('value'), 2) : 0;

        // Average Time to Close (Won Deals)
        $avgTimeToClose = 0;
        if ($wonCount > 0) {
            $totalDays = $wonDeals->sum(function ($deal) {
                return $deal->created_at->diffInDays($deal->updated_at);
            });
            $avgTimeToClose = round($totalDays / $wonCount, 1);
        }

        // Deal Stage Distribution
        $stageDistribution = $pipeline->stages->map(function ($stage) {
            return [
                'name' => $stage->name,
                'color' => $stage->color,
                'count' => $stage->deals->count(),
                'value' => (float) $stage->deals->sum('value'),
            ];
        });

        // Win/Loss Monthly Trend (Last 6 Months)
        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y');
            $monthKey = $monthStart->format('Y-m');

            $monthlyWon = $wonDeals->filter(function ($deal) use ($monthStart, $monthEnd) {
                return $deal->updated_at >= $monthStart && $deal->updated_at <= $monthEnd;
            });
            $monthlyLost = $lostDeals->filter(function ($deal) use ($monthStart, $monthEnd) {
                return $deal->updated_at >= $monthStart && $deal->updated_at <= $monthEnd;
            });

            $monthlyTrend->put($monthKey, [
                'label' => $monthLabel,
                'won_value' => (float) $monthlyWon->sum('value'),
                'won_count' => $monthlyWon->count(),
                'lost_value' => (float) $monthlyLost->sum('value'),
                'lost_count' => $monthlyLost->count(),
            ]);
        }

        // Expected Revenue Forecast (Next 6 Months including current)
        $monthlyForecast = collect();
        for ($i = 0; $i < 6; $i++) {
            $monthStart = now()->addMonths($i)->startOfMonth();
            $monthEnd = now()->addMonths($i)->endOfMonth();
            $monthLabel = $monthStart->format('M Y');
            $monthKey = $monthStart->format('Y-m');

            $forecastDeals = $openDeals->filter(function ($deal) use ($monthStart, $monthEnd) {
                return $deal->expected_close_at && $deal->expected_close_at >= $monthStart && $deal->expected_close_at <= $monthEnd;
            });

            $monthlyForecast->put($monthKey, [
                'label' => $monthLabel,
                'count' => $forecastDeals->count(),
                'value' => (float) $forecastDeals->sum('value'),
            ]);
        }

        // Team performance
        $teamPerformance = $allDeals->groupBy('assigned_to_id')->map(function ($deals, $userId) {
            $user = User::find($userId);
            $userName = $user ? $user->name : 'Unassigned';
            $won = $deals->where('status', 'won');
            $lost = $deals->where('status', 'lost');
            $open = $deals->where('status', 'open');
            $closed = $won->count() + $lost->count();
            return [
                'name' => $userName,
                'won_count' => $won->count(),
                'won_value' => (float) $won->sum('value'),
                'lost_count' => $lost->count(),
                'lost_value' => (float) $lost->sum('value'),
                'open_count' => $open->count(),
                'open_value' => (float) $open->sum('value'),
                'win_rate' => $closed > 0 ? round(($won->count() / $closed) * 100, 1) : 0,
            ];
        })->values()->sortByDesc('won_value');

        return view('crm.reports.index', compact(
            'pipelines',
            'pipeline',
            'totalDeals',
            'wonCount',
            'lostCount',
            'openCount',
            'wonValue',
            'lostValue',
            'openValue',
            'totalValue',
            'winRate',
            'lossRate',
            'avgDealValue',
            'avgTimeToClose',
            'stageDistribution',
            'monthlyTrend',
            'monthlyForecast',
            'teamPerformance'
        ));
    }
}
