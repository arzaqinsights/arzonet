@extends('layouts.app')
@section('title', 'Campaigns')
@section('heading', 'Campaigns')

@section('header-actions')
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary shadow-xl shadow-primary-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Campaign
    </a>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up">
    {{-- ── Campaign Global Intelligence ── --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Total Sent</p>
            <h2 class="text-3xl font-black text-surface-900">{{ number_format($campaigns->sum('sent_count')) }}</h2>
            <div class="w-full h-1 bg-primary-100 rounded-full mt-4"></div>
        </div>
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Success Rate</p>
            @php 
                $total = $campaigns->sum('sent_count') + $campaigns->sum('failed_count');
                $rate = $total > 0 ? round(($campaigns->sum('sent_count') / $total) * 100) : 100;
            @endphp
            <h2 class="text-3xl font-black text-emerald-600">{{ $rate }}%</h2>
            <div class="w-full h-1 bg-emerald-100 rounded-full mt-4"></div>
        </div>
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Active</p>
            <h2 class="text-3xl font-black text-primary-600">{{ $campaigns->where('status', 'sending')->count() }}</h2>
            <p class="text-[10px] text-surface-400 font-bold mt-2 uppercase">Running Now</p>
        </div>
        <div class="glass-card p-6 bg-gradient-to-br from-white to-surface-50 border-surface-200">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Total Reach</p>
            <h2 class="text-3xl font-black text-surface-900">{{ number_format($campaigns->sum('total_recipients')) }}</h2>
            <p class="text-[10px] text-surface-400 font-bold mt-2 uppercase">Target Audience</p>
        </div>
    </div>

    {{-- ── Campaign Registry ── --}}
    @if($campaigns->count())
    <div class="glass-card overflow-hidden border-surface-200 shadow-xl">
        <div class="p-6 bg-surface-50 border-b border-surface-100 flex items-center justify-between">
            <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest">Campaign Registry</h3>
            <span class="text-[10px] font-bold text-surface-400">{{ $campaigns->total() }} Total Managed</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr class="bg-surface-50/50">
                        <th class="!pl-8 text-[10px] font-black uppercase tracking-widest text-surface-400">Campaign Identity</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-surface-400">Target List</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-surface-400 text-center">Status</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-surface-400">Progress</th>
                        <th class="text-[10px] font-black uppercase tracking-widest text-surface-400 text-right !pr-8">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @foreach($campaigns as $campaign)
                    <tr class="group border-b border-surface-50 last:border-0 hover:bg-surface-50/50 transition-colors">
                        <td class="!pl-8 !py-5">
                            <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm font-black text-surface-900 hover:text-primary-600 block mb-0.5">{{ $campaign->name }}</a>
                            <p class="text-[9px] font-bold text-surface-400 uppercase tracking-tighter">{{ $campaign->created_at->diffForHumans() }}</p>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded bg-primary-50 flex items-center justify-center text-primary-600 text-[8px] font-black">
                                    {{ substr($campaign->emailList->name ?? 'L', 0, 1) }}
                                </div>
                                <span class="font-bold text-surface-700">{{ $campaign->emailList->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            @php $cls = match($campaign->status) { 'completed' => 'bg-emerald-100 text-emerald-700', 'sending' => 'bg-amber-100 text-amber-700', 'scheduled' => 'bg-indigo-100 text-indigo-700', 'cancelled' => 'bg-red-100 text-red-700', default => 'bg-surface-100 text-surface-600' }; @endphp
                            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest {{ $cls }}">
                                @if($campaign->status === 'sending')
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                @endif
                                {{ $campaign->status }}
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col gap-1.5 min-w-[120px]">
                                <div class="flex justify-between items-end">
                                    <span class="text-[9px] font-black text-surface-400 uppercase">{{ number_format($campaign->sent_count) }} / {{ number_format($campaign->total_recipients) }}</span>
                                    <span class="text-[10px] font-black text-primary-600">{{ $campaign->progress() }}%</span>
                                </div>
                                <div class="h-1.5 w-full bg-surface-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 rounded-full" style="width: {{ $campaign->progress() }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-right !pr-8">
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('campaigns.show', $campaign) }}" class="p-2 text-surface-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all" title="View Report">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                </a>
                                <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('Permanently delete this campaign?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete Campaign">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-6 bg-surface-50/50 border-t border-surface-100">
            {{ $campaigns->links() }}
        </div>
    </div>
    @else
    <div class="glass-card p-24 text-center">
        <div class="w-24 h-24 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-8 text-primary-200">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        </div>
        <h3 class="text-3xl font-black text-surface-900 mb-3">Launch Your First Mission</h3>
        <p class="text-surface-500 mb-10 max-w-md mx-auto text-lg leading-relaxed">No campaigns found. Start your first high-performance marketing mission today.</p>
        <a href="{{ route('campaigns.create') }}" class="btn btn-primary px-12 py-4 shadow-xl shadow-primary-200">Create New Campaign</a>
    </div>
    @endif
</div>
@endsection
