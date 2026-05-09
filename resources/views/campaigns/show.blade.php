@extends('layouts.app')
@section('title', 'Campaign Intelligence')
@section('heading', $campaign->name)

@section('content')
<div class="space-y-8 animate-slide-up" x-data="{ tab: 'analytics' }">
    
    {{-- Header Actions --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            @php
                $statusCls = match($campaign->status) {
                    'completed' => 'bg-green-100 text-green-700 border-green-200',
                    'sending' => 'bg-primary-100 text-primary-700 border-primary-200 animate-pulse',
                    'paused' => 'bg-amber-100 text-amber-700 border-amber-200',
                    default => 'bg-surface-100 text-surface-600 border-surface-200',
                };
            @endphp
            <span class="px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-widest border {{ $statusCls }}">
                {{ $campaign->status }}
            </span>
            <span class="text-xs font-medium text-surface-400">Created {{ $campaign->created_at->format('M d, Y') }}</span>
        </div>
        
        <div class="flex items-center gap-2">
            @if($campaign->status === 'draft')
                <form action="{{ route('admin.campaigns.send', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-md px-8">Launch Mission</button>
                </form>
            @elseif($campaign->status === 'sending')
                <form action="{{ route('admin.campaigns.pause', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-warning rounded-md">Pause</button>
                </form>
            @elseif($campaign->status === 'paused')
                <form action="{{ route('admin.campaigns.resume', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-md">Resume</button>
                </form>
            @endif

            <div class="h-8 w-px bg-surface-200 mx-2"></div>

            <form action="{{ route('admin.campaigns.clone', $campaign) }}" method="POST">
                @csrf
                <button type="submit" class="btn border-surface-200 hover:bg-surface-50 text-surface-700 rounded-md">Duplicate</button>
            </form>
        </div>
    </div>

    {{-- Main Stats Dashboard (TOP) --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4" id="stats-grid">
        <div class="glass-card p-5 rounded-md border-b-4 border-primary-500">
            <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest block mb-2">Delivery</span>
            <h3 class="text-2xl font-black text-surface-900" id="stat-sent-count">{{ number_format($campaign->sent_count) }}</h3>
            <p class="text-[10px] text-primary-600 mt-1 font-bold">{{ $campaign->progress() }}% Complete</p>
        </div>

        <div class="glass-card p-5 rounded-md border-b-4 border-indigo-500">
            <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest block mb-2">Opens</span>
            <h3 class="text-2xl font-black text-surface-900" id="stat-open-rate">{{ $campaign->open_rate }}%</h3>
            <p class="text-[10px] text-indigo-600 mt-1 font-bold">{{ number_format($stats['opens'] ?? 0) }} Total</p>
        </div>

        <div class="glass-card p-5 rounded-md border-b-4 border-emerald-500">
            <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest block mb-2">Clicks</span>
            <h3 class="text-2xl font-black text-surface-900" id="stat-click-rate">{{ $campaign->click_rate }}%</h3>
            <p class="text-[10px] text-emerald-600 mt-1 font-bold">{{ number_format($stats['clicks'] ?? 0) }} Total</p>
        </div>

        <div class="glass-card p-5 rounded-md border-b-4 border-rose-500">
            <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest block mb-2">Bounced</span>
            <h3 class="text-2xl font-black text-surface-900" id="stat-bounce-count">{{ number_format($campaign->bounce_count) }}</h3>
            <p class="text-[10px] text-rose-600 mt-1 font-bold">Rejected by Server</p>
        </div>

        <div class="glass-card p-5 rounded-md border-b-4 border-amber-500">
            <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest block mb-2">Failed</span>
            <h3 class="text-2xl font-black text-surface-900" id="stat-failed-count">{{ number_format($campaign->failed_count) }}</h3>
            <p class="text-[10px] text-amber-600 mt-1 font-bold">Dispatch Errors</p>
        </div>
    </div>

    {{-- Dynamic Compact Progress Engine --}}
    <div class="glass-card p-6 rounded-md mb-8 transition-all duration-500 {{ $campaign->status === 'sending' ? 'opacity-100' : 'opacity-0 hidden' }}" id="progress-engine">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-md flex items-center justify-center bg-primary-500 text-white animate-pulse">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="text-sm font-black text-surface-900 uppercase tracking-tight">Active Dispatch Pipeline</h3>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs font-black text-surface-400 uppercase tracking-widest" id="live-progress-label">In Progress</span>
                <span class="text-lg font-black text-primary-600" id="live-progress-percent">{{ number_format($campaign->progress(), 1) }}%</span>
            </div>
        </div>

        {{-- Small Multi-Segment Progress Bar --}}
        <div class="h-2 w-full bg-surface-100 rounded-full overflow-hidden flex border border-surface-200">
            @php 
                $total = max(1, $campaign->total_recipients);
                $sentP = ($campaign->sent_count / $total) * 100;
                $bounceP = ($campaign->bounce_count / $total) * 100;
                $failedP = ($campaign->failed_count / $total) * 100;
            @endphp
            <div class="h-full bg-primary-500 transition-all duration-1000 shadow-inner" id="bar-sent" style="width: {{ $sentP }}%"></div>
            <div class="h-full bg-rose-500 transition-all duration-1000" id="bar-bounce" style="width: {{ $bounceP }}%"></div>
            <div class="h-full bg-amber-500 transition-all duration-1000" id="bar-failed" style="width: {{ $failedP }}%"></div>
        </div>
    </div>

    {{-- Content Tabs --}}
    <div class="space-y-6">
        <div class="flex items-center gap-1 p-1 bg-surface-100 rounded-md w-fit">
            <button @click="tab = 'analytics'" :class="tab === 'analytics' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500'" class="px-6 py-2 rounded-md text-xs font-black uppercase tracking-widest transition-all">Engagement Stats</button>
            <button @click="tab = 'logs'" :class="tab === 'logs' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500'" class="px-6 py-2 rounded-md text-xs font-black uppercase tracking-widest transition-all">Real-time Logs</button>
            <button @click="tab = 'settings'" :class="tab === 'settings' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500'" class="px-6 py-2 rounded-md text-xs font-black uppercase tracking-widest transition-all">Configuration</button>
        </div>

        {{-- Analytics Tab --}}
        <div x-show="tab === 'analytics'" class="grid grid-cols-1 md:grid-cols-3 gap-8" x-transition>
            <div class="md:col-span-2 space-y-8">
                {{-- Link Performance --}}
                <div class="glass-card rounded-md">
                    <div class="p-6 border-b border-surface-100">
                        <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Link Performance</h4>
                    </div>
                    <div class="p-6 space-y-6">
                        @forelse($topLinks ?? [] as $link)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-surface-600 truncate max-w-md">{{ $link->url }}</span>
                                <span class="text-primary-600">{{ $link->count }} clicks</span>
                            </div>
                            <div class="w-full h-2 bg-surface-50 rounded-md overflow-hidden border border-surface-100">
                                <div class="h-full bg-primary-500" style="width: {{ ($link->count / max(1, $stats['clicks'] ?? 1)) * 100 }}%"></div>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-surface-400 text-center py-12 italic">No link engagement tracked yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Activity Feed --}}
                <div class="glass-card rounded-md">
                    <div class="p-6 border-b border-surface-100">
                        <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Recent Activity</h4>
                    </div>
                    <div class="p-0">
                        @foreach($campaign->activities()->with('email')->latest()->take(10)->get() as $activity)
                        <div class="p-4 border-b border-surface-50 last:border-0 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-8 h-8 rounded-md flex items-center justify-center text-xs',
                                    'bg-indigo-50 text-indigo-600' => $activity->type === 'opened',
                                    'bg-emerald-50 text-emerald-600' => $activity->type === 'clicked',
                                ])>
                                    @if($activity->type === 'opened')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-surface-900">{{ $activity->email->email ?? 'Unknown' }}</p>
                                    <p class="text-[10px] text-surface-400 mt-0.5">{{ $activity->created_at->diffForHumans() }} from {{ $activity->ip_address ?? 'Global' }}</p>
                                </div>
                            </div>
                            @if($activity->type === 'clicked')
                            <span class="text-[9px] font-black px-2 py-0.5 bg-surface-50 text-surface-400 rounded-md border border-surface-100">CLICKED LINK</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Sidebar Stats --}}
            <div class="space-y-6">
                <div class="glass-card p-6 rounded-md">
                    <h4 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-6">Device Profile</h4>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-surface-500 font-bold">Desktop</span>
                            <span class="text-surface-900 font-black">-- %</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-surface-500 font-bold">Mobile</span>
                            <span class="text-surface-900 font-black">-- %</span>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 rounded-md">
                    <h4 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-6">Infrastructure</h4>
                    <div class="space-y-3">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Sender Identity</span>
                            <span class="text-xs font-black text-surface-900">{{ $campaign->sender->email }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Protocol</span>
                            <span class="text-xs font-black text-primary-600 uppercase">{{ $campaign->sender->type }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Logs Tab --}}
        <div x-show="tab === 'logs'" class="glass-card rounded-md overflow-hidden" x-transition>
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="!pl-6">Recipient</th>
                        <th>Status</th>
                        <th>Message ID</th>
                        <th class="text-right !pr-6">Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td class="!pl-6">
                            <span class="font-bold text-surface-900">{{ $log->email_address }}</span>
                        </td>
                        <td>
                            <span @class([
                                'px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border',
                                'bg-green-50 text-green-700 border-green-100' => $log->status === 'sent',
                                'bg-amber-50 text-amber-700 border-amber-100' => $log->status === 'pending',
                                'bg-red-50 text-red-700 border-red-100' => in_array($log->status, ['failed', 'bounced', 'complaint']),
                            ])>
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="font-mono text-[10px] text-surface-400">{{ $log->message_id ?? 'N/A' }}</td>
                        <td class="text-right !pr-6 text-surface-500 font-medium text-xs">
                            {{ $log->sent_at ? $log->sent_at->format('H:i:s') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Configuration Tab --}}
        <div x-show="tab === 'settings'" class="glass-card rounded-md p-8" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="space-y-6">
                    <h5 class="text-sm font-black text-surface-900 uppercase tracking-widest border-b border-surface-100 pb-2">Campaign Settings</h5>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Subject Line</span>
                            <span class="text-sm font-medium text-surface-900">{{ $campaign->subject }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Batch Size</span>
                            <span class="text-sm font-medium text-surface-900">{{ $campaign->batch_size }} emails per job</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Target Velocity</span>
                            <span class="text-sm font-medium text-surface-900">{{ $campaign->emails_per_minute }} emails / min</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-6">
                    <h5 class="text-sm font-black text-surface-900 uppercase tracking-widest border-b border-surface-100 pb-2">Audience Profile</h5>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Email List</span>
                            <span class="text-sm font-medium text-surface-900">{{ $campaign->emailList->name }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-bold text-surface-400 uppercase">Total Candidates</span>
                            <span class="text-sm font-medium text-surface-900">{{ number_format($campaign->total_recipients) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    @if($campaign->status === 'sending' || $campaign->status === 'paused')
    // Auto-refresh stats every 5 seconds
    const pollInterval = setInterval(() => {
        fetch('{{ route("admin.campaigns.status", $campaign) }}')
        .then(response => response.json())
        .then(data => {
            // 1. Update Global Stats Grid
            document.getElementById('stat-sent-count').innerText = data.sent_count.toLocaleString();
            document.getElementById('stat-bounce-count').innerText = data.bounce_count.toLocaleString();
            document.getElementById('stat-failed-count').innerText = data.failed_count.toLocaleString();
            
            // 2. Update Progress Engine (if visible)
            const engine = document.getElementById('progress-engine');
            if (data.status === 'sending') {
                engine.classList.remove('hidden', 'opacity-0');
                engine.classList.add('opacity-100');
            } else if (data.status === 'completed') {
                engine.classList.add('opacity-0');
                setTimeout(() => engine.classList.add('hidden'), 500);
                clearInterval(pollInterval); // Stop polling when done
                window.location.reload(); // Refresh once to get final UI state
            }

            document.getElementById('live-progress-percent').innerText = data.progress + '%';

            // 3. Update Bars
            const total = Math.max(1, data.total);
            document.getElementById('bar-sent').style.width = ((data.sent_count / total) * 100) + '%';
            document.getElementById('bar-bounce').style.width = ((data.bounce_count / total) * 100) + '%';
            document.getElementById('bar-failed').style.width = ((data.failed_count / total) * 100) + '%';
            
            // If completed, reload to show final view
            if (data.status === 'completed') {
                window.location.reload();
            }
        });
    }, 5000);
    @endif
</script>
@endpush
