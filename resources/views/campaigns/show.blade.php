@extends('layouts.app')
@section('title', 'Campaign Intelligence')
@section('heading')
    <div class="flex items-center gap-3 group relative" x-data="{ 
        editingName: false, 
        newName: '{{ addslashes($campaign->name) }}', 
        saving: false,
        saveName() {
            if (this.newName.trim() === '') return;
            this.saving = true;
            fetch('{{ route("admin.campaigns.update", $campaign) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: this.newName })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.editingName = false;
                    window.location.reload();
                }
            })
            .finally(() => {
                this.saving = false;
            });
        }
    }">
        <template x-if="!editingName">
            <div class="flex items-center gap-3">
                <span class="cursor-pointer" @click="editingName = true; $nextTick(() => $refs.nameInput.focus())" x-text="newName"></span>
                <button @click="editingName = true; $nextTick(() => $refs.nameInput.focus())" class="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-surface-100 rounded-sm">
                    <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            </div>
        </template>
        <template x-if="editingName">
            <div class="flex items-center gap-2">
                <input type="text" x-model="newName" x-ref="nameInput" @keydown.enter="saveName()" @keydown.escape="editingName = false; newName = '{{ addslashes($campaign->name) }}'" :disabled="saving" class="bg-white border border-surface-200 rounded-sm px-2 py-1 text-lg font-black uppercase outline-none focus:border-primary-500 min-w-[300px]">
                <button @click="saveName()" class="p-1 hover:bg-emerald-50 text-emerald-600 rounded-sm" :disabled="saving">
                    <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </button>
                <button @click="editingName = false; newName = '{{ addslashes($campaign->name) }}'" class="p-1 hover:bg-rose-50 text-rose-600 rounded-sm" :disabled="saving">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>
@endsection

