@extends('layouts.app')
@section('title', 'Campaign Report: ' . $campaign->name)
@section('heading', 'Campaign Intelligence Report')

@section('header-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-ghost btn-sm">Back to Campaign</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">Download PDF</button>
    </div>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up">
    {{-- ── Campaign Summary ── --}}
    <div class="glass-card p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-3xl font-black text-surface-900 mb-1">{{ $campaign->name }}</h2>
                <p class="text-surface-500 font-medium">Sent on {{ $campaign->completed_at ? $campaign->completed_at->format('M d, Y \a\t h:i A') : 'Ongoing' }}</p>
            </div>
            <div class="flex gap-4">
                <div class="text-center px-6 border-r border-surface-100">
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Open Rate</p>
                    <p class="text-2xl font-black text-emerald-600">{{ $stats['sent'] > 0 ? round(($stats['unique_opens'] / $stats['sent']) * 100, 1) : 0 }}%</p>
                </div>
                <div class="text-center px-6">
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Click Rate</p>
                    <p class="text-2xl font-black text-indigo-600">{{ $stats['sent'] > 0 ? round(($stats['unique_clicks'] / $stats['sent']) * 100, 1) : 0 }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Funnel Stats ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="stat-card">
            <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Total Sent</p>
            <p class="text-2xl font-black text-surface-900 mt-1">{{ number_format($stats['sent']) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">Total Opens</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['opens']) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">Total Clicks</p>
            <p class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($stats['clicks']) }}</p>
        </div>
        <div class="stat-card">
            <p class="text-[10px] font-black text-red-500 uppercase tracking-widest">Bounces/Failed</p>
            <p class="text-2xl font-black text-red-600 mt-1">{{ number_format($stats['failed']) }}</p>
        </div>
        <div class="stat-card border-l-4 border-red-500 bg-red-50/20">
            <p class="text-[10px] font-black text-red-700 uppercase tracking-widest">Unsubscribes</p>
            <p class="text-2xl font-black text-red-700 mt-1">{{ number_format($stats['unsubscribes']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Link Tracking --}}
        <div class="lg:col-span-1 glass-card p-8">
            <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest mb-6">Top Clicked Links</h3>
            <div class="space-y-6">
                @forelse($topLinks as $link)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-surface-600 truncate max-w-[200px]" title="{{ $link->url }}">{{ $link->url }}</span>
                        <span class="text-xs font-black text-indigo-600">{{ $link->count }} clicks</span>
                    </div>
                    <div class="h-1.5 w-full bg-surface-100 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500" style="width: {{ ($link->count / max($stats['clicks'], 1)) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <div class="text-center py-10 opacity-50 italic text-sm">No link activity recorded.</div>
                @endforelse
            </div>
        </div>

        {{-- Activity Logs --}}
        <div class="lg:col-span-2 glass-card overflow-hidden">
            <div class="p-6 border-b border-surface-100 bg-surface-50/50 flex items-center justify-between">
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest">Detailed Logs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Status</th>
                            <th>Opens</th>
                            <th>Clicks</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr class="group hover:bg-surface-50/50 transition-colors">
                            <td>
                                <div class="font-bold text-surface-900">{{ $log->email->name ?? '—' }}</div>
                                <div class="text-xs text-surface-400">{{ $log->email_address }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $log->status === 'sent' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-xs font-black text-emerald-600">{{ $campaign->activities()->where('email_id', $log->email_id)->where('type', 'opened')->count() }}</span>
                            </td>
                            <td>
                                <span class="text-xs font-black text-indigo-600">{{ $campaign->activities()->where('email_id', $log->email_id)->where('type', 'clicked')->count() }}</span>
                            </td>
                            <td class="text-xs text-surface-400 font-medium">
                                {{ $log->sent_at ? $log->sent_at->diffForHumans() : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-surface-100 bg-surface-50/30">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
