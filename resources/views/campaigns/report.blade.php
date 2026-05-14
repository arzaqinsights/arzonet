@extends('layouts.app')
@section('title', 'Advanced Campaign Report')
@section('heading', 'Performance Intelligence')

@section('content')
<div class="space-y-8 animate-slide-up">
    
    <div class="glass-card p-6 rounded-md">
        <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest mb-6">Delivery Intelligence Overview</h4>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-6 mb-8 border-b border-surface-100 pb-8">
            <div class="text-center">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest block mb-2">Requests</span>
                <h3 class="text-3xl font-black text-primary-600">{{ number_format($stats['sent']) }}</h3>
            </div>
            <div class="text-center">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest block mb-2">Delivered</span>
                <h3 class="text-3xl font-black text-emerald-600">{{ number_format($stats['delivered']) }}</h3>
                <p class="text-[10px] text-emerald-500 mt-1 font-bold">{{ $stats['sent'] > 0 ? round(($stats['delivered'] / $stats['sent']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="text-center">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest block mb-2">Opened</span>
                <h3 class="text-3xl font-black text-sky-600">{{ number_format($stats['unique_opens']) }}</h3>
                <p class="text-[10px] text-sky-500 mt-1 font-bold">{{ $stats['delivered'] > 0 ? round(($stats['unique_opens'] / $stats['delivered']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="text-center">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest block mb-2">Clicked</span>
                <h3 class="text-3xl font-black text-indigo-600">{{ number_format($stats['unique_clicks']) }}</h3>
                <p class="text-[10px] text-indigo-500 mt-1 font-bold">{{ $stats['delivered'] > 0 ? round(($stats['unique_clicks'] / $stats['delivered']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="text-center">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest block mb-2">Bounces</span>
                <h3 class="text-3xl font-black text-rose-500">{{ number_format($stats['bounces']) }}</h3>
                <p class="text-[10px] text-rose-400 mt-1 font-bold">{{ $stats['sent'] > 0 ? round(($stats['bounces'] / $stats['sent']) * 100, 1) : 0 }}%</p>
            </div>
            <div class="text-center">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest block mb-2">Spam Reports</span>
                <h3 class="text-3xl font-black text-amber-500">{{ number_format($stats['spam_reports']) }}</h3>
                <p class="text-[10px] text-amber-400 mt-1 font-bold">{{ $stats['sent'] > 0 ? round(($stats['spam_reports'] / $stats['sent']) * 100, 1) : 0 }}%</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-y-6 gap-x-8">
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Total Opens</span>
                <span class="text-sm font-black text-surface-900">{{ number_format($stats['opens']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Unique Opens</span>
                <span class="text-sm font-black text-sky-600">{{ number_format($stats['unique_opens']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Total Clicks</span>
                <span class="text-sm font-black text-surface-900">{{ number_format($stats['clicks']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Unique Clicks</span>
                <span class="text-sm font-black text-indigo-600">{{ number_format($stats['unique_clicks']) }}</span>
            </div>
            
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Unsubscribes</span>
                <span class="text-sm font-black text-purple-600">{{ number_format($stats['unsubscribes']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Blocks</span>
                <span class="text-sm font-black text-orange-600">{{ number_format($stats['blocks']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Bounce Drops</span>
                <span class="text-sm font-black text-rose-400">{{ number_format($stats['drops']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Invalid Emails</span>
                <span class="text-sm font-black text-rose-700">{{ number_format($stats['invalid']) }}</span>
            </div>
            <div class="flex justify-between items-center border-b border-surface-100 pb-2">
                <span class="text-[11px] font-bold text-surface-500 uppercase">Deferred</span>
                <span class="text-sm font-black text-amber-600">{{ number_format($stats['deferred']) }}</span>
            </div>
        </div>
    </div>

    {{-- Infrastructure Performance --}}
    <div class="glass-card rounded-md overflow-hidden">
        <div class="p-6 bg-surface-50 border-b border-surface-100 flex items-center justify-between">
            <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Provider Infrastructure Performance</h4>
            <span class="text-[10px] font-bold text-surface-400">Comparing {{ $providerStats->count() }} Routing Nodes</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="!pl-8">Provider Identity</th>
                        <th class="text-center">Total Sent</th>
                        <th class="text-center">Success Rate</th>
                        <th class="text-center">Engagement</th>
                        <th class="text-right !pr-8">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($providerStats as $ps)
                    <tr class="hover:bg-surface-50/50 transition-colors">
                        <td class="!pl-8 !py-6">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-10 h-10 rounded-md flex items-center justify-center text-[10px] font-black uppercase',
                                    'bg-indigo-50 text-indigo-600' => $ps->provider === 'ses',
                                    'bg-emerald-50 text-emerald-600' => $ps->provider === 'sendgrid',
                                    'bg-surface-100 text-surface-600' => $ps->provider === 'smtp',
                                ])>
                                    {{ $ps->provider }}
                                </div>
                                <div>
                                    <p class="text-sm font-black text-surface-900">{{ $ps->sender_email }}</p>
                                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-tighter">{{ $ps->provider }} GATEWAY</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <p class="text-sm font-black text-surface-900">{{ number_format($ps->total) }}</p>
                        </td>
                        <td class="text-center">
                            @php $rate = $ps->total > 0 ? round(($ps->sent / $ps->total) * 100, 1) : 0; @endphp
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-xs font-black {{ $rate > 90 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $rate }}%</span>
                                <div class="w-16 h-1 bg-surface-100 rounded-md overflow-hidden">
                                    <div class="h-full {{ $rate > 90 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $rate }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold text-surface-600 uppercase">{{ number_format($ps->total_opens) }} Opens</span>
                                <span class="text-[10px] font-bold text-surface-400 uppercase">{{ number_format($ps->total_clicks) }} Clicks</span>
                            </div>
                        </td>
                        <td class="text-right !pr-8">
                            @if($rate > 95)
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[9px] font-black rounded-md border border-emerald-100 uppercase tracking-widest">Optimal</span>
                            @else
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-600 text-[9px] font-black rounded-md border border-amber-100 uppercase tracking-widest">Monitoring</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        {{-- Link Intelligence --}}
        <div class="glass-card rounded-md overflow-hidden">
            <div class="p-6 border-b border-surface-100">
                <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Link Engagement Intelligence</h4>
            </div>
            <div class="p-6 space-y-6">
                @forelse($topLinks as $link)
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-surface-600 truncate max-w-[250px]" title="{{ $link->url }}">{{ str_starts_with($link->url, 'http') ? $link->url : 'https://' . $link->url }}</span>
                        <span class="text-primary-600">{{ $link->count }} clicks</span>
                    </div>
                    <div class="w-full h-2 bg-surface-50 rounded-md overflow-hidden border border-surface-100">
                        <div class="h-full bg-primary-500" style="width: {{ ($link->count / max(1, $stats['clicks'])) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-surface-400 text-center py-12 italic">No link tracking data available.</p>
                @endforelse
            </div>
        </div>

        {{-- Log Registry --}}
        <div class="glass-card rounded-md overflow-hidden flex flex-col">
            <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Detailed Event Registry</h4>
                <div class="flex gap-2">
                    <span class="w-2 h-2 rounded-md bg-emerald-500" title="Sent"></span>
                    <span class="w-2 h-2 rounded-md bg-rose-500" title="Bounced"></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table !text-[11px]">
                    <thead>
                        <tr>
                            <th class="!pl-6">Recipient</th>
                            <th>Status</th>
                            <th class="text-right !pr-6">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td class="!pl-6 font-bold text-surface-900">{{ $log->email->email ?? 'Unknown' }}</td>
                            <td>
                                @php 
                                    $lcls = match($log->status) { 
                                        'delivered' => 'text-emerald-600',
                                        'sent' => 'text-primary-600', 
                                        'bounced', 'failed', 'dropped', 'invalid' => 'text-rose-600', 
                                        'blocked' => 'text-orange-600',
                                        'spamreport', 'complaint' => 'text-amber-500',
                                        'unsubscribed' => 'text-purple-600',
                                        'deferred' => 'text-amber-600',
                                        default => 'text-surface-400' 
                                    }; 
                                @endphp
                                <span class="font-black uppercase tracking-widest {{ $lcls }}">{{ $log->status }}</span>
                                @if($log->error_message)
                                <p class="text-[10px] text-rose-500 font-medium truncate max-w-[200px]" title="{{ $log->error_message }}">{{ $log->error_message }}</p>
                                @endif
                            </td>
                            <td class="text-right !pr-6 text-surface-400">
                                {{ $log->created_at->format('H:i:s') }}
                                @if($log->open_count > 0)
                                <div class="text-[9px] text-sky-500 font-bold uppercase mt-1">Opened ({{ $log->open_count }})</div>
                                @endif
                                @if($log->click_count > 0)
                                <div class="text-[9px] text-indigo-500 font-bold uppercase mt-1">Clicked ({{ $log->click_count }})</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-surface-100 bg-surface-50/50">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
