@extends('layouts.app')
@section('title', 'Audience Insights')
@section('heading', 'Audience Insights: ' . $emailList->name)

@section('content')
<div class="space-y-6 animate-slide-up">
    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Total Contacts -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Total Contacts</span>
                <span class="text-3xl font-black text-surface-900">{{ number_format($emailList->emails()->where('is_archived', false)->count()) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-brand/10 flex items-center justify-center text-brand">
                <i class="fa-solid fa-users text-lg"></i>
            </div>
        </div>

        <!-- Valid Contacts -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Valid (Clean)</span>
                <span class="text-3xl font-black text-emerald-600">{{ number_format($emailList->emails()->where('is_archived', false)->where('status', 'valid')->count()) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                <i class="fa-solid fa-circle-check text-lg"></i>
            </div>
        </div>

        <!-- Subscribed -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Subscribed</span>
                <span class="text-3xl font-black text-indigo-600">{{ number_format($emailList->emails()->where('is_archived', false)->where('subscription_status', 'subscribed')->count()) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                <i class="fa-solid fa-envelope-open text-lg"></i>
            </div>
        </div>

        <!-- Lead Score (Average) -->
        <div class="glass-card p-6 flex items-center justify-between">
            <div>
                <span class="block text-[10px] font-black text-surface-500 uppercase tracking-widest mb-1">Avg Lead Score</span>
                <span class="text-3xl font-black text-amber-500">{{ round($emailList->emails()->where('is_archived', false)->avg('email_lead_score') ?? 0, 1) }}</span>
            </div>
            <div class="w-12 h-12 rounded-full bg-amber-50 flex items-center justify-center text-amber-500">
                <i class="fa-solid fa-star text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Growth Trend Line Chart -->
        <div class="glass-card p-6 lg:col-span-2">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Subscriber Growth Trend (Last 30 Days)</h3>
            <div id="growth-chart" class="w-full h-80"></div>
        </div>

        <!-- Verification Status Donut Chart -->
        <div class="glass-card p-6">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Verification Health</h3>
            <div id="health-chart" class="w-full h-80 flex items-center justify-center"></div>
        </div>
    </div>

    <!-- Second Row of Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Opt-in Status Breakdown -->
        <div class="glass-card p-6">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Subscription Status Distribution</h3>
            <div id="subscription-chart" class="w-full h-80"></div>
        </div>

        <!-- Geographic Activity (IP address log) -->
        <div class="glass-card p-6">
            <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Top Active IP Locations</h3>
            <div class="overflow-x-auto mt-4">
                <table class="data-table w-full">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th class="text-center">Recorded Events</th>
                            <th class="text-right">Estimated Region</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($geoData as $geo)
                            <tr>
                                <td class="font-bold text-surface-700">{{ $geo->ip_address }}</td>
                                <td class="text-center font-bold text-brand">{{ number_format($geo->event_count) }}</td>
                                <td class="text-right font-medium text-surface-500">
                                    @if($geo->ip_address === '127.0.0.1' || str_starts_with($geo->ip_address, '192.168.') || str_starts_with($geo->ip_address, '10.'))
                                        <span class="badge badge-gray">Local / Sandbox Dev</span>
                                    @else
                                        <span class="badge badge-brand">External API User</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-10 text-surface-500">No geographic tracking activity logged yet. Launch campaign broadcasts to see geographical click analytics.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Leaderboard: Top Engaged Leads -->
    <div class="glass-card p-6">
        <h3 class="text-xs font-black text-surface-900 tracking-tight uppercase mb-4">Top Engaged Leads Leaderboard</h3>
        <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th>Lead Name</th>
                        <th>Email Contact</th>
                        <th class="text-center">Lead Score</th>
                        <th>Opt-in Status</th>
                        <th>Added Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topLeads as $lead)
                        <tr class="hover:bg-surface-50/50 transition-colors">
                            <td class="font-bold text-surface-900">{{ $lead->name ?: 'Unnamed Contact' }}</td>
                            <td class="font-medium text-surface-600">{{ $lead->email ?: ($lead->whatsapp_number ?: 'No address') }}</td>
                            <td class="text-center">
                                <span class="badge {{ $lead->email_lead_score >= 80 ? 'badge-brand bg-amber-500/10 text-amber-600' : ($lead->email_lead_score >= 50 ? 'bg-indigo-50 text-indigo-600' : 'badge-gray') }}">
                                    {{ $lead->email_lead_score }} Points
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $lead->subscription_status === 'subscribed' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($lead->subscription_status) }}
                                </span>
                            </td>
                            <td class="text-surface-500 text-xs font-semibold">{{ $lead->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-surface-500">No leads available on this workspace list.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 1. Growth Trend Chart
        var growthOptions = {
            chart: {
                type: 'area',
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3, colors: ['#5850ec'] },
            series: [{
                name: 'New Subscribers',
                data: [
                    @foreach($trend as $t)
                        {{ $t['count'] }},
                    @endforeach
                ]
            }],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                    colorStops: [
                        { offset: 0, color: '#5850ec', opacity: 0.4 },
                        { offset: 100, color: '#5850ec', opacity: 0 }
                    ]
                }
            },
            xaxis: {
                categories: [
                    @foreach($trend as $t)
                        '{{ \Carbon\Carbon::parse($t['date'])->format('M d') }}',
                    @endforeach
                ],
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            colors: ['#5850ec'],
            grid: { borderColor: '#f3f4f6' }
        };
        new ApexCharts(document.querySelector("#growth-chart"), growthOptions).render();

        // 2. Health / Verification Status Donut Chart
        var healthOptions = {
            chart: {
                type: 'donut',
                height: 320,
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            series: [
                {{ $verificationStats['valid'] ?? 0 }},
                {{ $verificationStats['invalid'] ?? 0 }},
                {{ $verificationStats['duplicate'] ?? 0 }},
                {{ $verificationStats['cross_duplicate'] ?? 0 }}
            ],
            labels: ['Valid', 'Invalid', 'List Duplicate', 'Cross-List Duplicate'],
            colors: ['#10b981', '#ef4444', '#f59e0b', '#6b7280'],
            legend: {
                position: 'bottom',
                fontSize: '11px',
                fontWeight: 600,
                labels: { colors: '#4b5563' }
            },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '75%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Health',
                                formatter: function (w) {
                                    var total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    if (total === 0) return '0%';
                                    var valid = w.globals.seriesTotals[0];
                                    return Math.round((valid / total) * 100) + '% Clean';
                                }
                            }
                        }
                    }
                }
            }
        };
        new ApexCharts(document.querySelector("#health-chart"), healthOptions).render();

        // 3. Subscription Status Distribution Bar Chart
        var subscriptionOptions = {
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                fontFamily: 'Outfit, Inter, sans-serif'
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '40%',
                    distributed: true
                }
            },
            dataLabels: { enabled: false },
            legend: { show: false },
            series: [{
                name: 'ContactsCount',
                data: [
                    {{ $subscriptionStats['subscribed'] ?? 0 }},
                    {{ $subscriptionStats['unsubscribed'] ?? 0 }},
                    {{ $subscriptionStats['bounced'] ?? 0 }}
                ]
            }],
            xaxis: {
                categories: ['Subscribed', 'Unsubscribed', 'Bounced'],
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: '#6b7280', fontWeight: 600, fontSize: '10px' }
                }
            },
            colors: ['#6366f1', '#f43f5e', '#f59e0b'],
            grid: { borderColor: '#f3f4f6' }
        };
        new ApexCharts(document.querySelector("#subscription-chart"), subscriptionOptions).render();
    });
</script>
@endsection
