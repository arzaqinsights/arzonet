@extends('layouts.app')
@section('title', $campaign->name)
@section('heading', 'Campaign Report')

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-xs font-bold uppercase tracking-widest text-surface-400 hover:text-surface-600">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </a>
        <div class="h-4 w-px bg-surface-200 mx-1"></div>
        <a href="{{ route('campaigns.report', $campaign) }}" class="btn btn-primary btn-xs font-black uppercase tracking-widest px-4 py-2">
            Analytics
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6 animate-slide-up"
     x-data="campaignDashboard()"
     x-init="@if(in_array($campaign->status, ['sending'])) pollStatus() @endif">

    {{-- ── Compact Mission Header ── --}}
    <div class="glass-card p-6 border-surface-200 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                @php
                    $color = match($campaign->status) {
                        'draft' => 'surface',
                        'scheduled' => 'indigo',
                        'sending' => 'amber',
                        'paused' => 'amber',
                        'completed' => 'emerald',
                        'cancelled' => 'red',
                        default => 'surface',
                    };
                @endphp
                <div class="w-12 h-12 rounded-xl bg-{{ $color }}-50 flex items-center justify-center text-{{ $color }}-600 flex-shrink-0">
                    @if($campaign->status === 'sending')
                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    @elseif($campaign->status === 'completed')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <h2 class="text-xl font-black text-surface-900 truncate">{{ $campaign->name }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest bg-{{ $color }}-100 text-{{ $color }}-700">
                            {{ $campaign->status }}
                        </span>
                    </div>
                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-tighter">
                        {{ $campaign->emailList->name }} • {{ $campaign->template->name }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if(in_array($campaign->status, ['draft', 'scheduled']))
                    <form action="{{ route('campaigns.send', $campaign) }}" method="POST">
                        @csrf
                        <button class="btn btn-success btn-sm px-6 font-black uppercase text-[10px] tracking-widest">Launch</button>
                    </form>
                @endif
                @if($campaign->status === 'sending')
                    <form action="{{ route('campaigns.pause', $campaign) }}" method="POST">
                        @csrf
                        <button class="btn btn-warning btn-sm px-6 font-black uppercase text-[10px] tracking-widest">Pause</button>
                    </form>
                @endif
                @if($campaign->status === 'paused')
                    <form action="{{ route('campaigns.resume', $campaign) }}" method="POST">
                        @csrf
                        <button class="btn btn-success btn-sm px-6 font-black uppercase text-[10px] tracking-widest">Resume</button>
                    </form>
                @endif
                @if(in_array($campaign->status, ['sending', 'paused', 'scheduled']))
                    <form action="{{ route('campaigns.cancel', $campaign) }}" method="POST">
                        @csrf
                        <button class="btn btn-ghost btn-sm text-red-600 font-bold uppercase text-[10px] tracking-widest">Abort</button>
                    </form>
                @endif
                
                @if($stats['failed'] > 0)
                    <form action="{{ route('campaigns.retry-failed', $campaign) }}" method="POST">
                        @csrf
                        <button class="btn btn-warning btn-sm px-6 font-black uppercase text-[10px] tracking-widest shadow-lg shadow-amber-100">
                            Retry Failures
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-surface-50">
            <div class="flex justify-between items-center mb-1.5">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Delivery Progress</span>
                <span class="text-xs font-black text-primary-600" x-text="progress + '%'">{{ $stats['progress'] }}%</span>
            </div>
            <div class="h-2 w-full bg-surface-100 rounded-full overflow-hidden">
                <div class="h-full bg-primary-600 transition-all duration-1000 ease-out" :style="'width:' + progress + '%'" style="width: {{ $stats['progress'] }}%"></div>
            </div>
            <p class="text-[9px] text-surface-400 font-bold mt-2 uppercase tracking-tight">
                <span x-text="sent.toLocaleString()">{{ number_format($stats['sent']) }}</span> of <span x-text="total.toLocaleString()">{{ number_format($stats['total']) }}</span> contacts reached
            </p>
        </div>
    </div>

    {{-- ── High-Density Stats Row ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-surface-100 p-4 rounded-xl shadow-sm">
            <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-1">Total</p>
            <p class="text-xl font-black text-surface-900" x-text="total.toLocaleString()">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white border border-emerald-100 p-4 rounded-xl shadow-sm border-b-2 border-b-emerald-500">
            <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Sent</p>
            <p class="text-xl font-black text-surface-900" x-text="sent.toLocaleString()">{{ number_format($stats['sent']) }}</p>
        </div>
        <div class="bg-white border border-red-100 p-4 rounded-xl shadow-sm border-b-2 border-b-red-500">
            <p class="text-[9px] font-black text-red-600 uppercase tracking-widest mb-1">Failed</p>
            <p class="text-xl font-black text-surface-900" x-text="failed.toLocaleString()">{{ number_format($stats['failed']) }}</p>
        </div>
        <div class="bg-white border border-amber-100 p-4 rounded-xl shadow-sm border-b-2 border-b-amber-500">
            <p class="text-[9px] font-black text-amber-600 uppercase tracking-widest mb-1">Queue</p>
            <p class="text-xl font-black text-surface-900" x-text="pending.toLocaleString()">{{ number_format($stats['pending']) }}</p>
        </div>
    </div>

    {{-- ── Compact Activity Log ── --}}
    <div class="glass-card overflow-hidden border-surface-200">
        <div class="px-6 py-4 bg-surface-50/50 border-b border-surface-100 flex items-center justify-between">
            <h3 class="text-[10px] font-black text-surface-900 uppercase tracking-widest">Real-time Activity Feed</h3>
            <div class="flex items-center gap-1.5 text-[8px] font-black uppercase text-emerald-600 tracking-widest">
                <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                Streaming
            </div>
        </div>
        
        <div class="overflow-x-auto">
            @if($recentLogs->count())
            <table class="data-table">
                <thead>
                    <tr class="bg-surface-50/50">
                        <th class="!pl-6 !py-2 text-[9px] font-black uppercase tracking-widest text-surface-400">Recipient</th>
                        <th class="!py-2 text-[9px] font-black uppercase tracking-widest text-surface-400 text-center">Status</th>
                        <th class="!py-2 text-[9px] font-black uppercase tracking-widest text-surface-400">Activity Detail</th>
                        <th class="!py-2 text-[9px] font-black uppercase tracking-widest text-surface-400 text-right !pr-6">Time</th>
                    </tr>
                </thead>
                <tbody class="text-[11px]">
                    @foreach($recentLogs as $log)
                    <tr class="border-b border-surface-50 last:border-0 hover:bg-surface-50/50 transition-colors">
                        <td class="!pl-6 !py-3 font-bold text-surface-900">{{ $log->email_address }}</td>
                        <td class="text-center">
                            @php $logCls = match($log->status) { 'sent' => 'text-emerald-600', 'failed' => 'text-red-600', default => 'text-surface-400' }; @endphp
                            <span class="font-black uppercase tracking-tighter {{ $logCls }}">{{ $log->status }}</span>
                        </td>
                        <td class="text-surface-500 max-w-xs md:max-w-md">
                            <div class="truncate" title="{{ $log->error_message }}">
                                {{ $log->error_message ?? 'Delivery Successful' }}
                            </div>
                        </td>
                        <td class="text-right !pr-6 text-surface-400 font-bold">{{ $log->sent_at?->diffForHumans() ?? 'Queue' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-12 text-center text-surface-400">
                <p class="text-xs font-bold uppercase tracking-widest">Awaiting deployment...</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function campaignDashboard() {
    return {
        total: {{ $stats['total'] }},
        sent: {{ $stats['sent'] }},
        failed: {{ $stats['failed'] }},
        pending: {{ $stats['pending'] }},
        progress: {{ $stats['progress'] }},

        pollStatus() {
            setInterval(() => {
                fetch('{{ route("campaigns.status", $campaign) }}')
                    .then(r => r.json())
                    .then(data => {
                        this.sent = data.sent_count;
                        this.failed = data.failed_count;
                        this.total = data.total;
                        this.progress = data.progress;
                        this.pending = data.total - data.sent_count - data.failed_count;

                        if (data.status === 'completed' || data.status === 'cancelled') {
                            location.reload();
                        }
                    });
            }, 3000);
        }
    };
}
</script>
@endsection
