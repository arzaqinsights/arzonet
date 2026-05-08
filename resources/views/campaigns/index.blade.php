@extends('layouts.app')
@section('title', 'Campaign Management')
@section('heading', 'All Campaigns')

@section('content')
<div class="space-y-8 animate-slide-up">
    
    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="glass-card p-6 rounded-md border-l-4 border-primary-500">
            <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Total Managed</p>
            <h3 class="text-2xl font-black text-surface-900 mt-2">{{ $campaigns->total() }}</h3>
        </div>
        <div class="glass-card p-6 rounded-md border-l-4 border-green-500">
            <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Global Reach</p>
            <h3 class="text-2xl font-black text-surface-900 mt-2">{{ number_format($campaigns->sum('total_recipients')) }}</h3>
        </div>
        <div class="glass-card p-6 rounded-md border-l-4 border-amber-500">
            <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Delivered</p>
            <h3 class="text-2xl font-black text-surface-900 mt-2">{{ number_format($campaigns->sum('sent_count')) }}</h3>
        </div>
        <div class="glass-card p-6 rounded-md border-l-4 border-indigo-500">
            <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Avg. Open Rate</p>
            <h3 class="text-2xl font-black text-surface-900 mt-2">-- %</h3>
        </div>
    </div>

    {{-- Campaign List --}}
    <div class="glass-card overflow-hidden rounded-md">
        <div class="p-6 bg-surface-50/50 border-b border-surface-100 flex justify-between items-center">
            <h4 class="text-surface-900 font-extrabold text-xs uppercase tracking-[0.2em]">Marketing Pipeline</h4>
            <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary btn-sm rounded-md px-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Campaign
            </a>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-1/3">Campaign & Identity</th>
                    <th>Engagement</th>
                    <th>Pipeline Status</th>
                    <th class="text-right">Manage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                <tr class="group">
                    <td>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-md bg-surface-100 flex flex-col items-center justify-center text-surface-500 border border-surface-200">
                                <span class="text-[9px] font-black uppercase tracking-tighter">{{ $campaign->created_at->format('M') }}</span>
                                <span class="text-lg font-black leading-none">{{ $campaign->created_at->format('d') }}</span>
                            </div>
                            <div>
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="font-bold text-surface-900 hover:text-primary-600 transition-colors leading-tight block">
                                    {{ $campaign->name }}
                                </a>
                                <div class="flex items-center gap-2 mt-1.5">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-surface-100 text-surface-600 border border-surface-200 uppercase">
                                        {{ $campaign->emailList->name ?? 'Audience' }}
                                    </span>
                                    <span class="text-[10px] font-medium text-surface-400">
                                        {{ number_format($campaign->total_recipients) }} recipients
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-tighter">
                                <span class="text-surface-400">Engagement Rate</span>
                                <span class="text-primary-600">{{ $campaign->open_rate }}%</span>
                            </div>
                            <div class="w-24 h-1.5 bg-surface-100 rounded-md overflow-hidden">
                                <div class="h-full bg-primary-500" style="width: {{ $campaign->open_rate }}%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                @php
                                    $statusCls = match($campaign->status) {
                                        'completed' => 'bg-green-100 text-green-700 border-green-200',
                                        'sending' => 'bg-primary-100 text-primary-700 border-primary-200 animate-pulse',
                                        'paused' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                        default => 'bg-surface-100 text-surface-600 border-surface-200',
                                    };
                                @endphp
                                <span class="text-[9px] font-black px-2 py-0.5 rounded-md border uppercase tracking-widest {{ $statusCls }}">
                                    {{ $campaign->status }}
                                </span>
                            </div>
                            <div class="w-full h-1.5 bg-surface-100 rounded-md overflow-hidden">
                                <div class="h-full bg-surface-400 transition-all duration-500" style="width: {{ $campaign->progress() }}%"></div>
                            </div>
                            <p class="text-[10px] font-medium text-surface-400">
                                {{ number_format($campaign->sent_count) }} / {{ number_format($campaign->total_recipients) }} sent
                            </p>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-200">
                            {{-- Clone --}}
                            <form action="{{ route('admin.campaigns.clone', $campaign) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 text-surface-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors" title="Duplicate Campaign">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                </button>
                            </form>

                            {{-- Edit --}}
                            @if(!in_array($campaign->status, ['completed', 'sending']))
                            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="p-2 text-surface-500 hover:text-primary-600 hover:bg-primary-50 rounded-md transition-colors" title="Edit Campaign">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            @endif

                            {{-- Report/Show --}}
                            <a href="{{ route('admin.campaigns.show', $campaign) }}" class="p-2 text-surface-500 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors" title="Analytics & Logs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('admin.campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Archive this campaign?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-surface-500 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Archive">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-32">
                        <div class="max-w-xs mx-auto">
                            <div class="w-20 h-20 bg-surface-50 rounded-md flex items-center justify-center mx-auto mb-6 text-surface-200 border border-dashed border-surface-200">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            </div>
                            <h5 class="text-surface-900 font-bold text-lg leading-tight">No Marketing Missions Found</h5>
                            <p class="text-sm text-surface-500 mt-2">Start your first high-performance campaign to grow your business.</p>
                            <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary mt-8 rounded-md px-10">Launch First Mission</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($campaigns->hasPages())
        <div class="px-8 py-6 border-t border-surface-100 bg-surface-50/30">
            {{ $campaigns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
