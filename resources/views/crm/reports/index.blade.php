@extends('layouts.app')
@section('title', 'CRM Reports & Forecasting')
@section('heading', 'CRM Reports & Forecasting')

@section('header-actions')
    @if($pipeline)
        <form action="{{ route('admin.crm-reports.index') }}" method="GET" id="pipeline-select-form" class="flex items-center gap-2">
            <label class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Active Pipeline:</label>
            <select name="pipeline_id" onchange="document.getElementById('pipeline-select-form').submit()" 
                class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-sm text-xs font-bold text-surface-800 outline-none focus:border-brand cursor-pointer">
                @foreach($pipelines as $p)
                    <option value="{{ $p->id }}" {{ $p->id == $pipeline->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </form>
    @endif
@endsection

@section('content')
<div class="space-y-6 animate-slide-up">

    @if(!$pipeline)
        <div class="glass-card p-16 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">No Pipelines Found</h3>
            <p class="text-surface-500 text-sm mb-6">Create a deal pipeline first to enable forecasting and performance reports.</p>
            <a href="{{ route('admin.pipelines.create') }}" class="btn btn-primary">Create Pipeline</a>
        </div>
    @else
        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Total Active Pipeline -->
            <div class="glass-card p-6 flex items-center justify-between">
                <div>
                    <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Total Pipeline</span>
                    <span class="text-2xl font-black text-surface-900">₹{{ number_format($totalValue) }}</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16v1m-4-8h4m-4 4h4m-4 4h4m6 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- Won Deals Value -->
            <div class="glass-card p-6 flex items-center justify-between">
                <div>
                    <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Won Revenue</span>
                    <span class="text-2xl font-black text-emerald-600">₹{{ number_format($wonValue) }}</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-950 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <!-- Win Rate -->
            <div class="glass-card p-6 flex items-center justify-between">
                <div>
                    <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Win Rate</span>
                    <span class="text-2xl font-black text-brand">{{ $winRate }}%</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </div>
            </div>

            <!-- Avg Deal Value -->
            <div class="glass-card p-6 flex items-center justify-between">
                <div>
                    <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Avg Deal Size</span>
                    <span class="text-2xl font-black text-indigo-600">₹{{ number_format($avgDealValue) }}</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>

            <!-- Sales Velocity -->
            <div class="glass-card p-6 flex items-center justify-between">
                <div>
                    <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Avg Days to Close</span>
                    <span class="text-2xl font-black text-amber-500">{{ $avgTimeToClose }} Days</span>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-950 flex items-center justify-center text-amber-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <!-- Charts Section 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Expected Revenue Forecast Area Chart -->
            <div class="glass-card p-6 lg:col-span-2">
                <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Revenue Forecast Timeline (Weighted Open Deals)</h3>
                <div id="forecast-chart" class="w-full h-80"></div>
            </div>

            <!-- Stage Distribution & Funnel -->
            <div class="glass-card p-6 flex flex-col justify-between">
                <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Pipeline Value by Stage</h3>
                <div class="space-y-4 py-2 flex-1 flex flex-col justify-center">
                    @foreach($stageDistribution as $stage)
                        @php
                            $percentage = $totalValue > 0 ? round(($stage['value'] / $totalValue) * 100, 1) : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-bold text-surface-850 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: {{ $stage['color'] }}"></span>
                                    {{ $stage['name'] }}
                                </span>
                                <span class="text-xs font-black text-surface-900">₹{{ number_format($stage['value']) }} <span class="text-[10px] text-surface-400 font-medium">({{ $stage['count'] }} deals)</span></span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-4 rounded-full overflow-hidden relative">
                                <div class="h-full transition-all duration-500 rounded-full" 
                                    style="width: {{ $percentage }}%; background-color: {{ $stage['color'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Charts Section 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Historical Won vs Lost Trend Bar Chart -->
            <div class="glass-card p-6 lg:col-span-2">
                <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Historical Sales Trend (Last 6 Months)</h3>
                <div id="historical-trend-chart" class="w-full h-80"></div>
            </div>

            <!-- Win/Loss Ratio KPI circle -->
            <div class="glass-card p-6 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Win/Loss Ratio</h3>
                    <div class="flex justify-center items-center py-6">
                        <div id="ratio-chart" class="w-full h-56 flex items-center justify-center"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 border-t border-gray-100 dark:border-gray-800 pt-4 text-center">
                    <div>
                        <span class="block text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-0.5">Won ({{ $wonCount }})</span>
                        <span class="text-sm font-black text-surface-900">₹{{ number_format($wonValue) }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-red-500 uppercase tracking-widest mb-0.5">Lost ({{ $lostCount }})</span>
                        <span class="text-sm font-black text-surface-900">₹{{ number_format($lostValue) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Team Performance Leaderboard -->
        <div class="glass-card p-6">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Sales Representative Leaderboard</h3>
            <div class="overflow-x-auto">
                <table class="data-table w-full text-xs">
                    <thead>
                        <tr>
                            <th>Sales Rep / Assignee</th>
                            <th class="text-center">Won Deals</th>
                            <th class="text-right">Won Value</th>
                            <th class="text-center">Lost Deals</th>
                            <th class="text-right">Lost Value</th>
                            <th class="text-center">Open Deals</th>
                            <th class="text-right">Open Value</th>
                            <th class="text-right">Win Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamPerformance as $perf)
                            <tr>
                                <td class="font-bold text-surface-850">{{ $perf['name'] }}</td>
                                <td class="text-center font-bold text-emerald-600">{{ number_format($perf['won_count']) }}</td>
                                <td class="text-right font-black text-emerald-600">₹{{ number_format($perf['won_value']) }}</td>
                                <td class="text-center font-bold text-red-500">{{ number_format($perf['lost_count']) }}</td>
                                <td class="text-right font-black text-red-500">₹{{ number_format($perf['lost_value']) }}</td>
                                <td class="text-center font-bold text-indigo-600">{{ number_format($perf['open_count']) }}</td>
                                <td class="text-right font-black text-indigo-600">₹{{ number_format($perf['open_value']) }}</td>
                                <td class="text-right font-black">
                                    <span class="inline-block py-0.5 px-2 rounded text-[10px] font-black {{ $perf['win_rate'] >= 60 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : ($perf['win_rate'] >= 40 ? 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400') }}">
                                        {{ $perf['win_rate'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-surface-500">No deal assignments found. Allocate deals to team members to track performance.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if($pipeline)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Forecast Chart (Area chart of next 6 months expected close values)
        var forecastOptions = {
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            stroke: { curve: 'smooth', width: 3, colors: ['#5850ec'] },
            dataLabels: { enabled: false },
            series: [{
                name: 'Expected Closed Value',
                data: [
                    @foreach($monthlyForecast as $f)
                        {{ $f['value'] }},
                    @endforeach
                ]
            }],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                    colorStops: [
                        { offset: 0, color: '#5850ec', opacity: 0.3 },
                        { offset: 100, color: '#5850ec', opacity: 0 }
                    ]
                }
            },
            xaxis: {
                categories: [
                    @foreach($monthlyForecast as $f)
                        '{{ $f['label'] }}',
                    @endforeach
                ],
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString();
                    },
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            colors: ['#5850ec'],
            grid: { borderColor: '#f3f4f6' }
        };
        new ApexCharts(document.querySelector("#forecast-chart"), forecastOptions).render();

        // 2. Historical Win/Loss Trend Chart (Stacked Column Chart of last 6 months)
        var historicalOptions = {
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,
                toolbar: { show: false },
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 4,
                    columnWidth: '45%'
                },
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            series: [
                {
                    name: 'Won Revenue',
                    data: [
                        @foreach($monthlyTrend as $t)
                            {{ $t['won_value'] }},
                        @endforeach
                    ]
                },
                {
                    name: 'Lost Revenue',
                    data: [
                        @foreach($monthlyTrend as $t)
                            {{ $t['lost_value'] }},
                        @endforeach
                    ]
                }
            ],
            xaxis: {
                categories: [
                    @foreach($monthlyTrend as $t)
                        '{{ $t['label'] }}',
                    @endforeach
                ],
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString();
                    },
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            colors: ['#10b981', '#ef4444'],
            fill: { opacity: 1 },
            grid: { borderColor: '#f3f4f6' },
            legend: {
                position: 'top',
                fontSize: '11px',
                fontWeight: 600,
                labels: { colors: '#4b5563' }
            }
        };
        new ApexCharts(document.querySelector("#historical-trend-chart"), historicalOptions).render();

        // 3. Win/Loss Ratio Radial Bar Chart
        var ratioOptions = {
            chart: {
                type: 'radialBar',
                height: 250,
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    dataLabels: {
                        name: {
                            fontSize: '11px',
                            color: '#6b7280',
                            offsetY: 80,
                            fontWeight: 700,
                            textAnchor: 'middle'
                        },
                        value: {
                            offsetY: 35,
                            fontSize: '22px',
                            color: '#111827',
                            fontWeight: 800,
                            formatter: function (val) {
                                return val + "%";
                            }
                        }
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    shadeIntensity: 0.15,
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 50, 65, 91]
                },
            },
            stroke: { dashArray: 4 },
            series: [{{ $winRate }}],
            labels: ['Win Ratio'],
            colors: ['#10b981']
        };
        new ApexCharts(document.querySelector("#ratio-chart"), ratioOptions).render();
    });
</script>
@endif
@endsection