@section('content')
<div class="space-y-8 animate-slide-up" x-data="{ tab: 'analytics', showExportLogsModal: false, exportLogsFormat: 'xlsx', exportLogsFilename: 'campaign_{{ $campaign->id }}_logs' }">
    
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
            @if($campaign->error_message)
                <div class="flex items-center gap-2 px-3 py-1 bg-rose-50 border border-rose-100 rounded-md">
                    <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span class="text-[10px] font-bold text-rose-600 uppercase tracking-tight">{{ $campaign->error_message }}</span>
                </div>
            @endif
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
                <form action="{{ route('admin.campaigns.retry-failed', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-md">Retry Failed</button>
                </form>
            @elseif($campaign->status === 'paused')
                <form action="{{ route('admin.campaigns.resume', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-md">Resume</button>
                </form>
                <form action="{{ route('admin.campaigns.retry-failed', $campaign) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-md">Retry Failed</button>
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
    @php
        $totalSent = max(1, $stats['sent'] ?? 0);
        $bouncedCount = $stats['bounced'] ?? 0;
        $failedCount = $stats['failed'] ?? 0;
        $droppedCount = $stats['dropped'] ?? 0;
        $successfulDeliveries = max(0, $totalSent - $bouncedCount - $failedCount - $droppedCount);
        $successRate = round(($successfulDeliveries / $totalSent) * 100, 1);
        
        $healthColor = 'text-emerald-700 bg-emerald-50 border-emerald-200';
        if ($successRate < 98 && $successRate >= 95) {
            $healthColor = 'text-amber-700 bg-amber-50 border-amber-200';
        } elseif ($successRate < 95) {
            $healthColor = 'text-rose-700 bg-rose-50 border-rose-200';
        }
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="stats-grid">
        {{-- Campaign Funnel & Core Engagement --}}
        <div class="lg:col-span-2 bg-white rounded-md border border-surface-200 shadow-sm flex flex-col justify-between">
            {{-- Panel Header --}}
            <div class="p-4 border-b border-surface-150 flex items-center justify-between bg-surface-50/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                    <h3 class="text-xs font-bold text-surface-900 uppercase tracking-wider">Campaign Dispatch & Engagement</h3>
                </div>
                <span class="text-[10px] text-surface-400 font-bold uppercase tracking-wider">Performance Funnel</span>
            </div>
            
            {{-- Funnel Steps --}}
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 relative">
                {{-- Step 1: Dispatched --}}
                <div class="flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-50 text-sky-600 border border-sky-100 uppercase tracking-wider">1. Sent</span>
                            <span class="text-sky-500 text-xs"><i class="fa-solid fa-paper-plane"></i></span>
                        </div>
                        <div class="flex items-baseline gap-1 mt-3">
                            <span class="text-3xl font-black text-surface-900 tracking-tight" id="stat-sent-count">{{ number_format($stats['sent'] ?? 0) }}</span>
                            <span class="text-[10px] text-surface-400 font-bold uppercase">emails</span>
                        </div>
                    </div>
                    <div>
                        <div class="w-full bg-surface-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-sky-500 h-full rounded-full transition-all duration-1000" id="bar-sent-funnel" style="width: {{ $campaign->progress() }}%"></div>
                        </div>
                        <div class="flex justify-between items-center mt-2 text-[10px] text-surface-500">
                            <span>Progress</span>
                            <span class="font-bold text-surface-900"><span id="funnel-progress-percent">{{ $campaign->progress() }}</span>% <span class="text-surface-400">of {{ number_format($campaign->total_recipients) }}</span></span>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Opened --}}
                <div class="flex flex-col justify-between space-y-4 md:border-l md:border-surface-200 md:pl-6">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-600 border border-indigo-100 uppercase tracking-wider">2. Opened</span>
                            <span class="text-indigo-500 text-xs"><i class="fa-solid fa-envelope-open"></i></span>
                        </div>
                        <div class="flex items-baseline gap-1 mt-3">
                            <span class="text-3xl font-black text-surface-900 tracking-tight" id="stat-open-rate">{{ $campaign->open_rate }}%</span>
                        </div>
                    </div>
                    <div>
                        <div class="w-full bg-surface-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-indigo-500 h-full rounded-full transition-all duration-1000" id="bar-open-funnel" style="width: {{ $campaign->open_rate }}%"></div>
                        </div>
                        <div class="flex justify-between items-center mt-2 text-[10px] text-surface-500">
                            <span>Unique Opens</span>
                            <span class="font-bold text-surface-900"><span id="stat-unique-opens">{{ number_format($stats['unique_opens'] ?? 0) }}</span> <span class="text-surface-300">/</span> <span id="stat-total-opens">{{ number_format($stats['opens'] ?? 0) }}</span></span>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Clicked --}}
                <div class="flex flex-col justify-between space-y-4 md:border-l md:border-surface-200 md:pl-6">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase tracking-wider">3. Clicked</span>
                            <span class="text-emerald-500 text-xs"><i class="fa-solid fa-arrow-pointer"></i></span>
                        </div>
                        <div class="flex items-baseline gap-1 mt-3">
                            <span class="text-3xl font-black text-surface-900 tracking-tight" id="stat-click-rate">{{ $campaign->click_rate }}%</span>
                        </div>
                    </div>
                    <div>
                        <div class="w-full bg-surface-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" id="bar-click-funnel" style="width: {{ $campaign->click_rate }}%"></div>
                        </div>
                        <div class="flex justify-between items-center mt-2 text-[10px] text-surface-500">
                            <span>Unique Clicks</span>
                            <span class="font-bold text-surface-900"><span id="stat-unique-clicks">{{ number_format($stats['unique_clicks'] ?? 0) }}</span> <span class="text-surface-300">/</span> <span id="stat-total-clicks">{{ number_format($stats['clicks'] ?? 0) }}</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delivery Health & Suppression --}}
        <div class="bg-white rounded-md border border-surface-200 shadow-sm flex flex-col justify-between">
            {{-- Panel Header --}}
            <div class="p-4 border-b border-surface-150 flex items-center justify-between bg-surface-50/50">
                <div class="flex items-center gap-2">
                    <span class="text-rose-500 text-xs"><i class="fa-solid fa-heart-pulse"></i></span>
                    <h3 class="text-xs font-bold text-surface-900 uppercase tracking-wider">Delivery Health</h3>
                </div>
                <span class="px-2 py-0.5 rounded text-[9px] font-bold border {{ $healthColor }}">
                    {{ $successRate }}% Success
                </span>
            </div>

            {{-- Health List --}}
            <div class="p-4 flex-1 flex flex-col justify-center divide-y divide-surface-100">
                {{-- Bounces --}}
                <div class="py-2 flex items-center justify-between first:pt-0">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 shrink-0">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </span>
                        <div>
                            <span class="text-xs font-bold text-surface-850 block leading-tight">Bounces</span>
                            <span class="text-[9px] text-surface-400 uppercase tracking-tight">Rejected by Server</span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-surface-900" id="stat-bounce-count">{{ number_format($stats['bounced'] ?? 0) }}</span>
                </div>

                {{-- Unsubscribed --}}
                <div class="py-2 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-slate-50 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 shrink-0">
                            <i class="fa-solid fa-user-minus"></i>
                        </span>
                        <div>
                            <span class="text-xs font-bold text-surface-850 block leading-tight">Unsubscribes</span>
                            <span class="text-[9px] text-surface-400 uppercase tracking-tight">Opt-outs</span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-surface-900" id="stat-unsubscribe-count">{{ number_format($stats['unsubscribed'] ?? 0) }}</span>
                </div>

                {{-- Failed --}}
                <div class="py-2 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 shrink-0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                        <div>
                            <span class="text-xs font-bold text-surface-850 block leading-tight">Failed</span>
                            <span class="text-[9px] text-surface-400 uppercase tracking-tight">Dispatch/Queue Errors</span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-surface-900" id="stat-failed-count">{{ number_format($stats['failed'] ?? 0) }}</span>
                </div>

                {{-- Dropped --}}
                <div class="py-2 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center text-[10px] border border-violet-100 shrink-0">
                            <i class="fa-solid fa-ban"></i>
                        </span>
                        <div>
                            <span class="text-xs font-bold text-surface-850 block leading-tight">Dropped</span>
                            <span class="text-[9px] text-surface-400 uppercase tracking-tight">Blocked / Suppressed</span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-surface-900" id="stat-dropped-count">{{ number_format($stats['dropped'] ?? 0) }}</span>
                </div>

                {{-- Spam/Complaints --}}
                <div class="py-2 flex items-center justify-between last:pb-0">
                    <div class="flex items-center gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-[10px] border border-red-100 shrink-0">
                            <i class="fa-solid fa-flag"></i>
                        </span>
                        <div>
                            <span class="text-xs font-bold text-surface-850 block leading-tight">Spam / Comp.</span>
                            <span class="text-[9px] text-surface-400 uppercase tracking-tight">Spam Reports</span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-surface-900" id="stat-spam-count">{{ number_format($stats['spam'] ?? 0) }}</span>
                </div>
            </div>
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
                <span class="text-lg font-black text-brand" id="live-progress-percent">{{ number_format($campaign->progress(), 1) }}%</span>
            </div>
        </div>

        {{-- Small Multi-Segment Progress Bar --}}
        <div class="h-2 w-full bg-surface-100 rounded-full overflow-hidden flex border border-surface-200">
            @php 
                $total = max(1, $campaign->total_recipients);
                $sentP = (($stats['sent'] ?? 0) / $total) * 100;
                $bounceP = (($stats['bounced'] ?? 0) / $total) * 100;
                $failedP = (($stats['failed'] ?? 0) / $total) * 100;
            @endphp
            <div class="h-full bg-primary-500 transition-all duration-1000 shadow-inner" id="bar-sent" style="width: {{ $sentP }}%"></div>
            <div class="h-full bg-rose-500 transition-all duration-1000" id="bar-bounce" style="width: {{ $bounceP }}%"></div>
            <div class="h-full bg-amber-500 transition-all duration-1000" id="bar-failed" style="width: {{ $failedP }}%"></div>
        </div>
    </div>

    {{-- Content Tabs --}}
    <div class="space-y-6">
        <div class="flex items-center gap-1 p-1 bg-surface-100 rounded-md shadow-inner border w-fit">
            <button @click="tab = 'analytics'" :class="tab === 'analytics' ? 'bg-surface-800 text-white' : 'text-surface-500'" class="px-6 py-2 rounded-md text-xs font-black uppercase tracking-widest transition-all">Engagement Stats</button>
            <button @click="tab = 'logs'" :class="tab === 'logs' ? 'bg-surface-800 text-white' : 'text-surface-500'" class="px-6 py-2 rounded-md text-xs font-black uppercase tracking-widest transition-all">Real-time Logs</button>
            <!-- <button @click="tab = 'settings'" :class="tab === 'settings' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-500'" class="px-6 py-2 rounded-md text-xs font-black uppercase tracking-widest transition-all">Configuration</button> -->
        </div>

        {{-- Analytics Tab --}}
        <div x-show="tab === 'analytics'" class="grid grid-cols-1 xl:grid-cols-3 gap-8" x-transition>
            
            <div class="xl:col-span-2 space-y-6">
                
                {{-- Charts Grid --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="glass-card rounded-md">
                        <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                            <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Engagement Overview</h4>
                        </div>
                        <div class="p-6">
                            <div id="engagement-chart" class="w-full h-[300px]"></div>
                        </div>
                    </div>

                    {{-- Geographic Engagement Map --}}
                    <div class="glass-card rounded-md">
                        <div class="p-6 border-b border-surface-100 flex items-center justify-between">
                            <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Global Engagement (Locations)</h4>
                        </div>
                        <div class="p-6">
                            <div id="location-chart" class="w-full h-[300px] flex items-center justify-center">
                                <span class="text-xs font-black text-surface-400 uppercase tracking-widest animate-pulse" id="location-loading">Analyzing Global Nodes...</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Activity Feed --}}
                <div class="glass-card rounded-md">
                    <div class="p-6 border-b border-surface-100">
                        <h4 class="text-xs font-black text-surface-900 uppercase tracking-widest">Recent Activity</h4>
                    </div>
                    <div class="p-0">
                        @foreach($campaign->emailEvents()->with('log')->whereIn('email_events.type', ['open', 'click'])->latest('email_events.created_at')->take(10)->get() as $activity)
                        <div class="p-4 border-b border-surface-50 last:border-0 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div @class([
                                    'w-8 h-8 rounded-md flex items-center justify-center text-xs',
                                    'bg-indigo-50 text-indigo-600' => $activity->type === 'open',
                                    'bg-emerald-50 text-emerald-600' => $activity->type === 'click',
                                ])>
                                    @if($activity->type === 'open')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-surface-900">{{ $activity->log->email_address ?? 'Unknown' }}</p>
                                    <p class="text-[10px] text-surface-400 mt-0.5">{{ $activity->created_at->diffForHumans() }} from {{ $activity->ip_address ?? 'Global' }}</p>
                                </div>
                            </div>
                            @if($activity->type === 'click')
                            <span class="text-[9px] font-black px-2 py-0.5 bg-surface-50 text-surface-400 rounded-md border border-surface-100">CLICKED LINK</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- Sidebar Stats --}}
            <div class="space-y-6">
                
                {{-- Link Performance --}}
                <div class="glass-card p-6 rounded-md">
                    <h4 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-6">Link Performance</h4>
                    <div class="space-y-6">
                        @forelse($topLinks ?? [] as $link)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-surface-600 truncate max-w-[150px]" title="{{ $link->url }}">{{ str_starts_with($link->url, 'http') ? $link->url : 'https://' . $link->url }}</span>
                                <span class="text-primary-600">{{ $link->unique_count ?? 0 }} unique / {{ $link->count }} total</span>
                            </div>
                            <div class="w-full h-1 bg-surface-50 rounded-md overflow-hidden border border-surface-100">
                                <div class="h-full bg-primary-500" style="width: {{ ($link->count / max(1, $stats['clicks'] ?? 1)) * 100 }}%"></div>
                            </div>
                        </div>
                        @empty
                        <p class="text-[10px] font-bold text-surface-400 text-center py-4 italic">No link engagement tracked yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Device Profile --}}
                <div class="glass-card p-6 rounded-md">
                    <h4 class="text-[10px] font-black text-surface-900 uppercase tracking-widest mb-6">Device Profile</h4>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-surface-500 font-bold">Desktop</span>
                            <span class="text-surface-900 font-black">{{ $desktopPercent }}%</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-surface-500 font-bold">Mobile/Tablet</span>
                            <span class="text-surface-900 font-black">{{ $mobilePercent }}%</span>
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
        <div x-show="tab === 'logs'" class="glass-card rounded-md overflow-hidden" x-transition id="logs-container">
            <div class="p-4 border-b border-surface-100 flex flex-wrap items-center justify-between gap-4 bg-surface-50/50">
                <div class="flex items-center gap-2">
                    <input type="text" id="log-search" placeholder="Search email..." class="text-xs px-3 py-2 rounded-md border-surface-200 focus:border-primary-500 focus:ring-0 w-48 md:w-64 bg-white shadow-sm" value="{{ request('search') }}">
                    
                    <select id="log-status-filter" class="text-xs px-3 py-2 rounded-md border-surface-200 focus:border-primary-500 focus:ring-0 bg-white shadow-sm">
                        <option value="">All Statuses</option>
                        <option value="sent">Sent</option>
                        <option value="delivered">Delivered</option>
                        <option value="bounced">Bounced</option>
                        <option value="failed">Failed</option>
                        <option value="dropped">Dropped</option>
                        <option value="spamreport">Spam Report</option>
                    </select>

                    <select id="log-engagement-filter" class="text-xs px-3 py-2 rounded-md border-surface-200 focus:border-primary-500 focus:ring-0 bg-white shadow-sm">
                        <option value="">All Engagement</option>
                        <option value="opened">Opened</option>
                        <option value="clicked">Clicked</option>
                    </select>

                    <select id="log-exported-filter" class="text-xs px-3 py-2 rounded-md border-surface-200 focus:border-primary-500 focus:ring-0 bg-white shadow-sm">
                        <option value="">All Logs</option>
                        <option value="not_exported" {{ request('exported_filter') === 'not_exported' ? 'selected' : '' }}>Not Exported</option>
                        <option value="exported" {{ request('exported_filter') === 'exported' ? 'selected' : '' }}>Already Exported</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="showExportLogsModal = true" class="flex items-center gap-2 px-4 py-2 bg-surface-900 text-white text-xs font-black uppercase tracking-widest rounded-md hover:bg-black transition-all shadow-md cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Logs
                    </button>
                </div>
            </div>

            <div id="logs-table-wrapper">
                @include('campaigns.partials.logs_table', ['logs' => $logs])
            </div>
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
                            <span class="text-sm font-medium text-surface-900">{{ $campaign->emailList->name ?? 'Audience (Deleted)' }}</span>
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

    {{-- ── Teleported Modals ── --}}
    <template x-teleport="body">
        <div>
            {{-- Export Logs Modal --}}
            <div x-show="showExportLogsModal" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/90" x-cloak>
                <div class="bg-white rounded-sm w-full max-w-lg overflow-hidden animate-scale-in" @click.away="showExportLogsModal = false">
                    <div class="p-8 border-b border-gray-100 bg-surface-50/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-surface-900 tracking-tight">Export Campaign Logs</h3>
                                <p class="text-[10px] text-surface-400 font-black uppercase mt-1 tracking-widest">Generate filtered dispatch logs</p>
                            </div>
                            <button @click="showExportLogsModal = false" class="text-surface-400 hover:text-surface-600 transition-colors cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-8 space-y-8">
                        <div>
                            <label class="block text-[10px] font-black text-surface-900 uppercase tracking-widest mb-3">Custom Filename</label>
                            <div class="relative">
                                <input type="text" x-model="exportLogsFilename" class="w-full px-4 py-3.5 bg-gray-50 border border-gray-100 rounded-sm text-sm font-bold focus:bg-white focus:border-brand focus:ring-0 transition-all">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-surface-300 uppercase" x-text="`.${exportLogsFormat}`"></div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-surface-900 uppercase tracking-widest mb-3">Select Format</label>
                            <div class="grid grid-cols-2 gap-4">
                                <button @click="exportLogsFormat = 'xlsx'" :class="exportLogsFormat === 'xlsx' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-6 border rounded-sm transition-all text-center cursor-pointer group">
                                    <div class="flex flex-col items-center gap-3">
                                        <div :class="exportLogsFormat === 'xlsx' ? 'bg-brand/10' : 'bg-gray-50'" class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <p class="text-[10px] font-black uppercase tracking-widest">Excel / XLSX</p>
                                    </div>
                                </button>
                                <button @click="exportLogsFormat = 'csv'" :class="exportLogsFormat === 'csv' ? 'border-brand bg-brand/5 text-brand' : 'border-gray-100 text-surface-400'" class="p-6 border rounded-sm transition-all text-center cursor-pointer group">
                                    <div class="flex flex-col items-center gap-3">
                                        <div :class="exportLogsFormat === 'csv' ? 'bg-brand/10' : 'bg-gray-50'" class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h1m2 0h1m-3 4h1m2 0h1m-3 4h1m2 0h1"></path></svg>
                                        </div>
                                        <p class="text-[10px] font-black uppercase tracking-widest">Text / CSV</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3">
                        <button @click="showExportLogsModal = false" class="flex-1 py-3.5 text-[10px] font-black text-surface-400 uppercase tracking-widest hover:text-surface-600 transition-colors cursor-pointer">Cancel</button>
                        <button @click="triggerLogsExport(exportLogsFormat, exportLogsFilename); showExportLogsModal = false" class="flex-2 bg-brand text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-sm transition-all hover:scale-[1.01]">Download File</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    function triggerLogsExport(format, filename) {
        const url = new URL(`{{ route('admin.campaigns.export-logs', $campaign) }}`);
        url.searchParams.set('format', format);
        url.searchParams.set('filename', filename);
        url.searchParams.set('status', logFilters.status || '');
        url.searchParams.set('engagement', logFilters.engagement || '');
        url.searchParams.set('exported_filter', logFilters.exported_filter || '');
        url.searchParams.set('search', logFilters.search || '');
        url.searchParams.set('sort_by', logFilters.sort_by || 'created_at');
        url.searchParams.set('sort_dir', logFilters.sort_dir || 'desc');
        window.location.href = url.toString();
    }

    // Initialize ApexCharts
    document.addEventListener("DOMContentLoaded", function () {
        var options = {
            series: [{
                name: 'Unique Opens',
                data: [{{ $stats['unique_opens'] ?? 0 }}]
            }, {
                name: 'Total Clicks',
                data: [{{ $stats['clicks'] ?? 0 }}]
            }, {
                name: 'Bounces',
                data: [{{ $stats['bounces'] ?? 0 }}]
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '40%',
                    borderRadius: 4
                },
            },
            dataLabels: { enabled: false },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ['Campaign Engagement'],
                labels: { style: { cssClass: 'text-xs font-bold text-surface-500 uppercase' } }
            },
            yaxis: {
                labels: { style: { cssClass: 'text-xs font-bold text-surface-500' } }
            },
            colors: ['#0284c7', '#4f46e5', '#f43f5e'],
            fill: { opacity: 1 },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " contacts"
                    }
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4,
            }
        };

        var chart = new ApexCharts(document.querySelector("#engagement-chart"), options);
        chart.render();

        // Location Chart Logic (Frontend IP to Geo)
        const topIps = {!! json_encode($topIps ?? []) !!};
        if (topIps.length > 0) {
            let promises = topIps.map(item => 
                fetch(`https://ipapi.co/${item.ip_address}/json/`)
                    .then(res => res.json())
                    .then(data => ({
                        country: data.country_name ? data.country_name : 'Unknown',
                        count: item.count
                    }))
                    .catch(() => ({ country: 'Unknown', count: item.count }))
            );

            Promise.all(promises).then(results => {
                // Group by country
                let countryMap = {};
                results.forEach(r => {
                    countryMap[r.country] = (countryMap[r.country] || 0) + r.count;
                });

                let labels = Object.keys(countryMap);
                let series = Object.values(countryMap);

                document.getElementById('location-loading').style.display = 'none';

                var geoOptions = {
                    series: series,
                    labels: labels,
                    chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                    colors: ['#0ea5e9', '#10b981', '#6366f1', '#f43f5e', '#f59e0b'],
                    dataLabels: { enabled: false },
                    stroke: { width: 0 },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    name: { fontSize: '10px', fontWeight: 800, color: '#64748b' },
                                    value: { fontSize: '24px', fontWeight: 900, color: '#0f172a' }
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        fontFamily: 'inherit',
                        fontSize: '11px',
                        fontWeight: 700,
                        markers: { radius: 2 }
                    }
                };

                new ApexCharts(document.querySelector("#location-chart"), geoOptions).render();
            });
        } else {
            document.getElementById('location-loading').innerText = "No location data available.";
        }
    });

    // Enhanced Filtering and Pagination for Logs
    let logFilters = {
        status: '',
        engagement: '',
        exported_filter: '',
        search: '',
        page: 1
    };

    function refreshLogs() {
        const container = document.getElementById('logs-table-wrapper');
        const url = new URL(window.location.href);
        url.searchParams.set('status', logFilters.status || '');
        url.searchParams.set('engagement', logFilters.engagement || '');
        url.searchParams.set('exported_filter', logFilters.exported_filter || '');
        url.searchParams.set('search', logFilters.search || '');
        url.searchParams.set('sort_by', logFilters.sort_by || 'created_at');
        url.searchParams.set('sort_dir', logFilters.sort_dir || 'desc');
        url.searchParams.set('page', logFilters.page || 1);

        // Export link updated via modal trigger

        container.style.opacity = '0.5';
        
        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            container.innerHTML = data.html;
            container.style.opacity = '1';
        })
        .catch(() => container.style.opacity = '1');
    }

    // Filter Listeners
    document.getElementById('log-status-filter')?.addEventListener('change', (e) => {
        logFilters.status = e.target.value;
        logFilters.page = 1;
        refreshLogs();
    });

    document.getElementById('log-engagement-filter')?.addEventListener('change', (e) => {
        logFilters.engagement = e.target.value;
        logFilters.page = 1;
        refreshLogs();
    });

    document.getElementById('log-exported-filter')?.addEventListener('change', (e) => {
        logFilters.exported_filter = e.target.value;
        logFilters.page = 1;
        refreshLogs();
    });

    let searchTimeout;
    document.getElementById('log-search')?.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            logFilters.search = e.target.value;
            logFilters.page = 1;
            refreshLogs();
        }, 500);
    });

    // AJAX Pagination for Logs Tab
    document.addEventListener('click', function(e) {
        const link = e.target.closest('#logs-table-wrapper .pagination a');
        if (link) {
            e.preventDefault();
            const url = new URL(link.href);
            logFilters.page = url.searchParams.get('page') || 1;
            refreshLogs();
        }

        // Sort Header Listener
        const header = e.target.closest('th[data-sort]');
        if (header) {
            const field = header.getAttribute('data-sort');
            if (logFilters.sort_by === field) {
                logFilters.sort_dir = logFilters.sort_dir === 'asc' ? 'desc' : 'asc';
            } else {
                logFilters.sort_by = field;
                logFilters.sort_dir = 'asc';
            }
            logFilters.page = 1;
            refreshLogs();
        }
    });

    @if($campaign->status !== 'draft')
    // Auto-refresh stats: 5s for preparing/sending, 30s for completed (catches late open/click events)
    const pollMs = {{ in_array($campaign->status, ['preparing', 'sending']) ? 5000 : 30000 }};
    const currentStatus = '{{ $campaign->status }}';

    const pollInterval = setInterval(() => {
        // Disable polling if filtering/searching to prevent UI jumps
        if (logFilters.status || logFilters.engagement || logFilters.search || logFilters.page > 1) {
            return;
        }
        fetch('{{ route("admin.campaigns.status", $campaign) }}')
        .then(response => response.json())
        .then(data => {
            if (currentStatus === 'preparing' && data.status === 'sending') {
                window.location.reload();
                return;
            }

            // 1. Update Global Stats Grid
            document.getElementById('stat-sent-count').innerText = data.sent_count.toLocaleString();
            document.getElementById('stat-bounce-count').innerText = data.bounce_count.toLocaleString();
            document.getElementById('stat-dropped-count').innerText = data.dropped_count.toLocaleString();
            document.getElementById('stat-spam-count').innerText = data.spam_count.toLocaleString();
            document.getElementById('stat-unsubscribe-count').innerText = data.unsubscribe_count.toLocaleString();
            document.getElementById('stat-failed-count').innerText = data.failed_count.toLocaleString();
            document.getElementById('stat-open-rate').innerText = data.open_rate + '%';
            document.getElementById('stat-click-rate').innerText = data.click_rate + '%';
            document.getElementById('stat-unique-opens').innerText = data.unique_opens.toLocaleString();
            document.getElementById('stat-total-opens').innerText = data.total_opens.toLocaleString();
            document.getElementById('stat-unique-clicks').innerText = data.unique_clicks.toLocaleString();
            document.getElementById('stat-total-clicks').innerText = data.total_clicks.toLocaleString();
            
            // 2. Update Progress Engine (if visible)
            const engine = document.getElementById('progress-engine');
            if (data.status === 'sending') {
                engine?.classList.remove('hidden', 'opacity-0');
                engine?.classList.add('opacity-100');
            } else if (data.status === 'completed') {
                engine?.classList.add('opacity-0');
                setTimeout(() => engine?.classList.add('hidden'), 500);
                clearInterval(pollInterval); // Stop polling when done
                window.location.reload(); // Refresh once to get final UI state
                return;
            }

            if (document.getElementById('live-progress-percent')) {
                document.getElementById('live-progress-percent').innerText = data.progress + '%';
            }

            // 3. Update Bars
            const total = Math.max(1, data.total);
            const barSent = document.getElementById('bar-sent');
            if (barSent) barSent.style.width = ((data.sent_count / total) * 100) + '%';
            const barBounce = document.getElementById('bar-bounce');
            if (barBounce) barBounce.style.width = ((data.bounce_count / total) * 100) + '%';
            const barFailed = document.getElementById('bar-failed');
            if (barFailed) barFailed.style.width = ((data.failed_count / total) * 100) + '%';

            // 4. Update Funnel Progress Bars safely
            const barSentFunnel = document.getElementById('bar-sent-funnel');
            if (barSentFunnel) {
                barSentFunnel.style.width = data.progress + '%';
            }
            const funnelProgressPercent = document.getElementById('funnel-progress-percent');
            if (funnelProgressPercent) {
                funnelProgressPercent.innerText = data.progress;
            }
            const barOpenFunnel = document.getElementById('bar-open-funnel');
            if (barOpenFunnel) {
                barOpenFunnel.style.width = data.open_rate + '%';
            }
            const barClickFunnel = document.getElementById('bar-click-funnel');
            if (barClickFunnel) {
                barClickFunnel.style.width = data.click_rate + '%';
            }
            
            // 4. Update Logs Table (if any recent logs provided)
            if (data.recent_logs && data.recent_logs.length > 0) {
                const tbody = document.getElementById('logs-body');
                tbody.innerHTML = '';
                data.recent_logs.forEach(log => {
                    const row = `<tr>
                        <td class="!pl-6"><span class="font-bold text-surface-900">${log.email_address}</span></td>
                        <td>
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border ${
                                log.status === 'sent' ? 'bg-green-50 text-green-700 border-green-100' :
                                log.status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-100' :
                                'bg-red-50 text-red-700 border-red-100'
                            }">
                                ${log.status}
                            </span>
                        </td>
                        <td class="font-mono text-[10px] text-surface-400">${log.message_id || 'N/A'}</td>
                        <td class="text-right !pr-6 text-surface-500 font-medium text-xs">${log.sent_at}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
            }

            // If completed, reload to show final view
            if (data.status === 'completed') {
                window.location.reload();
            }
        });
    }, pollMs);
    @endif
</script>
@endpush
