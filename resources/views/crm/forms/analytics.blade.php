@extends('layouts.app')
@section('title', 'Form Analytics')
@section('heading', 'Form Analytics: ' . $signupForm->name)

@section('header-actions')
    <a href="{{ route('admin.signup-forms.index') }}"
        class="px-5 py-3 flex items-center rounded-sm bg-gray-150 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-[10px] font-black uppercase tracking-widest transition-all focus:outline-none focus:ring-0 cursor-pointer">
        <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Forms
    </a>
@endsection

@section('content')
<div class="space-y-6 animate-slide-up">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        <!-- Total Views -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Total Views</span>
                <span class="text-3xl font-black text-surface-900">{{ number_format($totalViews) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </div>
        </div>

        <!-- Unique Views -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Unique Visitors</span>
                <span class="text-3xl font-black text-indigo-600">{{ number_format($uniqueViews) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>

        <!-- Completed Submissions -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Submissions</span>
                <span class="text-3xl font-black text-emerald-600">{{ number_format($totalSubmissions) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Conversion Rate</span>
                <span class="text-3xl font-black text-brand">{{ $conversionRate }}%</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>

        <!-- Abandoned -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Abandoned / Dropoffs</span>
                <span class="text-3xl font-black text-amber-500">{{ number_format($abandonedSubmissions) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950 flex items-center justify-center text-amber-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Growth/Submissions timeline chart -->
        <div class="glass-card p-6 lg:col-span-2">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Traffic & Conversions (Last 30 Days)</h3>
            <div id="timeline-chart" class="w-full h-80"></div>
        </div>

        <!-- Form Details / Configuration -->
        <div class="glass-card p-6">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Form Configuration</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2.5 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-surface-500 font-semibold">Associated List</span>
                    <a href="{{ route('admin.email-lists.show', $emailList) }}" class="text-xs font-bold text-brand hover:underline">{{ $emailList->name }}</a>
                </div>
                <div class="flex justify-between items-center py-2.5 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-surface-500 font-semibold">Opt-In Type</span>
                    <span class="badge {{ $signupForm->double_opt_in ? 'badge-amber' : 'badge-emerald' }}">
                        {{ $signupForm->double_opt_in ? 'Double Opt-In' : 'Single Opt-In' }}
                    </span>
                </div>
                <div class="flex justify-between items-center py-2.5 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-surface-500 font-semibold">Form Type</span>
                    <span class="badge {{ !empty($signupForm->steps) ? 'badge-brand' : 'badge-neutral' }}">
                        {{ !empty($signupForm->steps) ? 'Multi-Step Wizard' : 'Single Page Form' }}
                    </span>
                </div>
                @if(!empty($signupForm->steps))
                    <div class="flex justify-between items-center py-2.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-surface-500 font-semibold">Total Steps</span>
                        <span class="text-xs font-bold text-surface-900">{{ count($signupForm->steps) }} Steps</span>
                    </div>
                @endif
                <div class="py-2.5 border-b border-gray-100 dark:border-gray-800">
                    <span class="block text-xs text-surface-500 font-semibold mb-1.5">Auto-Assign Tags</span>
                    <div class="flex flex-wrap gap-1">
                        @forelse($signupForm->tags ?? [] as $tag)
                            <span class="badge badge-indigo text-[10px]">{{ $tag }}</span>
                        @empty
                            <span class="text-xs text-surface-400 italic">None configured</span>
                        @endforelse
                    </div>
                </div>
                <div class="py-2.5">
                    <span class="block text-xs text-surface-500 font-semibold mb-1">Success Message</span>
                    <p class="text-xs text-surface-700 bg-gray-50 dark:bg-gray-900 p-2.5 rounded-sm border border-gray-100 dark:border-gray-800 italic">
                        "{{ $signupForm->success_message }}"
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Funnel / Steps breakdown -->
    @if(!empty($signupForm->steps))
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Graphical Step Funnel -->
            <div class="glass-card p-6 lg:col-span-1 flex flex-col justify-between">
                <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Step Drop-Off Funnel</h3>
                <div class="space-y-4 py-2 relative flex-1 flex flex-col justify-center">
                    @foreach($stepsStats as $stat)
                        <div class="relative flex items-center gap-4">
                            <!-- Visual box representing volume -->
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold text-surface-800">Step {{ $stat['step_number'] }}: {{ Str::limit($stat['title'], 25) }}</span>
                                    <span class="text-xs font-black text-surface-900">{{ number_format($stat['reached']) }} <span class="text-[10px] text-surface-400 font-medium">({{ $stat['reached_pct'] }}%)</span></span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-6 rounded overflow-hidden relative flex items-center px-2">
                                    <div class="absolute left-0 top-0 h-full transition-all duration-500" 
                                        style="width: {{ $stat['reached_pct'] }}%; background-color: {{ $signupForm->theme_color }}33; border-right: 2px solid {{ $signupForm->theme_color }}"></div>
                                    <span class="text-[10px] font-bold z-10 text-surface-600 dark:text-surface-300">
                                        {{ $stat['reached_pct'] }}% of visitors
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="flex justify-center my-1 text-amber-500">
                                <div class="flex items-center gap-1.5 py-1 px-2.5 bg-amber-50 dark:bg-amber-950/40 rounded-full border border-amber-100/60 dark:border-amber-900/30 text-[10px] font-bold">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/></svg>
                                    -{{ number_format($stat['dropoff']) }} dropoff ({{ $stat['dropoff_pct'] }}%)
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Funnel Details Table -->
            <div class="glass-card p-6 lg:col-span-2">
                <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Detailed Step Metrics</h3>
                <div class="overflow-x-auto">
                    <table class="data-table w-full text-xs">
                        <thead>
                            <tr>
                                <th class="w-16">Step</th>
                                <th>Title / Description</th>
                                <th class="text-center">Fields Grouped</th>
                                <th class="text-center">Reached (Visitors)</th>
                                <th class="text-center">Conversion (of step)</th>
                                <th class="text-center">Dropoff (Abandoned)</th>
                                <th class="text-right">Dropoff %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stepsStats as $index => $stat)
                                @php
                                    $stepConfig = $signupForm->steps[$index] ?? [];
                                    $fieldNames = collect($stepConfig['fields'] ?? [])->map(function($f) {
                                        return ucwords(str_replace(['custom_', '_'], ['', ' '], $f));
                                    })->join(', ');
                                @endphp
                                <tr>
                                    <td class="font-bold text-center text-surface-800">#{{ $stat['step_number'] }}</td>
                                    <td>
                                        <div class="font-bold text-surface-900">{{ $stat['title'] }}</div>
                                        @if(!empty($stepConfig['description']))
                                            <div class="text-[10px] text-surface-400 font-medium mt-0.5">{{ $stepConfig['description'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center text-surface-600 font-medium">{{ $fieldNames ?: 'None' }}</td>
                                    <td class="text-center font-bold text-surface-950">{{ number_format($stat['reached']) }}</td>
                                    <td class="text-center font-medium">
                                        <span class="text-emerald-600 font-bold">{{ 100 - $stat['dropoff_pct'] }}%</span>
                                    </td>
                                    <td class="text-center font-bold text-amber-600">{{ number_format($stat['dropoff']) }}</td>
                                    <td class="text-right font-black text-amber-600">{{ $stat['dropoff_pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Referrer & Submissions log row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Referrer Sources -->
        <div class="glass-card p-6 lg:col-span-1">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Traffic Acquisition Sources</h3>
            <div class="overflow-x-auto">
                <table class="data-table w-full text-xs">
                    <thead>
                        <tr>
                            <th>Source / Referrer</th>
                            <th class="text-right">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrers as $referrer)
                            <tr>
                                <td class="font-bold text-surface-700">
                                    <span class="block truncate max-w-[200px]" title="{{ $referrer['url'] }}">
                                        {{ $referrer['host'] }}
                                    </span>
                                </td>
                                <td class="text-right font-bold text-brand">{{ number_format($referrer['count']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center py-10 text-surface-500">No referrer traffic data logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Submissions List -->
        <div class="glass-card p-6 lg:col-span-2">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Recent Submissions (Last 10)</h3>
            <div class="overflow-x-auto">
                <table class="data-table w-full text-xs">
                    <thead>
                        <tr>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th class="text-center">Date Subscribed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $recentSubmissions = \App\Models\FormSubmission::where('signup_form_id', $signupForm->id)
                                ->orderByDesc('updated_at')
                                ->limit(10)
                                ->get();
                        @endphp
                        @forelse($recentSubmissions as $sub)
                            <tr>
                                <td class="font-bold text-surface-900">
                                    @if($sub->email)
                                        {{ $sub->email }}
                                    @else
                                        <span class="text-surface-400 italic">Anonymous / Not Entered</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sub->is_completed)
                                        <span class="badge badge-emerald">Completed</span>
                                    @else
                                        <span class="badge badge-amber">Abandoned at Step {{ $sub->abandoned_step }}</span>
                                    @endif
                                </td>
                                <td class="text-center text-surface-500 font-medium">
                                    {{ $sub->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-10 text-surface-500">No submissions recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var timelineOptions = {
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3, colors: ['#5850ec', '#10b981'] },
            series: [
                {
                    name: 'Page Views',
                    data: @json($chartViews)
                },
                {
                    name: 'Conversions',
                    data: @json($chartSubmissions)
                }
            ],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                    colorStops: [
                        [
                            { offset: 0, color: '#5850ec', opacity: 0.3 },
                            { offset: 100, color: '#5850ec', opacity: 0 }
                        ],
                        [
                            { offset: 0, color: '#10b981', opacity: 0.3 },
                            { offset: 100, color: '#10b981', opacity: 0 }
                        ]
                    ]
                }
            },
            xaxis: {
                categories: @json($chartDates),
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            colors: ['#5850ec', '#10b981'],
            grid: { borderColor: '#f3f4f6' },
            legend: {
                position: 'top',
                fontSize: '11px',
                fontWeight: 600,
                labels: { colors: '#4b5563' }
            }
        };
        new ApexCharts(document.querySelector("#timeline-chart"), timelineOptions).render();
    });
</script>
@endsection
