@extends('layouts.app')
@section('title', 'Dashboard Overview')
@section('heading', 'Dashboard')

@section('content')
<div class="space-y-8 animate-slide-up">

    {{-- ── CRM & Mailing Stat Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Contacts --}}
        <div class="stat-card glass-card-hover group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-1">Total Contacts</p>
                    <h3 class="text-3xl font-black text-surface-900">{{ number_format($totalContacts) }}</h3>
                </div>
                <div class="stat-card-icon bg-primary-50 text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-surface-400 mt-4 font-bold uppercase tracking-tighter">Database Volume</p>
        </div>

        {{-- Avg Open Rate --}}
        <div class="stat-card glass-card-hover group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Avg Open Rate</p>
                    <h3 class="text-3xl font-black text-surface-900">{{ $globalOpenRate }}%</h3>
                </div>
                <div class="stat-card-icon bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-emerald-600 mt-4 font-bold uppercase tracking-tighter">Campaign Engagement</p>
        </div>

        {{-- Total Opens --}}
        <div class="stat-card glass-card-hover group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-1">Total Opens</p>
                    <h3 class="text-3xl font-black text-surface-900">{{ number_format($totalOpens) }}</h3>
                </div>
                <div class="stat-card-icon bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-indigo-600 mt-4 font-bold uppercase tracking-tighter">Historical Reach</p>
        </div>

        {{-- Total Clicks --}}
        <div class="stat-card glass-card-hover group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black text-violet-500 uppercase tracking-widest mb-1">Total Clicks</p>
                    <h3 class="text-3xl font-black text-surface-900">{{ number_format($totalClicks) }}</h3>
                </div>
                <div class="stat-card-icon bg-violet-50 text-violet-600 group-hover:bg-violet-600 group-hover:text-white transition-colors duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <p class="text-[10px] text-violet-600 mt-4 font-bold uppercase tracking-tighter">Conversion Power</p>
        </div>
    </div>

    {{-- ── Middle Section: Usage & Cost ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- Sending Limits --}}
        <div class="glass-card p-8">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-lg font-bold text-surface-900">Mailing Infrastructure</h3>
                <span class="text-xs font-bold text-primary-600 bg-primary-50 px-2 py-1 rounded">Live Status</span>
            </div>
            <div class="space-y-6">
                @foreach(['daily', 'weekly', 'monthly'] as $period)
                @php $stat = $usageStats[$period]; @endphp
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-surface-700 capitalize">{{ $period }} Allowance</span>
                        <span class="text-xs font-bold text-surface-500">{{ number_format($stat['sent']) }} / {{ number_format($stat['limit']) }}</span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar-fill" style="width: {{ $stat['percent'] }}%; background: {{ $stat['percent'] > 85 ? 'linear-gradient(90deg, #ef4444, #f87171)' : 'linear-gradient(90deg, #6366f1, #818cf8)' }}"></div>
                    </div>
                    <div class="flex justify-between mt-2">
                        <p class="text-[10px] text-surface-400 uppercase tracking-widest font-bold">{{ number_format($stat['remaining']) }} Left</p>
                        <p class="text-[10px] text-surface-400 font-bold">{{ round($stat['percent']) }}%</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Financial Estimates --}}
        <div class="glass-card p-8">
            <h3 class="text-lg font-bold text-surface-900 mb-8">Cost Breakdown</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-5 rounded-xl border border-surface-100 bg-surface-50/50">
                    <p class="text-[10px] text-surface-400 font-bold uppercase tracking-wider">Unit Cost</p>
                    <p class="text-2xl font-black text-surface-900 mt-1">${{ number_format($costBreakdown['cost_per_email'], 4) }}</p>
                </div>
                <div class="p-5 rounded-xl border border-emerald-100 bg-emerald-50/30">
                    <p class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">Today</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1">${{ number_format($costBreakdown['daily_cost'], 2) }}</p>
                </div>
                <div class="p-5 rounded-xl border border-indigo-100 bg-indigo-50/30">
                    <p class="text-[10px] text-indigo-600 font-bold uppercase tracking-wider">This Week</p>
                    <p class="text-2xl font-black text-indigo-700 mt-1">${{ number_format($costBreakdown['weekly_cost'], 2) }}</p>
                </div>
                <div class="p-5 rounded-xl border border-purple-100 bg-purple-50/30">
                    <p class="text-[10px] text-purple-600 font-bold uppercase tracking-wider">This Month</p>
                    <p class="text-2xl font-black text-purple-700 mt-1">${{ number_format($costBreakdown['monthly_cost'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Bottom Tables ── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        {{-- Recent Campaigns --}}
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-surface-900">Recent Campaigns</h3>
                <a href="{{ route('admin.campaigns.index') }}" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Metrics</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentCampaigns as $campaign)
                        <tr>
                            <td>
                                <div class="font-bold text-surface-900">{{ $campaign->name }}</div>
                                <div class="text-[10px] text-surface-400">{{ $campaign->created_at->format('M d, Y') }}</div>
                            </td>
                            <td>
                                @php
                                    $statusClasses = [
                                        'draft' => 'badge-info',
                                        'scheduled' => 'badge-info',
                                        'sending' => 'badge-warning',
                                        'paused' => 'badge-warning',
                                        'completed' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $statusClasses[$campaign->status] ?? 'badge-neutral' }}">{{ ucfirst($campaign->status) }}</span>
                            </td>
                            <td>
                                <div class="text-xs font-bold text-surface-700">{{ number_format($campaign->sent_count) }} sent</div>
                                <div class="text-[10px] text-surface-400">of {{ number_format($campaign->total_recipients) }}</div>
                            </td>
                            <td class="w-24">
                                <div class="progress-container h-1.5">
                                    <div class="progress-bar-fill" style="width: {{ $campaign->progress() }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Lists --}}
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-surface-900">Latest Lists</h3>
                <a href="{{ route('admin.email-lists.create') }}" class="btn btn-primary btn-sm">Import New</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>List Name</th>
                            <th>Total</th>
                            <th>Valid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLists as $list)
                        <tr>
                            <td>
                                <div class="font-bold text-surface-900">{{ $list->name }}</div>
                                <div class="text-[10px] text-surface-400">{{ $list->original_filename }}</div>
                            </td>
                            <td class="font-bold text-surface-700">{{ number_format($list->total_records) }}</td>
                            <td class="font-bold text-emerald-600">{{ number_format($list->valid_count) }}</td>
                            <td>
                                @php
                                    $listStatusClasses = [
                                        'pending' => 'badge-neutral',
                                        'processing' => 'badge-warning',
                                        'completed' => 'badge-success',
                                        'failed' => 'badge-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $listStatusClasses[$list->status] ?? 'badge-neutral' }}">{{ ucfirst($list->status) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
