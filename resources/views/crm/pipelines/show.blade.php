@extends('layouts.app')
@section('title', $pipeline->name . ' — Kanban Board')
@section('heading')
    @php
        $switcherQuery = \App\Models\Pipeline::query();
        if (app()->has('team_user')) {
            $teamUserId = app('team_user')->id;
            $switcherQuery->where(function($q) use ($teamUserId) {
                $q->where('is_public', true)
                  ->orWhere('created_by_id', $teamUserId);
            });
        }
        $switcherPipelines = $switcherQuery->orderBy('name')->get();
        $isOwner = !app()->has('team_user') || $pipeline->created_by_id === (app()->has('team_user') ? app('team_user')->id : auth()->id());
    @endphp
    <div class="flex items-center gap-4">
        <span>{{ $pipeline->name }}</span>
        <div class="relative">
            <select onchange="if(this.value) window.location.href = this.value" class="appearance-none bg-white/80 border border-gray-200 rounded-sm px-3 py-1.5 pr-8 text-xs font-bold text-surface-700 focus:outline-none focus:ring-0 focus:border-brand cursor-pointer">
                <option value="">Switch Pipeline...</option>
                @foreach($switcherPipelines as $p)
                    <option value="{{ route('admin.pipelines.show', $p) }}" {{ $p->id === $pipeline->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-surface-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>
@endsection

@section('header-actions')
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.pipelines.index') }}"
            class="px-4 py-3 flex items-center rounded-sm bg-white border border-gray-100 text-surface-600 hover:text-surface-900 text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            All Pipelines
        </a>
    </div>
@endsection

@section('content')
<div x-data="kanbanBoard()">

    <div class="animate-slide-up">
        {{-- Search, Reports Toggle & Add Deal Bar --}}
        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" placeholder="Search deals..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-100 rounded-sm text-sm font-medium placeholder:text-surface-300 focus:border-brand focus:ring-0 focus:outline-none">
            </div>
            <div class="flex items-center gap-3">
                <button @click="showReports = !showReports"
                    class="px-4 py-2.5 flex items-center rounded-sm border border-gray-100 text-surface-600 hover:text-surface-900 text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer"
                    :class="showReports ? 'bg-brand/10 border-brand/30 text-brand' : 'bg-white'">
                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Reports
                </button>
                @if($pipeline->canPerformAction('add_deal'))
                <button @click="showAddDeal = true"
                    class="px-5 py-2.5 flex items-center rounded-sm bg-brand hover:bg-brand/90 text-white text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer">
                    <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                    Add Deal
                </button>
                @endif
            </div>
        </div>

        {{-- Analytics Panel (Collapsible) --}}
        <div x-show="showReports" x-cloak x-transition class="mb-6">
            <div class="bg-white rounded-sm border border-gray-100 p-6">
                <template x-if="analyticsData">
                    <div class="space-y-6">
                        {{-- Key Metrics Row --}}
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="text-center">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Win Rate</p>
                                <p class="text-2xl font-black text-emerald-600 mt-1" x-text="analyticsData.win_rate + '%'"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Avg Deal Value</p>
                                <p class="text-2xl font-black text-surface-900 mt-1" x-text="'₹' + Number(analyticsData.avg_deal_value).toLocaleString('en-IN')"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Avg Close Time</p>
                                <p class="text-2xl font-black text-surface-900 mt-1" x-text="analyticsData.avg_time_to_close + ' days'"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Won Value</p>
                                <p class="text-2xl font-black text-emerald-600 mt-1" x-text="'₹' + Number(analyticsData.won_value).toLocaleString('en-IN')"></p>
                            </div>
                            <div class="text-center">
                                <p class="text-[9px] font-black text-red-500 uppercase tracking-widest">Lost Value</p>
                                <p class="text-2xl font-black text-red-600 mt-1" x-text="'₹' + Number(analyticsData.lost_value).toLocaleString('en-IN')"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-t border-surface-100 pt-6">
                            {{-- Win/Loss Donut --}}
                            <div>
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-3">Win / Loss Ratio</p>
                                <div class="flex items-center gap-6">
                                    <div class="relative w-20 h-20">
                                        <svg viewBox="0 0 36 36" class="w-full h-full">
                                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f3f4f6" stroke-width="3"/>
                                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#10b981" stroke-width="3"
                                                    stroke-dasharray="100" :stroke-dashoffset="100 - analyticsData.win_rate"
                                                    stroke-linecap="round" transform="rotate(-90 18 18)"/>
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center text-xs font-black text-surface-900" x-text="analyticsData.win_rate + '%'"></div>
                                    </div>
                                    <div class="space-y-1.5 text-[10px]">
                                        <div class="flex items-center gap-2"><span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Won: <span class="font-black" x-text="analyticsData.won_count"></span></div>
                                        <div class="flex items-center gap-2"><span class="w-2 h-2 bg-red-500 rounded-full"></span> Lost: <span class="font-black" x-text="analyticsData.lost_count"></span></div>
                                        <div class="flex items-center gap-2"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> Open: <span class="font-black" x-text="analyticsData.open_count"></span></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Stage Distribution --}}
                            <div>
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-3">Stage Distribution</p>
                                <div class="space-y-2">
                                    <template x-for="stage in analyticsData.stage_distribution" :key="stage.name">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-bold text-surface-600 w-20 truncate" x-text="stage.name"></span>
                                            <div class="flex-1 bg-surface-100 rounded-full h-2 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500" :style="`width: ${analyticsData.total_deals > 0 ? (stage.deal_count / analyticsData.total_deals * 100) : 0}%; background: ${stage.color}`"></div>
                                            </div>
                                            <span class="text-[9px] font-black text-surface-700 w-6 text-right" x-text="stage.deal_count"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Monthly Forecast --}}
                            <div>
                                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest mb-3">Monthly Forecast</p>
                                <template x-if="analyticsData.monthly_forecast.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="month in analyticsData.monthly_forecast" :key="month.month">
                                            <div class="flex items-center justify-between gap-2 text-[10px]">
                                                <span class="font-semibold text-surface-600" x-text="month.label"></span>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-surface-500" x-text="month.count + ' deals'"></span>
                                                    <span class="font-black text-surface-900" x-text="'₹' + Number(month.value).toLocaleString('en-IN')"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="analyticsData.monthly_forecast.length === 0">
                                    <p class="text-[10px] text-surface-400 italic">No forecast data — set expected close dates on open deals.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="!analyticsData">
                    <div class="text-center py-8">
                        <p class="text-sm text-surface-400">Loading analytics...</p>
                    </div>
                </template>
            </div>
        </div>

        {{-- Sales Target Progress Bar --}}
        @php
            $wonDealsSum = $pipeline->deals->where('status', 'won')->sum('value');
            $monthlyTarget = $pipeline->monthly_target;
            $targetPercentage = $monthlyTarget > 0 ? min(100, round(($wonDealsSum / $monthlyTarget) * 100)) : 0;
        @endphp
        @if($monthlyTarget > 0)
            <div class="mb-6 p-5 bg-gradient-to-r from-surface-900 to-indigo-950 rounded-sm border border-surface-800 text-white shadow-sm relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-brand/20 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-indigo-300">Sales Target Progress</span>
                        <h3 class="text-lg font-black tracking-tight mt-1 flex items-baseline gap-2">
                            <span>₹{{ number_format($wonDealsSum) }}</span>
                            <span class="text-xs text-indigo-300 font-medium">won of ₹{{ number_format($monthlyTarget) }} target</span>
                        </h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-black text-brand-300" style="color: #6366f1;">{{ $targetPercentage }}%</span>
                        <span class="px-2 py-0.5 rounded-sm text-[8px] font-black border border-indigo-400 bg-indigo-500/20 uppercase tracking-wider">
                            {{ $targetPercentage >= 100 ? 'Target Achieved 🎉' : 'Active Goal' }}
                        </span>
                    </div>
                </div>
                
                <div class="mt-4 w-full bg-surface-800 rounded-full h-3.5 overflow-hidden p-0.5 border border-surface-700/50">
                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-brand to-emerald-500 transition-all duration-1000 shadow-[0_0_10px_rgba(99,102,241,0.5)]"
                         style="width: {{ $targetPercentage }}%"></div>
                </div>
            </div>
        @endif

        {{-- Pipeline Summary --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-sm border border-gray-100">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Total Deals</p>
                <p id="summary-total-deals" class="text-2xl font-black text-surface-900 mt-1">{{ $pipeline->deals->count() }}</p>
            </div>
            <div class="bg-white p-4 rounded-sm border border-gray-100">
                <p class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Pipeline Value</p>
                <p id="summary-total-value" class="text-2xl font-black text-surface-900 mt-1">₹{{ number_format($pipeline->deals->sum('value')) }}</p>
            </div>
            <div class="bg-white p-4 rounded-sm border border-gray-100">
                <p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Won</p>
                <p id="summary-won-deals" class="text-2xl font-black text-emerald-600 mt-1">{{ $pipeline->deals->where('status', 'won')->count() }}</p>
            </div>
            <div class="bg-white p-4 rounded-sm border border-gray-100">
                <p class="text-[9px] font-black text-red-500 uppercase tracking-widest">Lost</p>
                <p id="summary-lost-deals" class="text-2xl font-black text-red-600 mt-1">{{ $pipeline->deals->where('status', 'lost')->count() }}</p>
            </div>
        </div>

        {{-- Kanban Board --}}
        <div class="flex gap-4 overflow-x-auto pb-6 scrollbar" style="min-height: 500px;" id="kanban-board-columns">
            @foreach($pipeline->stages as $stage)
                <div class="flex-shrink-0 w-[300px] bg-surface-50 rounded-sm border border-surface-200 flex flex-col transition-all"
                     id="stage-{{ $stage->id }}"
                     data-stage-name="{{ $stage->name }}"
                     @dragover.prevent="$event.currentTarget.classList.add('drag-over')"
                     @dragleave="$event.currentTarget.classList.remove('drag-over')"
                     @drop="dropDeal($event, {{ $stage->id }}); $event.currentTarget.classList.remove('drag-over')">

                    {{-- Stage Header --}}
                    <div class="p-4 border-b border-surface-200 flex items-center justify-between cursor-grab active:cursor-grabbing select-none"
                         draggable="{{ $isOwner ? 'true' : 'false' }}"
                         @dragstart="dragStageStart($event, {{ $stage->id }})"
                         @dragend="dragStageEnd($event)"
                         @dragover.prevent=""
                         @drop="dropStage($event, {{ $stage->id }})">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-3 h-3 rounded-full shrink-0" style="background: {{ $stage->color }}"></div>
                            <h3 class="text-xs font-black text-surface-900 uppercase tracking-widest truncate">{{ $stage->name }}</h3>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span id="stage-count-{{ $stage->id }}" class="text-[10px] font-black text-surface-400 bg-white px-2 py-0.5 rounded-full border border-surface-200">
                                {{ $stage->deals->count() }}
                            </span>
                            @if($isOwner)
                            <button @click="editStage({{ json_encode(['id' => $stage->id, 'name' => $stage->name, 'color' => $stage->color, 'automation_action' => $stage->automation_action, 'automation_value' => $stage->automation_value]) }})"
                                    class="text-surface-300 hover:text-brand p-0.5 cursor-pointer transition-colors" title="Edit Stage">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                            <button @click="deleteStage({{ $stage->id }}, '{{ $stage->name }}')"
                                    class="text-surface-300 hover:text-red-500 p-0.5 cursor-pointer transition-colors" title="Delete Stage">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Stage Value Bar --}}
                    <div class="px-4 py-2 bg-white/50 border-b border-surface-100">
                        <p id="stage-value-{{ $stage->id }}" data-value="{{ $stage->deals->sum('value') }}" class="text-[9px] font-bold text-surface-400">₹{{ number_format($stage->deals->sum('value')) }}</p>
                    </div>

                    {{-- Deal List --}}
                    <div class="p-3 flex-1 space-y-2.5 overflow-y-auto" style="max-height: 500px;" id="stage-deals-{{ $stage->id }}">
                        @foreach($stage->deals as $deal)
                            @php
                                $idleDays = $deal->updated_at->diffInDays(now());
                                $rottingDays = $pipeline->rotting_days ?? 14;
                                $isRotting = $deal->status === 'open' && $idleDays >= $rottingDays;
                                $isWarning = $deal->status === 'open' && $idleDays >= max(1, round($rottingDays / 2));
                            @endphp
                            <div class="deal-card bg-white rounded-sm border border-surface-200 p-3 hover:border-brand/40 transition-colors group relative {{ $pipeline->canPerformAction('move_deal') ? 'cursor-grab active:cursor-grabbing' : 'cursor-default' }} {{ $isRotting ? 'border-l-2 border-l-rose-500 bg-rose-50/10' : ($isWarning ? 'border-l-2 border-l-amber-500 bg-amber-50/10' : '') }}"
                                 draggable="{{ $pipeline->canPerformAction('move_deal') ? 'true' : 'false' }}"
                                 id="deal-{{ $deal->id }}"
                                 data-deal-id="{{ $deal->id }}"
                                 data-value="{{ $deal->value }}"
                                 data-status="{{ $deal->status }}"
                                 data-stage-id="{{ $stage->id }}"
                                 x-show="!search || '{{ strtolower($deal->title) }}'.includes(search.toLowerCase()) || '{{ strtolower($deal->contact?->name ?? '') }}'.includes(search.toLowerCase())"
                                 @dragstart="dragStart($event, {{ $deal->id }})"
                                 @dragend="dragEnd($event)"
                                 @mouseenter="showTooltip({{ json_encode($deal) }}, {{ json_encode($deal->contact) }})"
                                 @mousemove="trackMouse($event)"
                                 @mouseleave="hideTooltip()">

                                <!-- Top: Title, Value, Assignee Avatar -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        @if($pipeline->canPerformAction('move_deal'))
                                            <i class="fa-solid fa-grip-vertical text-surface-300 hover:text-surface-600 cursor-grab active:cursor-grabbing mr-0.5 shrink-0" title="Drag to reorder/move"></i>
                                        @endif
                                        <span id="deal-status-indicator-{{ $deal->id }}" class="w-1.5 h-1.5 rounded-full shrink-0 {{ $deal->status === 'won' ? 'bg-emerald-500' : ($deal->status === 'lost' ? 'bg-red-500' : 'bg-blue-500') }}"></span>
                                        <h4 class="font-bold text-xs text-surface-900 truncate" title="{{ $deal->title }}">{{ $deal->title }}</h4>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="text-xs font-black text-surface-900">₹{{ number_format($deal->value) }}</span>
                                        @if($deal->assignee)
                                            <span class="w-5 h-5 rounded-full bg-brand/10 text-brand text-[7px] font-black flex items-center justify-center border border-brand/20" title="{{ $deal->assignee->name }}">
                                                {{ strtoupper(substr($deal->assignee->name, 0, 2)) }}
                                            </span>
                                        @else
                                            <span class="w-5 h-5 rounded-full border border-dashed border-surface-300 flex items-center justify-center text-surface-300" title="Unassigned">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Tags pill list -->
                                @if(!empty($deal->tags) && is_array($deal->tags))
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach($deal->tags as $tag)
                                            <span class="px-1.5 py-0.5 rounded-sm text-[8px] font-black uppercase tracking-wide bg-surface-100 text-surface-600 border border-surface-200">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Middle: Contact, Date, Notes, Idle Alert -->
                                <div class="mt-2 space-y-1.5 text-[10px] text-surface-500 border-t border-surface-100/50 pt-2">
                                    @if($deal->contact)
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <svg class="w-3 h-3 text-surface-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <a href="{{ route('admin.contacts.show', $deal->email_id) }}" @click.stop=""
                                               class="font-semibold text-surface-700 hover:text-brand hover:underline truncate max-w-[170px]">
                                                {{ $deal->contact->name ?? $deal->contact->email }}
                                            </a>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 text-surface-300 italic">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span>No contact</span>
                                        </div>
                                    @endif

                                    @if($deal->expected_close_at)
                                        <div class="flex items-center gap-1.5 text-surface-400">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span class="font-medium text-[11px]">{{ $deal->expected_close_at->format('M d, Y') }}</span>
                                        </div>
                                    @endif

                                    <div x-data="{ localNote: {{ json_encode($deal->notes ?? '') }}, isEditing: false }" @click.stop="" class="relative">
                                        <!-- Read-only View -->
                                        <div x-show="localNote && !isEditing"
                                             @click="isEditing = true; $nextTick(() => $refs.noteInput.focus())"
                                             class="cursor-pointer hover:bg-surface-100/80 hover:border-brand/40 transition-all duration-150 flex items-start gap-1.5 text-surface-400 italic text-xs bg-surface-50 p-2 rounded-sm border border-surface-100 mb-1.5"
                                             title="Click to edit note">
                                            <span class="font-bold text-surface-500 shrink-0">Note:</span>
                                            <span class="text-surface-700 font-medium break-words max-w-[180px] truncate" x-text="localNote"></span>
                                        </div>

                                        <!-- Editable Input View -->
                                        <input type="text"
                                               x-ref="noteInput"
                                               x-show="!localNote || isEditing"
                                               x-model="localNote"
                                               placeholder="Add note..."
                                               draggable="false"
                                               @dragstart.stop.prevent=""
                                               @blur="updateDealNote({{ $deal->id }}, {{ json_encode($deal->title) }}, localNote, $event.target).then(success => { if (success) isEditing = false; })"
                                               @keydown.enter="updateDealNote({{ $deal->id }}, {{ json_encode($deal->title) }}, localNote, $event.target).then(success => { if (success) { isEditing = false; $event.target.blur(); } })"
                                               class="w-full p-2 bg-surface-50 border border-surface-200 rounded-sm text-xs text-surface-600 placeholder:text-surface-300 focus:bg-white focus:border-brand focus:ring-0 focus:outline-none transition-all">
                                    </div>

                                    {{-- Rotting Deal Alert --}}
                                    @if($isRotting)
                                        <div class="flex items-center gap-1 text-[8px] font-black text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-sm border border-rose-200">
                                            <i class="fa-solid fa-skull text-[9px] text-rose-500 animate-pulse"></i>
                                            ROTTING — {{ $idleDays }} days idle
                                        </div>
                                    @elseif($isWarning)
                                        <div class="flex items-center gap-1 text-[8px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-sm border border-amber-200">
                                            <i class="fa-solid fa-hourglass-half text-[9px] text-amber-500"></i>
                                            STALE — {{ $idleDays }} days
                                        </div>
                                    @endif
                                </div>

                                <!-- Bottom: Status Badge + Actions (Always Visible) -->
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-surface-100/50">
                                    <span id="deal-status-badge-{{ $deal->id }}" class="text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm border
                                        {{ $deal->status === 'won' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($deal->status === 'lost' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-blue-50 text-blue-700 border-blue-200') }}">
                                        {{ $deal->status }}
                                    </span>

                                    <div class="flex items-center gap-1 shrink-0">
                                        {{-- Quick Email --}}
                                        @if($deal->contact)
                                        <a href="mailto:{{ $deal->contact->email }}" @click.stop="" title="Send Email"
                                           class="text-surface-300 hover:text-brand border border-surface-200 rounded-sm p-1 transition-colors cursor-pointer">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </a>
                                        @endif
                                        {{-- Quick WhatsApp --}}
                                        @if($deal->contact && $deal->contact->whatsapp_number)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $deal->contact->whatsapp_number) }}" target="_blank" @click.stop="" title="WhatsApp"
                                           class="text-surface-300 hover:text-emerald-600 border border-surface-200 rounded-sm p-1 transition-colors cursor-pointer">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        </a>
                                        @endif
                                        {{-- Timeline --}}
                                        <button @click.prevent.stop="openTimeline({{ $deal->id }}, '{{ addslashes($deal->title) }}')"
                                                class="text-surface-300 hover:text-indigo-600 border border-surface-200 rounded-sm p-1 transition-colors cursor-pointer" title="Activity Timeline">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        {{-- Edit --}}
                                        <button @click.prevent.stop="editDeal({{ json_encode($deal) }})"
                                                class="text-surface-300 hover:text-brand border border-surface-200 rounded-sm p-1 transition-colors cursor-pointer" title="Edit Deal">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        {{-- Delete --}}
                                        @if($pipeline->canPerformAction('delete_deal'))
                                        <button @click.prevent.stop="deleteDeal({{ $deal->id }})"
                                                class="text-surface-300 hover:text-red-600 border border-surface-200 rounded-sm p-1 transition-colors cursor-pointer" title="Delete Deal">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Add Stage Column (Owner only) --}}
            @if($isOwner)
            <div class="flex-shrink-0 w-[300px] bg-surface-50/50 rounded-sm border-2 border-dashed border-surface-200 flex flex-col items-center justify-center min-h-[200px] cursor-pointer hover:border-brand/40 hover:bg-brand/5 transition-all"
                 @click="showAddStage = true">
                <svg class="w-8 h-8 text-surface-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <p class="text-xs font-bold text-surface-400">Add Stage</p>
            </div>
            @endif
        </div>
    </div> {{-- Close animate-slide-up --}}

    {{-- Modals & Tooltips --}}

    {{-- Add Deal Modal --}}
    <div x-show="showAddDeal" x-cloak class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" @click.self="showAddDeal = false">
        <div class="bg-white rounded-sm border border-surface-200 w-full max-w-lg animate-slide-up" @keydown.escape.window="showAddDeal = false">
            <div class="p-6 border-b border-surface-100">
                <h3 class="text-lg font-black text-surface-900">Add New Deal</h3>
                <p class="text-sm text-surface-500 mt-1">Create a deal and assign it to a pipeline stage.</p>
            </div>
            <form action="{{ route('admin.pipelines.deals.store', $pipeline) }}" method="POST">
                @csrf
                <div class="p-6 space-y-5">
                    <div>
                        <label class="form-label">Deal Title *</label>
                        <input type="text" name="title" class="form-input" placeholder="e.g. Enterprise License" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Value (₹)</label>
                            <input type="number" name="value" class="form-input" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div>
                            <label class="form-label">Stage *</label>
                            <select name="pipeline_stage_id" class="form-select" required>
                                @foreach($pipeline->stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Link Contact</label>
                            <select name="email_id" class="form-select">
                                <option value="">— No contact —</option>
                                @foreach($contacts as $c)
                                    <option value="{{ $c->id }}">{{ $c->name ?? $c->email }} ({{ $c->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to_id" class="form-select">
                                <option value="">— Unassigned —</option>
                                @foreach($teamMembers as $tm)
                                    <option value="{{ $tm->id }}">{{ $tm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Expected Close Date</label>
                            <input type="date" name="expected_close_at" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Tags (comma-separated)</label>
                            <input type="text" name="tags" class="form-input" placeholder="e.g. VIP, Enterprise">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Any notes about this deal..."></textarea>
                    </div>
                </div>
                <div class="p-6 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showAddDeal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Deal</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Deal Modal --}}
    <div x-show="showEditDeal" x-cloak class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" @click.self="showEditDeal = false">
        <div class="bg-white rounded-sm border border-surface-200 w-full max-w-lg animate-slide-up" @keydown.escape.window="showEditDeal = false">
            <div class="p-6 border-b border-surface-100">
                <h3 class="text-lg font-black text-surface-900">Edit Deal</h3>
                <p class="text-sm text-surface-500 mt-1">Update deal details, status, or notes.</p>
            </div>
            <form @submit.prevent="submitEditDeal">
                <div class="p-6 space-y-5">
                    <div>
                        <label class="form-label">Deal Title *</label>
                        <input type="text" x-model="editingDeal.title" class="form-input" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Value (₹)</label>
                            <input type="number" x-model="editingDeal.value" class="form-input" step="0.01" min="0">
                        </div>
                        <div>
                            <label class="form-label">Status *</label>
                            <select x-model="editingDeal.status" class="form-select" required>
                                <option value="open">Open</option>
                                <option value="won">Won</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Link Contact</label>
                            <select x-model="editingDeal.email_id" class="form-select">
                                <option value="">— No contact —</option>
                                @foreach($contacts as $c)
                                    <option value="{{ $c->id }}">{{ $c->name ?? $c->email }} ({{ $c->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Assign To</label>
                            <select x-model="editingDeal.assigned_to_id" class="form-select">
                                <option value="">— Unassigned —</option>
                                @foreach($teamMembers as $tm)
                                    <option value="{{ $tm->id }}">{{ $tm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Expected Close Date</label>
                            <input type="date" x-model="editingDeal.expected_close_at" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Tags (comma-separated)</label>
                            <input type="text" x-model="editingDeal.tags" class="form-input" placeholder="e.g. VIP, Enterprise">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea x-model="editingDeal.notes" class="form-input" rows="3"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showEditDeal = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Stage Modal --}}
    <div x-show="showAddStage" x-cloak class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" @click.self="showAddStage = false">
        <div class="bg-white rounded-sm border border-surface-200 w-full max-w-sm animate-slide-up">
            <div class="p-6 border-b border-surface-100">
                <h3 class="text-lg font-black text-surface-900">Add New Stage</h3>
            </div>
            <form @submit.prevent="submitAddStage">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="form-label">Stage Name *</label>
                        <input type="text" x-model="newStage.name" class="form-input" placeholder="e.g. Negotiation" required>
                    </div>
                    <div>
                        <label class="form-label">Color</label>
                        <input type="color" x-model="newStage.color" class="w-full h-10 rounded-sm border border-surface-200 cursor-pointer">
                    </div>
                </div>
                <div class="p-6 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showAddStage = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stage</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Stage Modal --}}
    <div x-show="showEditStage" x-cloak class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center p-4" @click.self="showEditStage = false">
        <div class="bg-white rounded-sm border border-surface-200 w-full max-w-sm animate-slide-up">
            <div class="p-6 border-b border-surface-100">
                <h3 class="text-lg font-black text-surface-900">Edit Stage</h3>
            </div>
            <form @submit.prevent="submitEditStage">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="form-label">Stage Name *</label>
                        <input type="text" x-model="editingStage.name" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Color</label>
                        <input type="color" x-model="editingStage.color" class="w-full h-10 rounded-sm border border-surface-200 cursor-pointer">
                    </div>
                    <div>
                        <label class="form-label">Drop Automation Action</label>
                        <select x-model="editingStage.automation_action" class="form-select">
                            <option value="">— No action —</option>
                            <option value="tag_contact">Tag Contact</option>
                            <option value="subscribe_email">Subscribe to List</option>
                            <option value="unsubscribe_email">Unsubscribe from List</option>
                        </select>
                    </div>
                    <div x-show="editingStage.automation_action === 'tag_contact'">
                        <label class="form-label">Tag Name</label>
                        <input type="text" x-model="editingStage.automation_value" class="form-input" placeholder="e.g. LeadTag">
                    </div>
                </div>
                <div class="p-6 border-t border-surface-100 flex justify-end gap-3">
                    <button type="button" @click="showEditStage = false" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Slide-out Drawer Dashboard -->
    <div x-show="showTimeline" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <!-- Background overlay -->
            <div class="absolute inset-0 bg-surface-900/30 transition-opacity"
                 x-show="showTimeline"
                 x-transition:enter="ease-in-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showTimeline = false"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="pointer-events-auto w-screen max-w-md transform bg-white shadow-2xl transition-all duration-300 border-l border-surface-200 flex flex-col h-full"
                     x-show="showTimeline"
                     x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full">
                    
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-surface-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-black text-surface-900" id="slide-over-title">Deal Dashboard</h2>
                            <p class="text-xs text-surface-400 font-semibold truncate max-w-[280px]" x-text="timelineDealTitle"></p>
                        </div>
                        <button @click="showTimeline = false" class="rounded-sm text-surface-400 hover:text-surface-700 focus:outline-none cursor-pointer">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Tab Buttons -->
                    <div class="flex border-b border-surface-150 bg-surface-50 p-1">
                        <button @click="drawerTab = 'timeline'"
                                :class="drawerTab === 'timeline' ? 'bg-white text-surface-900 shadow-sm border-surface-200' : 'text-surface-500 hover:text-surface-700'"
                                class="flex-1 py-2 text-center text-xs font-black uppercase tracking-wider rounded-sm transition-all focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-clock-rotate-left mr-1.5"></i>Timeline
                        </button>
                        <button @click="drawerTab = 'tasks'"
                                :class="drawerTab === 'tasks' ? 'bg-white text-surface-900 shadow-sm border-surface-200' : 'text-surface-500 hover:text-surface-700'"
                                class="flex-1 py-2 text-center text-xs font-black uppercase tracking-wider rounded-sm transition-all focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-list-check mr-1.5"></i>Tasks & Reminders
                        </button>
                    </div>

                    <!-- Drawer Contents -->
                    <div class="flex-grow overflow-y-auto p-6 space-y-6 flex flex-col justify-between h-0">
                        
                        <!-- TAB 1: Timeline & Comments -->
                        <div x-show="drawerTab === 'timeline'" class="space-y-6 flex-1 flex flex-col justify-between h-full">
                            {{-- Activity list --}}
                            <div class="overflow-y-auto flex-grow max-h-[50vh] pr-1 scrollbar relative">
                                <template x-if="timelineActivities.length > 0">
                                    <div class="relative pl-6">
                                        <div class="absolute left-2 top-0 bottom-0 w-px bg-surface-200"></div>
                                        <template x-for="activity in timelineActivities" :key="activity.id">
                                            <div class="relative mb-5">
                                                <div class="absolute -left-4 w-3.5 h-3.5 rounded-full border-2 border-white flex items-center justify-center shadow-sm"
                                                     :class="{
                                                         'bg-emerald-500': activity.type === 'created' || (activity.type === 'status_changed' && activity.new_value === 'won'),
                                                         'bg-red-500': activity.type === 'deleted' || (activity.type === 'status_changed' && activity.new_value === 'lost'),
                                                         'bg-indigo-500': activity.type === 'moved',
                                                         'bg-amber-500': activity.type === 'assigned',
                                                         'bg-sky-500': activity.type === 'note_added',
                                                         'bg-surface-400': activity.type === 'edited'
                                                     }">
                                                </div>
                                                <div class="bg-surface-50 border border-surface-200 p-3 rounded-sm">
                                                    <div class="flex items-center justify-between text-[9px] text-surface-400 font-bold uppercase tracking-wider mb-1">
                                                        <span x-text="activity.performer"></span>
                                                        <span x-text="activity.time_ago"></span>
                                                    </div>
                                                    <p class="text-xs font-semibold text-surface-800 break-words" x-html="activity.description.replace(/\n/g, '<br>')"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="timelineActivities.length === 0">
                                    <div class="text-center py-12 text-surface-400 italic text-xs">
                                        No activity logs found.
                                    </div>
                                </template>
                            </div>

                            {{-- Comment Input Form --}}
                            <div class="border-t border-surface-150 pt-4 mt-auto">
                                <form @submit.prevent="submitComment">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-surface-400 mb-2">Add Comment / Note</label>
                                    <textarea x-model="newComment" rows="2" class="w-full text-xs rounded-sm border-surface-200 focus:ring-0 focus:border-brand placeholder:text-surface-300" placeholder="Type comments here..."></textarea>
                                    <div class="flex justify-end mt-2">
                                        <button type="submit" :disabled="!newComment.trim()" class="px-3.5 py-1.5 bg-brand text-white rounded-sm text-[10px] font-black uppercase tracking-widest hover:bg-brand/90 disabled:opacity-50 cursor-pointer">
                                            Post Comment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- TAB 2: Tasks & Reminders -->
                        <div x-show="drawerTab === 'tasks'" class="space-y-6 flex-1 flex flex-col justify-between h-full">
                            {{-- Task list --}}
                            <div class="overflow-y-auto flex-grow max-h-[50vh] pr-1 scrollbar space-y-3">
                                <template x-if="dealTasksList.length > 0">
                                    <div class="space-y-2">
                                        <template x-for="task in dealTasksList" :key="task.id">
                                            <div class="flex items-center justify-between p-3 bg-surface-50 border rounded-sm transition-all"
                                                 :class="task.is_completed ? 'border-surface-200 opacity-60' : 'border-surface-200'">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <input type="checkbox" :checked="task.is_completed" @change="toggleTask(task)"
                                                           class="rounded-sm border-surface-300 text-brand focus:ring-0 cursor-pointer">
                                                    <div class="min-w-0">
                                                        <p class="text-xs font-bold text-surface-800 truncate"
                                                           :class="task.is_completed ? 'line-through text-surface-400' : ''"
                                                           x-text="task.title"></p>
                                                        <template x-if="task.due_date">
                                                            <span class="text-[9px] text-surface-400 font-bold block mt-0.5" x-text="'Due: ' + task.due_date"></span>
                                                        </template>
                                                    </div>
                                                </div>
                                                <button @click="deleteTask(task.id)" class="text-surface-300 hover:text-red-500 p-1 cursor-pointer transition-colors">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="dealTasksList.length === 0">
                                    <div class="text-center py-12 text-surface-400 italic text-xs">
                                        No tasks or reminders set.
                                    </div>
                                </template>
                            </div>

                            {{-- Add Task Form --}}
                            <div class="border-t border-surface-150 pt-4 mt-auto">
                                <form @submit.prevent="submitTask">
                                    <label class="block text-[10px] font-black uppercase tracking-wider text-surface-400 mb-2">New Task / Reminder</label>
                                    <div class="space-y-3">
                                        <input type="text" x-model="newTaskTitle" class="w-full text-xs rounded-sm border-surface-200 focus:ring-0 focus:border-brand placeholder:text-surface-300" placeholder="e.g. Call client for follow-up" required>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[9px] font-bold text-surface-400 uppercase tracking-widest mb-1">Due Date</label>
                                                <input type="date" x-model="newTaskDueDate" class="w-full text-xs rounded-sm border-surface-200 focus:ring-0 focus:border-brand">
                                            </div>
                                            <div class="flex items-end justify-end">
                                                <button type="submit" :disabled="!newTaskTitle.trim()" class="px-3.5 py-2.5 bg-brand text-white rounded-sm text-[10px] font-black uppercase tracking-widest hover:bg-brand/90 disabled:opacity-50 cursor-pointer w-full text-center">
                                                    Add Task
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Global Hover Tooltip --}}
    <div x-show="hoveredDeal" x-cloak
         class="fixed bg-white border border-surface-200 p-4 z-[100] w-80 rounded-sm shadow-lg max-h-[85vh] overflow-y-auto cursor-default"
         :style="`left: ${tooltipX}px; top: ${tooltipY}px;`"
         @mouseenter="cancelHideTooltip()"
         @mouseleave="hideTooltip()"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

        <template x-if="hoveredDeal">
            <div class="space-y-3">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-sm border"
                              :class="hoveredDeal.status === 'won' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (hoveredDeal.status === 'lost' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-blue-50 text-blue-700 border-blue-200')"
                              x-text="hoveredDeal.status">
                        </span>
                        <span class="text-xs font-black text-brand" x-text="new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(hoveredDeal.value)"></span>
                    </div>
                    <h4 class="text-sm font-black text-surface-900 leading-tight" x-text="hoveredDeal.title"></h4>
                </div>

                <div class="border-t border-surface-100 pt-2.5">
                    <p class="text-[9px] font-bold text-surface-400 uppercase tracking-wider mb-1.5">Contact Info</p>
                    <template x-if="hoveredContact">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-surface-900 truncate" x-text="hoveredContact.name || 'Unnamed'"></div>
                                    <div class="text-[10px] font-semibold text-surface-600 truncate" x-text="hoveredContact.email"></div>
                                </div>
                                <button @click="copyToClipboard(hoveredContact.email, $event)" 
                                        class="text-surface-300 hover:text-brand cursor-pointer shrink-0 transition-colors p-1 rounded-sm border border-surface-200" title="Copy Email">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </button>
                            </div>

                            <div x-show="hoveredContact.whatsapp_number" class="text-[10px] font-semibold text-surface-600 flex items-center justify-between gap-2 border-t border-surface-100 pt-1.5 mt-1.5">
                                <div class="flex items-center gap-1 min-w-0">
                                    <svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span class="truncate" x-text="hoveredContact.whatsapp_number"></span>
                                </div>
                                <button @click="copyToClipboard(hoveredContact.whatsapp_number, $event)" 
                                        class="text-surface-300 hover:text-emerald-600 cursor-pointer shrink-0 transition-colors p-1 rounded-sm border border-surface-200" title="Copy WhatsApp Number">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Verification & Subscription Badges --}}
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <template x-if="hoveredContact.status">
                                    <span class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-sm border"
                                          :class="hoveredContact.status === 'valid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'"
                                          x-text="'Verification: ' + hoveredContact.status"></span>
                                </template>
                                <template x-if="hoveredContact.subscription_status">
                                    <span class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-sm border"
                                          :class="hoveredContact.subscription_status === 'subscribed' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-surface-100 text-surface-600 border-surface-200'"
                                          x-text="hoveredContact.subscription_status"></span>
                                </template>
                            </div>

                            {{-- Engagement / Lead Score --}}
                            <template x-if="hoveredContact.engagement_score !== undefined && hoveredContact.engagement_score !== null">
                                <div class="pt-1.5">
                                    <div class="flex items-center justify-between text-[9px] font-bold text-surface-400 mb-0.5">
                                        <span>LEAD ENGAGEMENT</span>
                                        <span class="text-brand" x-text="hoveredContact.engagement_score + '%'"></span>
                                    </div>
                                    <div class="w-full bg-surface-100 rounded-full h-1 overflow-hidden">
                                        <div class="h-full rounded-full bg-brand transition-all duration-300" :style="`width: ${hoveredContact.engagement_score}%`"></div>
                                    </div>
                                </div>
                            </template>

                            {{-- Tags --}}
                            <template x-if="hoveredContact.tags && hoveredContact.tags.length > 0">
                                <div class="flex flex-wrap gap-1 pt-1.5">
                                    <template x-for="tag in hoveredContact.tags" :key="tag">
                                        <span class="text-[7px] font-black uppercase tracking-wider bg-brand/5 text-brand px-1 py-0.5 rounded-sm border border-brand/10" x-text="tag"></span>
                                    </template>
                                </div>
                            </template>

                            {{-- Custom Fields / Meta --}}
                            <template x-if="hoveredContact.meta && Object.keys(hoveredContact.meta).length > 0">
                                <div class="border-t border-surface-100 pt-2 mt-2">
                                    <p class="text-[9px] font-bold text-surface-400 uppercase tracking-wider mb-1.5">Additional Details</p>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <template x-for="(value, key) in hoveredContact.meta" :key="key">
                                            <template x-if="value && typeof value !== 'object'">
                                                <div class="bg-surface-50 p-1.5 rounded-sm border border-surface-150 text-[10px]">
                                                    <span class="block text-[7px] font-black text-surface-400 uppercase tracking-wider mb-0.5" x-text="key.replace(/_/g, ' ')"></span>
                                                    <span class="font-bold text-surface-700 break-all" x-text="value"></span>
                                                </div>
                                            </template>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    <template x-if="!hoveredContact">
                        <div class="text-xs text-surface-400 italic">No contact details linked</div>
                    </template>
                </div>

                <div class="border-t border-surface-100 pt-2.5" x-show="hoveredDeal.updated_at">
                    <p class="text-[9px] font-bold text-surface-400 uppercase tracking-wider mb-1">Last Updated</p>
                    <div class="text-xs font-semibold text-surface-700" x-text="hoveredDeal.updated_at ? new Date(hoveredDeal.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : ''"></div>
                </div>

                <div class="border-t border-surface-100 pt-2.5" x-show="hoveredDeal.expected_close_at">
                    <p class="text-[9px] font-bold text-surface-400 uppercase tracking-wider mb-1">Expected Close</p>
                    <div class="text-xs font-semibold text-surface-700" x-text="new Date(hoveredDeal.expected_close_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })"></div>
                </div>

                <div class="border-t border-surface-100 pt-2.5" x-show="hoveredDeal.notes">
                    <p class="text-[9px] font-bold text-surface-400 uppercase tracking-wider mb-1">Notes</p>
                    <p class="text-[11px] text-surface-600 italic leading-relaxed whitespace-pre-line" x-text="hoveredDeal.notes"></p>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function kanbanBoard() {
    return {
        search: '',
        showAddDeal: false,
        showEditDeal: false,
        showAddStage: false,
        showEditStage: false,
        showTimeline: false,
        showReports: false,
        draggedDealId: null,
        draggedStageId: null,
        hoveredDeal: null,
        hoveredContact: null,
        updatedNotes: {},
        tooltipX: 0,
        tooltipY: 0,
        hideTimeout: null,
        analyticsData: null,
        timelineActivities: [],
        timelineDealTitle: '',
        editingDeal: { id: null, title: '', value: 0, status: 'open', email_id: '', assigned_to_id: '', expected_close_at: '', notes: '', tags: '' },
        newStage: { name: '', color: '#6366f1' },
        editingStage: { id: null, name: '', color: '#6366f1', automation_action: '', automation_value: '' },
        drawerTab: 'timeline',
        dealTasksList: [],
        newComment: '',
        newTaskTitle: '',
        newTaskDueDate: '',
        timelineDealId: null,

        init() {
            this.$watch('showReports', (val) => {
                if (val && !this.analyticsData) this.loadAnalytics();
            });

            // Handle scroll and highlight if hash contains #deal-ID
            this.$nextTick(() => {
                const hash = window.location.hash;
                if (hash && hash.startsWith('#deal-')) {
                    const dealId = hash.substring(6);
                    const dealEl = document.getElementById('deal-' + dealId);
                    if (dealEl) {
                        setTimeout(() => {
                            dealEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                            
                            // Highlight visual cue
                            dealEl.classList.remove('border-surface-200');
                            dealEl.classList.add('border-brand', 'ring-2', 'ring-brand/20', 'bg-brand/5');
                            
                            setTimeout(() => {
                                dealEl.classList.remove('ring-2', 'ring-brand/20', 'bg-brand/5');
                                dealEl.classList.add('border-surface-200');
                                dealEl.classList.remove('border-brand');
                            }, 3000);
                        }, 500); // Small delay to allow layout to settle
                    }
                }
            });
        },

        trackMouse(event) {
            const tooltipEl = document.querySelector('[x-show="hoveredDeal"]');
            const tw = tooltipEl && tooltipEl.offsetWidth ? tooltipEl.offsetWidth : 320;
            const th = tooltipEl && tooltipEl.offsetHeight ? tooltipEl.offsetHeight : 450;
            
            let x = event.clientX + 15, y = event.clientY + 15;
            if (x + tw > window.innerWidth) x = event.clientX - tw - 15;
            if (y + th > window.innerHeight) y = event.clientY - th - 15;
            if (x < 10) x = 10;
            if (y < 10) y = 10;
            this.tooltipX = x;
            this.tooltipY = y;
        },

        showTooltip(deal, contact) {
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
                this.hideTimeout = null;
            }
            if (this.updatedNotes && this.updatedNotes[deal.id] !== undefined) {
                deal.notes = this.updatedNotes[deal.id];
            }
            this.hoveredDeal = deal;
            this.hoveredContact = contact;
        },

        hideTooltip() {
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
            }
            this.hideTimeout = setTimeout(() => {
                this.hoveredDeal = null;
                this.hoveredContact = null;
                this.hideTimeout = null;
            }, 300); // 300ms hover bridge buffer
        },

        cancelHideTooltip() {
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
                this.hideTimeout = null;
            }
        },

        copyToClipboard(text, event) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.currentTarget;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        },

        // Deal CRUD
        editDeal(deal) {
            this.editingDeal = {
                id: deal.id, title: deal.title, value: deal.value, status: deal.status,
                email_id: deal.email_id || '', assigned_to_id: deal.assigned_to_id || '',
                expected_close_at: deal.expected_close_at ? deal.expected_close_at.split('T')[0] : '',
                notes: deal.notes || '',
                tags: Array.isArray(deal.tags) ? deal.tags.join(', ') : (deal.tags || '')
            };
            this.showEditDeal = true;
        },

        submitEditDeal() {
            fetch('{{ route("admin.pipelines.deals.update", ":id") }}'.replace(':id', this.editingDeal.id), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(this.editingDeal)
            })
            .then(r => r.json()).then(d => { if (d.success) window.location.reload(); else alert(d.message || 'Error'); })
            .catch(e => { console.error(e); alert('Error updating deal.'); });
        },

        updateDealNote(dealId, title, note, inputEl) {
            inputEl.classList.remove('border-surface-200', 'border-emerald-500', 'border-red-500');
            inputEl.classList.add('border-brand');

            return fetch('{{ route("admin.pipelines.deals.update", ":id") }}'.replace(':id', dealId), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({
                    title: title,
                    notes: note
                })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    inputEl.classList.remove('border-brand');
                    inputEl.classList.add('border-emerald-500');
                    setTimeout(() => {
                        inputEl.classList.remove('border-emerald-500');
                        inputEl.classList.add('border-surface-200');
                    }, 1500);

                    this.updatedNotes[dealId] = note;
                    return true;
                } else {
                    inputEl.classList.remove('border-brand');
                    inputEl.classList.add('border-red-500');
                    alert(d.message || 'Error updating note.');
                    return false;
                }
            })
            .catch(e => {
                console.error(e);
                inputEl.classList.remove('border-brand');
                inputEl.classList.add('border-red-500');
                alert('Error updating deal note.');
                return false;
            });
        },

        deleteDeal(dealId) {
            if (!confirm('Delete this deal?')) return;
            fetch('{{ route("admin.pipelines.deals.destroy", ":id") }}'.replace(':id', dealId), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json()).then(d => { if (d.success) document.getElementById('deal-' + dealId)?.remove(); });
        },

        // Stage CRUD
        submitAddStage() {
            fetch('{{ route("admin.pipelines.stages.store", $pipeline) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(this.newStage)
            })
            .then(r => r.json()).then(d => { if (d.success) window.location.reload(); })
            .catch(e => console.error(e));
        },

        editStage(stage) {
            this.editingStage = {
                id: stage.id,
                name: stage.name,
                color: stage.color,
                automation_action: stage.automation_action || '',
                automation_value: stage.automation_value || ''
            };
            this.showEditStage = true;
        },

        submitEditStage() {
            fetch('{{ route("admin.pipelines.stages.update", ":id") }}'.replace(':id', this.editingStage.id), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(this.editingStage)
            })
            .then(r => r.json()).then(d => { if (d.success) window.location.reload(); })
            .catch(e => console.error(e));
        },

        deleteStage(stageId, stageName) {
            if (!confirm(`Delete stage "${stageName}"? Deals will be moved to the first remaining stage.`)) return;
            fetch('{{ route("admin.pipelines.stages.destroy", ":id") }}'.replace(':id', stageId), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json()).then(d => { if (d.success) window.location.reload(); });
        },

        // Stage Drag & Drop (Column Reordering)
        dragStageStart(event, stageId) {
            if (this.draggedDealId) {
                event.preventDefault();
                return;
            }
            this.draggedStageId = stageId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/stage-id', stageId);

            const col = document.getElementById('stage-' + stageId);
            if (col) col.style.opacity = '0.4';
        },

        dragStageEnd(event) {
            if (this.draggedStageId) {
                const col = document.getElementById('stage-' + this.draggedStageId);
                if (col) col.style.opacity = '1';
                this.draggedStageId = null;
            }
        },

        dropStage(event, stageId) {
            event.preventDefault();
            const dragStageId = event.dataTransfer.getData('text/stage-id');
            if (!dragStageId || parseInt(dragStageId) === stageId) return;

            const boardContainer = document.getElementById('kanban-board-columns');
            const draggedCol = document.getElementById('stage-' + dragStageId);
            const targetCol = document.getElementById('stage-' + stageId);

            if (boardContainer && draggedCol && targetCol) {
                const children = [...boardContainer.children];
                const dragIndex = children.indexOf(draggedCol);
                const targetIndex = children.indexOf(targetCol);

                if (dragIndex < targetIndex) {
                    boardContainer.insertBefore(draggedCol, targetCol.nextSibling);
                } else {
                    boardContainer.insertBefore(draggedCol, targetCol);
                }

                this.saveStageOrder();
            }
        },

        saveStageOrder() {
            const boardContainer = document.getElementById('kanban-board-columns');
            if (!boardContainer) return;

            const stagesOrder = [...boardContainer.children]
                .filter(col => col.id && col.id.startsWith('stage-') && !col.id.includes('deals'))
                .map((col, index) => {
                    const id = col.id.replace('stage-', '');
                    return {
                        id: parseInt(id),
                        order: index
                    };
                });

            fetch('{{ route("admin.pipelines.stages.reorder", $pipeline) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ stages: stagesOrder })
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    alert('Failed to save stage order');
                    window.location.reload();
                }
            })
            .catch(e => {
                console.error('Reorder failed:', e);
                window.location.reload();
            });
        },

        // Drag & Drop
        dragStart(event, dealId) {
            this.draggedDealId = dealId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dealId);
            event.target.style.opacity = '0.5';
        },
        dragEnd(event) {
            event.target.style.opacity = '1';
            this.draggedDealId = null;
        },
        dropDeal(event, stageId) {
            event.preventDefault();
            const dealId = event.dataTransfer.getData('text/plain');
            if (!dealId) return;

            const dealEl = document.getElementById('deal-' + dealId);
            if (!dealEl) return;

            const oldStageId = parseInt(dealEl.dataset.stageId);
            const dealValue = parseFloat(dealEl.dataset.value || 0);
            const oldStatus = dealEl.dataset.status;

            const targetListEl = document.getElementById('stage-deals-' + stageId);
            if (!targetListEl) return;

            // Determine insertion point based on cursor position relative to siblings
            const siblings = [...targetListEl.querySelectorAll('.deal-card:not(#deal-' + dealId + ')')];
            const nextSibling = siblings.find(sibling => {
                const rect = sibling.getBoundingClientRect();
                return event.clientY < rect.top + rect.height / 2;
            });

            if (nextSibling) {
                targetListEl.insertBefore(dealEl, nextSibling);
            } else {
                targetListEl.appendChild(dealEl);
            }

            // Get target stage name to compute status
            const stageEl = document.getElementById('stage-' + stageId);
            const newStageName = stageEl ? stageEl.dataset.stageName.toLowerCase() : '';
            let newStatus = 'open';
            if (newStageName === 'won') {
                newStatus = 'won';
            } else if (newStageName === 'lost') {
                newStatus = 'lost';
            }

            // Update DOM properties for the deal card
            dealEl.dataset.stageId = stageId;
            dealEl.dataset.status = newStatus;

            // Update status badge & indicator on the card
            const badgeEl = document.getElementById('deal-status-badge-' + dealId);
            if (badgeEl) {
                badgeEl.textContent = newStatus;
                badgeEl.className = 'text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-sm border ' +
                    (newStatus === 'won' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                    (newStatus === 'lost' ? 'bg-red-50 text-red-700 border-red-200' :
                    'bg-blue-50 text-blue-700 border-blue-200'));
            }
            const indicatorEl = document.getElementById('deal-status-indicator-' + dealId);
            if (indicatorEl) {
                indicatorEl.className = 'w-1.5 h-1.5 rounded-full shrink-0 ' +
                    (newStatus === 'won' ? 'bg-emerald-500' :
                    (newStatus === 'lost' ? 'bg-red-500' :
                    'bg-blue-500'));
            }

            // Update stage counts & values only if the stage actually changed
            if (oldStageId !== stageId) {
                // Update source stage count and value
                const sourceCountEl = document.getElementById('stage-count-' + oldStageId);
                if (sourceCountEl) {
                    const val = Math.max(0, parseInt(sourceCountEl.textContent) - 1);
                    sourceCountEl.textContent = val;
                }
                const sourceValEl = document.getElementById('stage-value-' + oldStageId);
                if (sourceValEl) {
                    const oldVal = parseFloat(sourceValEl.dataset.value || 0);
                    const newVal = Math.max(0, oldVal - dealValue);
                    sourceValEl.dataset.value = newVal;
                    sourceValEl.textContent = '₹' + newVal.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                }

                // Update destination stage count and value
                const destCountEl = document.getElementById('stage-count-' + stageId);
                if (destCountEl) {
                    const val = parseInt(destCountEl.textContent) + 1;
                    destCountEl.textContent = val;
                }
                const destValEl = document.getElementById('stage-value-' + stageId);
                if (destValEl) {
                    const oldVal = parseFloat(destValEl.dataset.value || 0);
                    const newVal = oldVal + dealValue;
                    destValEl.dataset.value = newVal;
                    destValEl.textContent = '₹' + newVal.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                }
            }

            // Update Pipeline Summary totals
            if (oldStatus !== newStatus) {
                if (oldStatus === 'won') {
                    const el = document.getElementById('summary-won-deals');
                    if (el) el.textContent = Math.max(0, parseInt(el.textContent) - 1);
                } else if (oldStatus === 'lost') {
                    const el = document.getElementById('summary-lost-deals');
                    if (el) el.textContent = Math.max(0, parseInt(el.textContent) - 1);
                }

                if (newStatus === 'won') {
                    const el = document.getElementById('summary-won-deals');
                    if (el) el.textContent = parseInt(el.textContent) + 1;
                } else if (newStatus === 'lost') {
                    const el = document.getElementById('summary-lost-deals');
                    if (el) el.textContent = parseInt(el.textContent) + 1;
                }
            }

            // Calculate the new index position of the card in the list of children
            const count = [...targetListEl.querySelectorAll('.deal-card')].indexOf(dealEl);

            // Send request to server
            fetch('{{ route("admin.pipelines.deals.move") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ deal_id: parseInt(dealId), stage_id: stageId, order: count })
            })
            .then(r => r.json())
            .then(d => {
                if (!d.success) {
                    alert(d.message || 'Move failed');
                    window.location.reload();
                } else {
                    if (this.showReports) {
                        this.loadAnalytics();
                    }
                }
            })
            .catch(e => {
                console.error('Move failed:', e);
                window.location.reload();
            });
        },

        // Activity Timeline & Drawer Dashboard
        openTimeline(dealId, title) {
            this.timelineDealId = dealId;
            this.timelineDealTitle = title;
            this.timelineActivities = [];
            this.dealTasksList = [];
            this.drawerTab = 'timeline';
            this.newComment = '';
            this.newTaskTitle = '';
            this.newTaskDueDate = '';
            this.showTimeline = true;

            // Load Activities
            fetch('{{ route("admin.pipelines.deals.activities", ":id") }}'.replace(':id', dealId), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json()).then(d => { if (d.success) this.timelineActivities = d.activities; });

            // Load Tasks
            fetch('{{ route("admin.pipelines.deals.tasks", ":id") }}'.replace(':id', dealId), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json()).then(d => { if (d.success) this.dealTasksList = d.tasks; });
        },

        submitComment() {
            if (!this.newComment.trim()) return;
            const dealId = this.timelineDealId;
            fetch('{{ route("admin.pipelines.deals.comments.store", ":id") }}'.replace(':id', dealId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ comment: this.newComment })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.timelineActivities.unshift(d.activity);
                    this.newComment = '';
                } else {
                    alert(d.message || 'Error posting comment');
                }
            })
            .catch(e => { console.error(e); alert('Network error'); });
        },

        submitTask() {
            if (!this.newTaskTitle.trim()) return;
            const dealId = this.timelineDealId;
            fetch('{{ route("admin.pipelines.deals.tasks.store", ":id") }}'.replace(':id', dealId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ title: this.newTaskTitle, due_date: this.newTaskDueDate })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.dealTasksList.unshift(d.task);
                    this.newTaskTitle = '';
                    this.newTaskDueDate = '';
                } else {
                    alert(d.message || 'Error adding task');
                }
            })
            .catch(e => { console.error(e); alert('Network error'); });
        },

        toggleTask(task) {
            fetch('{{ route("admin.pipelines.deals.tasks.toggle", ":id") }}'.replace(':id', task.id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    task.is_completed = d.is_completed;
                } else {
                    alert('Error toggling task');
                }
            })
            .catch(e => { console.error(e); alert('Network error'); });
        },

        deleteTask(taskId) {
            if (!confirm('Delete this task?')) return;
            fetch('{{ route("admin.pipelines.deals.tasks.destroy", ":id") }}'.replace(':id', taskId), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    this.dealTasksList = this.dealTasksList.filter(t => t.id !== taskId);
                } else {
                    alert('Error deleting task');
                }
            })
            .catch(e => { console.error(e); alert('Network error'); });
        },

        // Analytics
        loadAnalytics() {
            fetch('{{ route("admin.pipelines.analytics", $pipeline) }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json()).then(d => { if (d.success) this.analyticsData = d.data; });
        }
    };
}
</script>
@endpush

@push('head')
<style>
    .deal-card {
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .deal-card[draggable="true"]:active {
        border-color: var(--color-brand);
    }
    [id^="stage-"] {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    [id^="stage-"].drag-over {
        background: color-mix(in srgb, var(--color-brand) 5%, white);
        border-color: var(--color-brand);
    }
</style>
@endpush
