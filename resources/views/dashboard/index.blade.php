@extends('layouts.app')
@section('title', 'Dashboard Overview')

@section('content')
<div class="space-y-4 animate-slide-up pb-8">

    {{-- Key Performance Indicators --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        {{-- Total Sent & Delivered --}}
        <div class="bg-white border rounded-sm p-4 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-3 transition-opacity">
                <svg class="w-12 h-12 text-surface-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Total Sent</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($totalSent) }}</h3>
                <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1 py-0.5 rounded-sm">{{ number_format($totalDelivered) }} Delivered</span>
            </div>
            <div class="mt-4 h-8 w-full flex items-end gap-0.5">
                @php $sparklineData = array_slice($chartData, -15); @endphp
                @foreach($sparklineData as $day)
                    @php 
                        $maxSpark = max(array_column($sparklineData, 'sent')) ?: 1;
                        $h = $totalSent > 0 ? ($day['sent'] / $maxSpark * 100) : 0; 
                    @endphp
                    <div class="flex-1 bg-surface-100 group-hover:bg-brand transition-colors" style="height: {{ max(10, $h) }}%" title="{{ $day['date'] }}: {{ $day['sent'] }}"></div>
                @endforeach
            </div>
        </div>

        {{-- Avg Open Rate --}}
        <div class="bg-white border border-gray-200 rounded-sm p-4 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-3 transition-opacity">
                <svg class="w-12 h-12 text-surface-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </div>
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Open Rate</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ $globalOpenRate }}%</h3>
                @php
                    $openStatus = $globalOpenRate >= 25 ? ['label' => 'High', 'class' => 'text-emerald-600 bg-emerald-50'] : 
                                 ($globalOpenRate >= 15 ? ['label' => 'Average', 'class' => 'text-blue-600 bg-blue-50'] : 
                                 ['label' => 'Low', 'class' => 'text-surface-400 bg-surface-50']);
                @endphp
                <span class="text-[9px] font-bold px-1 py-0.5 rounded-sm {{ $openStatus['class'] }}">{{ $openStatus['label'] }}</span>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-[9px] font-black text-surface-400 mb-1 uppercase tracking-tighter">
                    <span>Reach</span>
                    <span>{{ number_format($totalOpens) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-full transition-all duration-1000" style="width: {{ $globalOpenRate }}%"></div>
                </div>
            </div>
        </div>

        {{-- Click-Through Rate --}}
        <div class="bg-white border border-gray-200 rounded-sm p-4 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-3 transition-opacity">
                <svg class="w-12 h-12 text-surface-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
            </div>
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">CTR</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ $globalClickRate }}%</h3>
                @php
                    $clickStatus = $globalClickRate >= 10 ? ['label' => 'Strong', 'class' => 'text-emerald-600 bg-emerald-50'] : 
                                  ($globalClickRate >= 5 ? ['label' => 'Good', 'class' => 'text-blue-600 bg-blue-50'] : 
                                  ['label' => 'Steady', 'class' => 'text-surface-400 bg-surface-50']);
                @endphp
                <span class="text-[9px] font-bold px-1 py-0.5 rounded-sm {{ $clickStatus['class'] }}">{{ $clickStatus['label'] }}</span>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-[9px] font-black text-surface-400 mb-1 uppercase tracking-tighter">
                    <span>Clicks</span>
                    <span>{{ number_format($totalClicks) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                    <div class="bg-brand h-full transition-all duration-1000" style="width: {{ $globalClickRate }}%"></div>
                </div>
            </div>
        </div>

        {{-- Bounce Rate --}}
        <div class="bg-white border border-gray-200 rounded-sm p-4 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-3 transition-opacity">
                <svg class="w-12 h-12 text-surface-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Bounce Rate</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ $bounceRate }}%</h3>
                @php
                    $bounceStatus = $bounceRate <= 2 ? ['label' => 'Healthy', 'class' => 'text-emerald-600 bg-emerald-50'] : 
                                   ($bounceRate <= 5 ? ['label' => 'Warning', 'class' => 'text-amber-600 bg-amber-50'] : 
                                   ['label' => 'High Risk', 'class' => 'text-red-600 bg-red-50']);
                @endphp
                <span class="text-[9px] font-bold px-1 py-0.5 rounded-sm {{ $bounceStatus['class'] }}">{{ $bounceStatus['label'] }}</span>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-[9px] font-black text-surface-400 mb-1 uppercase tracking-tighter">
                    <span>Bounces</span>
                    <span>{{ $totalBounced }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                    <div class="bg-red-500 h-full transition-all duration-1000" style="width: {{ $bounceRate }}%"></div>
                </div>
            </div>
        </div>

        {{-- Total Audience --}}
        <div class="bg-white border border-gray-200 rounded-sm p-4 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-3 transition-opacity">
                <svg class="w-12 h-12 text-surface-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <p class="text-[10px] font-black text-surface-500 uppercase tracking-widest">Audience</p>
            <div class="mt-1.5 flex items-baseline gap-2">
                <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($totalContacts) }}</h3>
                <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1 py-0.5 rounded-sm">+{{ number_format($contactsThisWeek) }}</span>
            </div>
            <div class="mt-4 h-8 w-full flex items-end gap-0.5">
                @php $maxAudienceSpark = max($audienceGrowth->toArray() ?: [1]) ?: 1; @endphp
                @for($i = 14; $i >= 0; $i--)
                    @php 
                        $date = now()->subDays($i)->format('Y-m-d');
                        $count = $audienceGrowth[$date] ?? 0;
                        $h = $totalContacts > 0 ? ($count / $maxAudienceSpark * 100) : 0;
                    @endphp
                    <div class="flex-1 bg-surface-100 group-hover:bg-emerald-500 transition-colors" style="height: {{ max(10, $h) }}%" title="{{ $date }}: {{ $count }}"></div>
                @endfor
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Performance Trends Chart --}}
        <div class="lg:col-span-2 bg-white border rounded-sm flex flex-col">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Performance Trends</h3>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Last 30 Days Activity</p>
                </div>
                <div class="flex items-center gap-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 bg-blue-500 rounded-sm"></div> Sent</div>
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 bg-red-400 rounded-sm"></div> Failed</div>
                </div>
            </div>
            <div class="p-6 flex-1 flex items-end gap-1.5 h-64 relative bg-gray-50/20">
                @if(count($chartData) > 0)
                    @php $maxSentGlobal = max(array_column($chartData, 'sent')) ?: 1; @endphp
                    @foreach($chartData as $day)
                        @php $sentH = ($day['sent'] / $maxSentGlobal) * 100; @endphp
                        <div class="flex-1 flex flex-col justify-end group relative h-full">
                            <div class="w-full bg-blue-100 relative rounded-t-sm transition-all duration-300 group-hover:bg-blue-300" style="height: {{ max(2, $sentH) }}%">
                                <div class="absolute bottom-0 left-0 w-full bg-red-400 rounded-t-sm opacity-80" style="height: {{ $day['sent'] > 0 ? ($day['failed'] / $day['sent']) * 100 : 0 }}%"></div>
                            </div>
                            <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] py-2 px-3 rounded-sm opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 shadow-xl transition-all border border-white/10">
                                <p class="font-black border-b border-white/10 pb-1 mb-1">{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</p>
                                <div class="flex justify-between gap-4"><span>Sent:</span> <span class="font-bold">{{ number_format($day['sent']) }}</span></div>
                                <div class="flex justify-between gap-4"><span>Failed:</span> <span class="font-bold">{{ number_format($day['failed']) }}</span></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <div class="p-4 border-t border-gray-50 flex justify-between items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                <span>{{ \Carbon\Carbon::parse($chartData[0]['date'])->format('M d') }}</span>
                <span>Timeline (30 Days)</span>
                <span>Today</span>
            </div>
        </div>

        {{-- Top Clicked Links & Audience Health --}}
        <div class="flex flex-col gap-4">
            {{-- Top Clicked Links --}}
            <div class="bg-white border rounded-sm flex flex-col">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Performance URLs</h3>
                </div>
                <div class="p-4 flex-1 space-y-3">
                    @php $maxClicks = $topLinks->max('clicks') ?: 1; @endphp
                    @forelse($topLinks as $link)
                        <div class="group cursor-default">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-bold text-surface-600 truncate max-w-[150px]" title="{{ $link->url }}">{{ str_replace(['http://', 'https://'], '', $link->url) }}</span>
                                <span class="text-[10px] font-black text-brand">{{ number_format($link->clicks) }}</span>
                            </div>
                            <div class="w-full bg-gray-50 border border-gray-100 rounded-sm h-1.5 overflow-hidden">
                                <div class="bg-surface-800 h-full group-hover:bg-brand transition-all" style="width: {{ ($link->clicks / $maxClicks) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center py-6">No Data</p>
                    @endforelse
                </div>
            </div>

            {{-- Audience Health --}}
            <div class="bg-white border rounded-sm p-4">
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight mb-4" style="font-family:'Outfit',sans-serif;">Audience Health</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">
                            <span>Deliverability</span>
                            <span class="text-emerald-600">{{ $validPercent }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-sm h-2 overflow-hidden p-0.5 border border-gray-50">
                            <div class="bg-emerald-500 h-full rounded-sm" style="width: {{ $validPercent }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">
                            <span>Invalid/Risky</span>
                            <span class="text-red-500">{{ $invalidPercent }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-sm h-2 overflow-hidden p-0.5 border border-gray-50">
                            <div class="bg-red-500 h-full rounded-sm" style="width: {{ $invalidPercent }}%"></div>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-between items-center text-[10px] font-black text-gray-400 uppercase tracking-widest border-t border-gray-50">
                        <span>Active Unsubs</span>
                        <span class="text-gray-900">{{ number_format($totalUnsubscribed) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Row: Campaigns & Engagement Distribution --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        {{-- Recent Campaigns Table --}}
        <div class="xl:col-span-2 bg-white border rounded-sm flex flex-col">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Recent Deployments</h3>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Dispatch History</p>
                </div>
                <a href="{{ route('admin.campaigns.index') }}" class="text-[10px] font-black text-brand uppercase tracking-widest">View All &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Campaign</th>
                            <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                            <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Progress</th>
                            <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Open Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentCampaigns as $campaign)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <p class="text-xs font-bold text-gray-900">{{ $campaign->name }}</p>
                                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-tighter">{{ $campaign->created_at->format('M d, H:i') }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-sm border {{ ['draft'=>'bg-gray-50 text-gray-400 border-gray-200','sending'=>'bg-blue-50 text-blue-600 border-blue-200','completed'=>'bg-emerald-50 text-emerald-600 border-emerald-200','cancelled'=>'bg-red-50 text-red-600 border-red-200'][$campaign->status] ?? 'bg-gray-50' }}">
                                        {{ $campaign->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] font-black text-gray-700">{{ $campaign->progress() }}%</span>
                                        <div class="w-16 bg-gray-100 rounded-sm h-1 mt-1 overflow-hidden">
                                            <div class="bg-blue-500 h-full" style="width: {{ $campaign->progress() }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="text-xs font-black {{ ($campaign->open_rate ?? 0) > 20 ? 'text-emerald-600' : 'text-gray-900' }}">
                                        {{ number_format($campaign->open_rate ?? 0, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-12 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">No Activity</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Engagement Distribution --}}
        <div class="bg-white border rounded-sm flex flex-col">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-base font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Engagement Matrix</h3>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">Global Interaction Breakdown</p>
            </div>
            <div class="p-5 flex-1 space-y-6 bg-gray-50/20">
                <div>
                    <div class="flex justify-between items-end mb-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <span>SES Monthly Quota Usage</span>
                        <span>{{ round($usageStats['monthly']['percent']) }}%</span>
                    </div>
                    <div class="w-full bg-white border border-gray-100 rounded-sm h-3 overflow-hidden p-0.5 shadow-inner">
                        <div class="h-full rounded-sm {{ $usageStats['monthly']['percent'] > 85 ? 'bg-red-500' : 'bg-surface-800' }}" style="width: {{ $usageStats['monthly']['percent'] }}%"></div>
                    </div>
                    <p class="text-[9px] text-gray-500 mt-2 font-black uppercase tracking-tighter">
                        {{ number_format($usageStats['monthly']['sent']) }} / {{ number_format($usageStats['monthly']['limit']) }}
                    </p>
                </div>

                <div class="pt-4 border-t border-gray-100 space-y-4">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Activity Distribution</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-4 bg-white border border-gray-100 rounded-sm group hover:border-brand transition-colors">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Total Opens</p>
                            <p class="text-xl font-black text-gray-900 mt-1">{{ number_format($totalOpens) }}</p>
                        </div>
                        <div class="p-4 bg-white border border-gray-100 rounded-sm group hover:border-brand transition-colors">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Total Clicks</p>
                            <p class="text-xl font-black text-gray-900 mt-1">{{ number_format($totalClicks) }}</p>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-surface-900 rounded-sm relative overflow-hidden shadow-lg group">
                        <div class="absolute -right-6 -bottom-6 w-16 h-16 bg-white/5 rounded-full transition-transform group-hover:scale-150 duration-700"></div>
                        <p class="text-[10px] font-black text-white/50 uppercase tracking-widest">Overall Success Rate</p>
                        <p class="text-3xl font-black text-white mt-1">{{ $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0 }}%</p>
                        <div class="mt-3 w-full bg-white/10 rounded-full h-1 overflow-hidden">
                            <div class="bg-emerald-500 h-full" style="width: {{ $totalSent > 0 ? ($totalDelivered / $totalSent) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-3 border-t border-gray-50 text-center">
                <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest italic">Live data synced with Amazon SES API</p>
            </div>
        </div>
    </div>

</div>
@endsection
