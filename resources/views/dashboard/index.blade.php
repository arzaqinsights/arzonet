@extends('layouts.app')
@section('title', 'Intelligent Dashboard Overview')

@section('content')
<div class="space-y-6 animate-slide-up pb-8">

    {{-- Top Action Bar --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-900 p-6 rounded-sm shadow-xl border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-brand/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
        <div class="relative z-10">
            <h1 class="text-2xl font-black text-white" style="font-family:'Outfit',sans-serif;">Arzonet Intelligence <span class="text-brand">v2.1</span></h1>
            <p class="text-xs text-white/50 font-bold uppercase tracking-widest mt-1">Real-time Performance & Audience Analytics</p>
        </div>
        <div class="flex items-center gap-2 relative z-10">
            <a href="{{ route('admin.campaigns.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-brand hover:bg-brand-dark text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all shadow-lg hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                New Campaign
            </a>
            <a href="{{ route('admin.email-lists.create') }}" class="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all border border-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Import Contacts
            </a>
            <button onclick="location.reload()" class="p-2.5 bg-white/5 hover:bg-white/10 text-white/70 rounded-sm border border-white/10 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
        </div>
    </div>

    {{-- Key Performance Indicators --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        {{-- Total Sent --}}
        <div class="bg-white border rounded-sm p-4 relative overflow-hidden group hover:shadow-md transition-shadow">
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Global Sent</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($totalSent) }}</h3>
                <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1 py-0.5 rounded-sm">{{ number_format($totalDelivered) }} Delivered</span>
            </div>
            <div class="mt-4 h-8 w-full flex items-end gap-0.5">
                @php $sparklineData = array_slice($chartData, -15); @endphp
                @foreach($sparklineData as $day)
                    @php 
                        $maxSpark = max(array_column($sparklineData, 'sent')) ?: 1;
                        $h = ($day['sent'] / $maxSpark * 100); 
                    @endphp
                    <div class="flex-1 bg-surface-100 group-hover:bg-brand transition-colors" style="height: {{ max(10, $h) }}%" title="{{ $day['date'] }}: {{ $day['sent'] }}"></div>
                @endforeach
            </div>
        </div>

        {{-- Open Rate --}}
        <div class="bg-white border rounded-sm p-4 relative overflow-hidden group hover:shadow-md transition-shadow">
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Avg Open Rate</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ $globalOpenRate }}%</h3>
                <span class="text-[9px] font-bold {{ $globalOpenRate > 20 ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50' }} px-1 py-0.5 rounded-sm">
                    {{ $globalOpenRate > 20 ? 'Healthy' : 'Steady' }}
                </span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-50 rounded-full h-1 overflow-hidden">
                    <div class="bg-emerald-500 h-full transition-all duration-1000" style="width: {{ $globalOpenRate }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-[9px] font-black text-surface-400 uppercase tracking-tighter">
                    <span>Unique Opens</span>
                    <span>{{ number_format($totalOpens) }}</span>
                </div>
            </div>
        </div>

        {{-- Click Rate --}}
        <div class="bg-white border rounded-sm p-4 relative overflow-hidden group hover:shadow-md transition-shadow">
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Avg Click Rate</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ $globalClickRate }}%</h3>
                <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1 py-0.5 rounded-sm">Conversion</span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-50 rounded-full h-1 overflow-hidden">
                    <div class="bg-brand h-full transition-all duration-1000" style="width: {{ $globalClickRate }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-[9px] font-black text-surface-400 uppercase tracking-tighter">
                    <span>Unique Clicks</span>
                    <span>{{ number_format($totalClicks) }}</span>
                </div>
            </div>
        </div>

        {{-- Bounce Rate --}}
        <div class="bg-white border rounded-sm p-4 relative overflow-hidden group hover:shadow-md transition-shadow">
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Bounce Rate</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ $bounceRate }}%</h3>
                <span class="text-[9px] font-bold {{ $bounceRate < 5 ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50' }} px-1 py-0.5 rounded-sm">
                    {{ $bounceRate < 5 ? 'Low Risk' : 'High Risk' }}
                </span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-50 rounded-full h-1 overflow-hidden">
                    <div class="bg-red-500 h-full transition-all duration-1000" style="width: {{ $bounceRate }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-[9px] font-black text-surface-400 uppercase tracking-tighter">
                    <span>Total Bounces</span>
                    <span>{{ number_format($totalBounced) }}</span>
                </div>
            </div>
        </div>

        {{-- Audience Health --}}
        <div class="bg-white border rounded-sm p-4 relative overflow-hidden group hover:shadow-md transition-shadow">
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Audience Health</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($totalContacts) }}</h3>
                <span class="text-[9px] font-bold text-surface-600 bg-surface-50 px-1 py-0.5 rounded-sm">Verified</span>
            </div>
            <div class="mt-4">
                <div class="flex items-center gap-1">
                    <div class="h-1.5 bg-emerald-500 rounded-full transition-all" style="width: {{ $validPercent }}%"></div>
                    <div class="h-1.5 bg-red-400 rounded-full transition-all" style="width: {{ $invalidPercent }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-[9px] font-black text-surface-400 uppercase tracking-tighter">
                    <span>Unsubs</span>
                    <span>{{ number_format($totalUnsubscribed) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Performance Chart --}}
        <div class="lg:col-span-2 bg-white border rounded-sm flex flex-col shadow-sm">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Performance Intelligence</h3>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">30-Day Activity Heatmap (Hybrid Source)</p>
                </div>
                <div class="flex items-center gap-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 bg-brand rounded-sm"></div> Sent</div>
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 bg-red-400 rounded-sm"></div> Failed</div>
                </div>
            </div>
            <div class="p-8 flex-1 flex items-end gap-1.5 h-80 relative bg-gray-50/10">
                @php $maxSentGlobal = max(array_column($chartData, 'sent')) ?: 1; @endphp
                @foreach($chartData as $day)
                    @php $sentH = ($day['sent'] / $maxSentGlobal) * 100; @endphp
                    <div class="flex-1 flex flex-col justify-end group relative h-full">
                        <div class="w-full bg-surface-100 relative rounded-t-sm transition-all duration-300 group-hover:bg-brand/20" style="height: {{ max(2, $sentH) }}%">
                            <div class="absolute bottom-0 left-0 w-full bg-brand rounded-t-sm opacity-80" style="height: 100%"></div>
                            <div class="absolute bottom-0 left-0 w-full bg-red-400 rounded-t-sm z-10" style="height: {{ $day['sent'] > 0 ? ($day['failed'] / $day['sent']) * 100 : 0 }}%"></div>
                        </div>
                        <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-surface-900 text-white text-[10px] py-2 px-3 rounded-sm opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 shadow-2xl transition-all border border-white/10">
                            <p class="font-black border-b border-white/10 pb-1 mb-1">{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</p>
                            <div class="flex justify-between gap-4"><span>Sent:</span> <span class="font-bold">{{ number_format($day['sent']) }}</span></div>
                            <div class="flex justify-between gap-4"><span>Failed:</span> <span class="font-bold text-red-400">{{ number_format($day['failed']) }}</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t border-gray-50 flex justify-between items-center text-[10px] font-black text-surface-400 uppercase tracking-widest">
                <span>{{ \Carbon\Carbon::parse($chartData[0]['date'])->format('M d') }}</span>
                <span class="text-surface-300">Live Trend Synchronization</span>
                <span>Today</span>
            </div>
        </div>

        {{-- Engagement Distribution & ISP Health --}}
        <div class="flex flex-col gap-6">
            {{-- ISP Health Tracking --}}
            <div class="bg-white border rounded-sm p-5 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight mb-4" style="font-family:'Outfit',sans-serif;">ISP Reputation Index</h3>
                <div class="space-y-4">
                    @forelse($ispPerformance as $isp)
                        <div class="group">
                            <div class="flex justify-between items-end mb-1.5">
                                <span class="text-[10px] font-black text-surface-800 uppercase tracking-widest">{{ $isp->domain }}</span>
                                <span class="text-[10px] font-black {{ $isp->delivery_rate > 95 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $isp->delivery_rate }}%</span>
                            </div>
                            <div class="w-full bg-surface-50 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-surface-800 h-full group-hover:bg-brand transition-all" style="width: {{ $isp->delivery_rate }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center py-6">Calculating reputation...</p>
                    @endforelse
                </div>
            </div>

            {{-- Hourly Heatmap --}}
            <div class="bg-white border rounded-sm p-5 shadow-sm flex-1">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight mb-4" style="font-family:'Outfit',sans-serif;">Peak Engagement Time</h3>
                <div class="grid grid-cols-6 gap-1 h-32 items-end">
                    @for($h = 0; $h < 24; $h += 4)
                        @php 
                            $count = $hourlyStats[$h] ?? 0;
                            $maxHourly = $hourlyStats->max() ?: 1;
                            $h_percent = ($count / $maxHourly) * 100;
                        @endphp
                        <div class="flex-1 bg-surface-50 rounded-sm relative group" style="height: {{ max(10, $h_percent) }}%">
                            <div class="absolute bottom-0 left-0 w-full bg-brand rounded-sm opacity-50 h-full group-hover:opacity-100 transition-opacity"></div>
                            <span class="absolute -bottom-5 left-1/2 -translate-x-1/2 text-[8px] font-black text-surface-400">{{ $h }}h</span>
                        </div>
                    @endfor
                </div>
                <p class="mt-8 text-[9px] text-surface-400 font-bold uppercase tracking-widest leading-relaxed">
                    Analyzing last 7 days of interaction data to identify optimal dispatch windows.
                </p>
            </div>
        </div>
    </div>

    {{-- Recent Deployments & Links --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Recent Campaigns --}}
        <div class="xl:col-span-2 bg-white border rounded-sm shadow-sm">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Recent Deployments</h3>
                </div>
                <a href="{{ route('admin.campaigns.index') }}" class="text-[10px] font-black text-brand uppercase tracking-widest border-b border-brand pb-0.5">Full History &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-5 py-3 text-[10px] font-black text-surface-400 uppercase tracking-widest">Campaign</th>
                            <th class="px-5 py-3 text-[10px] font-black text-surface-400 uppercase tracking-widest text-center">Status</th>
                            <th class="px-5 py-3 text-[10px] font-black text-surface-400 uppercase tracking-widest text-right">Metrics</th>
                            <th class="px-5 py-3 text-[10px] font-black text-surface-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentCampaigns as $campaign)
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-5 py-4">
                                    <p class="text-xs font-black text-surface-900">{{ $campaign->name }}</p>
                                    <p class="text-[9px] text-surface-400 font-bold mt-0.5 uppercase">{{ $campaign->created_at->format('M d, Y') }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full border {{ ['draft'=>'bg-surface-50 text-surface-400','sending'=>'bg-blue-50 text-blue-600','completed'=>'bg-emerald-50 text-emerald-600','cancelled'=>'bg-red-50 text-red-600'][$campaign->status] ?? 'bg-gray-50' }}">
                                        {{ $campaign->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] font-black text-surface-900">{{ $campaign->openRate() }}% Open</span>
                                        <div class="w-16 bg-gray-100 rounded-full h-1 mt-1 overflow-hidden">
                                            <div class="bg-brand h-full" style="width: {{ $campaign->openRate() }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.campaigns.report', $campaign) }}" class="inline-flex p-1.5 text-surface-400 hover:text-brand transition-colors bg-surface-50 rounded-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2zM9 20h6M9 20l-2.25-2.25M15 20V10a2 2 0 00-2-2h-2a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-12 text-center text-[10px] font-bold text-surface-300 uppercase tracking-widest">No recent campaigns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- High Performing Links --}}
        <div class="bg-white border rounded-sm shadow-sm flex flex-col">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Intelligence URLs</h3>
            </div>
            <div class="p-5 flex-1 space-y-4">
                @forelse($topLinks as $link)
                    <div class="group">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-[10px] font-bold text-surface-600 truncate max-w-[180px]" title="{{ $link->url }}">{{ str_replace(['http://', 'https://'], '', $link->url) }}</span>
                            <span class="text-[10px] font-black text-brand">{{ number_format($link->clicks) }}</span>
                        </div>
                        <div class="w-full bg-surface-50 rounded-full h-1 overflow-hidden">
                            <div class="bg-brand h-full transition-all group-hover:scale-x-105 origin-left" style="width: {{ min(100, ($link->clicks / ($topLinks->max('clicks') ?: 1)) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 opacity-30">
                        <svg class="w-12 h-12 text-surface-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <p class="text-[10px] font-black uppercase tracking-widest">No links tracked yet</p>
                    </div>
                @endforelse
            </div>
            <div class="p-4 bg-surface-900 rounded-b-sm">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[10px] font-black text-white/50 uppercase tracking-widest">Global Success Rate</span>
                    <span class="text-xs font-black text-white">{{ $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0 }}%</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-1 overflow-hidden">
                    <div class="bg-emerald-500 h-full" style="width: {{ $totalSent > 0 ? ($totalDelivered / $totalSent) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
