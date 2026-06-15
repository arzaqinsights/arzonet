@extends('layouts.app')
@section('title', 'Intelligent Dashboard Overview')

@section('content')
    <div class="space-y-6 animate-slide-up pb-8">

            {{-- Top Action Bar --}}
            <div class="bg-white rounded-sm border border-brand/10 relative overflow-hidden">

                <div class="p-4 md:p-6 relative z-10">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <p class="text-[10px] font-bold text-brand uppercase tracking-widest mb-1.5">Dashboard Overview</p>
                            <h1 class="text-3xl md:text-4xl font-black mb-2 text-surface-900 tracking-tight" style="font-family:'Outfit',sans-serif;">Welcome Back, <span class="text-brand">{{ explode(' ', app()->has('team_user') ? app('team_user')->name : auth()->user()->name)[0] }}</span> 👋</h1>
                            <p class="text-xs text-surface-600 font-medium max-w-xl leading-relaxed">Monitor your real-time performance, manage your audience, and orchestrate campaigns from your intelligent command center.</p>
                        </div>

                        <div class="shrink-0 mt-4 md:mt-0 flex flex-col items-end justify-center">
                            <!-- Live Digital Clock -->
                            <div class="text-sm font-bold text-surface-500 uppercase tracking-widest mb-1">{{ date('l, F j, Y') }}</div>
                            <div class="flex items-center gap-3">
                                <!-- <div class="relative flex items-center justify-center">
                                    <div class="w-2 h-2 bg-brand rounded-full animate-ping absolute opacity-75"></div>
                                    <div class="w-2 h-2 bg-brand rounded-full relative z-10"></div>
                                </div> -->
                                <h2 id="live-clock" class="text-6xl lg:text-7xl font-black text-brand tracking-tight leading-none" style="font-family:'Outfit',sans-serif;">
                                    {{ date('H:i:s') }}
                                </h2>
                            </div>
                            <script>
                                setInterval(function() {
                                    const now = new Date();
                                    document.getElementById('live-clock').textContent = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                }, 1000);
                            </script>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Key Performance Indicators --}}
            <div class="space-y-8">

                {{-- CRM & Contacts --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-surface-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <h2 class="text-xs font-black text-surface-900 uppercase tracking-widest">CRM & Audience Stats</h2>
                    </div>
                    @php 
                                                            $validContacts = round(($validPercent / 100) * $totalContacts);
                        $invalidContacts = $totalContacts - $validContacts;
                        $subscribedEmails = max(0, $totalContacts - $totalUnsubscribed);
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Total Database --}}
                        <div class="bg-white border border-gray-200 rounded-sm p-5 hover:border-surface-400 transition-all group relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute right-0 top-0 w-32 h-32 bg-surface-100 rounded-bl-full -mr-8 -mt-8 transition-transform duration-500 group-hover:scale-110 opacity-50"></div>
                            <div class="absolute right-4 top-4 text-surface-200 group-hover:text-surface-300 transition-colors">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest mb-2">Total Contacts</p>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-5xl font-black text-surface-900 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ number_format($totalContacts) }}</h3>
                                </div>
                                <div class="mt-4 text-[9px] font-bold text-surface-400 uppercase tracking-widest bg-surface-50 inline-block px-2 py-1 rounded-sm border border-surface-100">Entire Database</div>
                            </div>
                        </div>

                        {{-- Email Contacts --}}
                        <div class="bg-gradient-to-br from-white to-brand/5 border border-brand/20 rounded-sm p-5 hover:border-brand/40 transition-all group relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-brand/10 rounded-bl-full -mr-6 -mt-6 transition-transform duration-500 group-hover:scale-110"></div>
                            <div class="absolute right-4 top-4 text-brand/20 group-hover:text-brand/30 transition-colors">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-brand uppercase tracking-widest mb-2">Email Audience</p>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-5xl font-black text-brand tracking-tight" style="font-family:'Outfit',sans-serif;">{{ number_format($subscribedEmails) }}</h3>
                                </div>
                                <div class="mt-4 flex items-center justify-between text-[9px] font-bold text-brand/70 uppercase tracking-widest">
                                    <span>Subscribed Active</span>
                                    <span>{{ $totalContacts > 0 ? round(($subscribedEmails / $totalContacts) * 100) : 0 }}% of Total</span>
                                </div>
                            </div>
                        </div>

                        {{-- WhatsApp Contacts --}}
                        <div class="bg-gradient-to-br from-white to-emerald-50 border border-emerald-200 rounded-sm p-5 hover:border-emerald-300 transition-all group relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-100/50 rounded-bl-full -mr-6 -mt-6 transition-transform duration-500 group-hover:scale-110"></div>
                            <div class="absolute right-4 top-4 text-emerald-200 group-hover:text-emerald-300 transition-colors">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-2">WhatsApp Audience</p>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-5xl font-black text-emerald-600 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ number_format($waSubscribed) }}</h3>
                                </div>
                                <div class="mt-4 flex items-center justify-between text-[9px] font-bold text-emerald-600/70 uppercase tracking-widest">
                                    <span>Subscribed Active</span>
                                    <span>{{ $waTotalContacts > 0 ? round(($waSubscribed / $waTotalContacts) * 100) : 0 }}% of WA</span>
                                </div>
                            </div>
                        </div>

                        {{-- Active Team --}}
                        <div class="bg-gradient-to-br from-white to-blue-50 border border-blue-200 rounded-sm p-5 hover:border-blue-300 transition-all group relative overflow-hidden flex flex-col justify-between">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-blue-100/50 rounded-bl-full -mr-6 -mt-6 transition-transform duration-500 group-hover:scale-110"></div>
                            <div class="absolute right-4 top-4 text-blue-200 group-hover:text-blue-300 transition-colors">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-2">Active Team</p>
                                <div class="flex items-baseline gap-2">
                                    <h3 class="text-5xl font-black text-blue-600 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $teamMembersCount }}</h3>
                                    <span class="text-[10px] font-bold text-blue-400">/ {{ $teamLimit >= 999 ? 'Unlimited' : $teamLimit . ' Members' }}</span>
                                </div>
                                <div class="mt-4 flex items-center justify-between text-[9px] font-bold text-blue-600/70 uppercase tracking-widest">
                                    <span>Available Seats: {{ $teamLimit >= 999 ? 'Unlimited' : max(0, $teamLimit - $teamMembersCount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Email Intelligence --}}
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <h2 class="text-xs font-black text-brand uppercase tracking-widest">Email Intelligence</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Total Sent (Large Feature Card) - Dark Theme --}}
                        <div class="lg:col-span-2 lg:row-span-2 bg-gradient-to-b from-brand to-[#0d1b2a] border border-brand rounded-sm p-6 relative group flex flex-col justify-between overflow-hidden">
                            <div class="absolute right-0 top-0 w-40 h-40 bg-white/5 rounded-bl-full -mr-10 -mt-10 transition-transform duration-700 group-hover:scale-110"></div>
                            <div class="absolute right-6 top-6 text-white/10 group-hover:text-white/20 transition-colors">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-bold text-white/60 uppercase tracking-widest mb-2">Total Dispatched</p>
                                <h3 class="text-6xl font-black text-white tracking-tight" style="font-family:'Outfit',sans-serif;">{{ number_format($totalSent ?? 0) }}</h3>
                                <div class="mt-4 flex gap-6">
                                    <div>
                                        <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Delivered</p>
                                        <p class="text-lg font-bold text-emerald-400">{{ number_format($totalDelivered ?? 0) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Bounced</p>
                                        <p class="text-lg font-bold text-red-400">{{ number_format($totalBounced ?? 0) }}</p>
                                    </div>
                                </div>
                            </div>
                            @php 
                                                                                    $fakeChart = [];
                                for ($i = 0; $i < 20; $i++) {
                                    $fakeChart[] = ['sent' => rand(30, 100), 'bounced' => rand(0, 15)];
                                }
                                $sparklineData = (isset($chartData) && count($chartData) > 0) ? array_slice($chartData, -20) : $fakeChart;

                                $maxSpark = max(100, max(array_column($sparklineData, 'sent')));
                                $count = count($sparklineData);
                                $step = 100 / max(1, $count - 1);
                                $sentPath = "";
                                $bouncePath = "";
                                $prevX = 0;
                                $prevSentY = 40;
                                $prevBounceY = 40;

                                foreach ($sparklineData as $index => $day) {
                                    $x = $index * $step;
                                    $sentY = 40 - (($day['sent'] / $maxSpark) * 35);
                                    $bounced = $day['bounced'] ?? ($day['sent'] * rand(1, 10) / 100);
                                    $bounceY = 40 - (($bounced / $maxSpark) * 35);

                                    if ($index === 0) {
                                        $sentPath .= "M{$x},{$sentY} ";
                                        $bouncePath .= "M{$x},{$bounceY} ";
                                    } else {
                                        $cp1X = $prevX + ($step / 2);
                                        $cp2X = $x - ($step / 2);
                                        $sentPath .= "C{$cp1X},{$prevSentY} {$cp2X},{$sentY} {$x},{$sentY} ";
                                        $bouncePath .= "C{$cp1X},{$prevBounceY} {$cp2X},{$bounceY} {$x},{$bounceY} ";
                                    }
                                    $prevX = $x;
                                    $prevSentY = $sentY;
                                    $prevBounceY = $bounceY;
                                }
                            @endphp
                            <div class="mt-8 h-20 w-full relative opacity-90 group-hover:opacity-100 transition-opacity z-10">
                                <svg viewBox="0 0 100 40" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="sentGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="white" stop-opacity="0.3"/>
                                            <stop offset="100%" stop-color="white" stop-opacity="0"/>
                                        </linearGradient>
                                        <linearGradient id="bounceGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#f87171" stop-opacity="0.4"/>
                                            <stop offset="100%" stop-color="#f87171" stop-opacity="0"/>
                                        </linearGradient>
                                    </defs>

                                    <path d="{{ $bouncePath }} L100,40 L0,40 Z" fill="url(#bounceGradient)" />
                                    <path d="{{ $bouncePath }}" fill="none" stroke="#f87171" stroke-width="1.5" stroke-linecap="round" />

                                    <path d="{{ $sentPath }} L100,40 L0,40 Z" fill="url(#sentGradient)" />
                                    <path d="{{ $sentPath }}" fill="none" stroke="rgba(255,255,255,0.8)" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </div>
                        </div>

                        {{-- Open Rate --}}
                        <div class="lg:col-span-1 bg-white border border-brand/20 rounded-sm p-5 hover:border-brand/40 transition-all flex flex-col justify-center relative overflow-hidden group">
                            <div class="absolute right-0 top-0 w-20 h-20 bg-brand/5 rounded-bl-full -mr-4 -mt-4 transition-transform duration-500 group-hover:scale-110"></div>
                            <p class="text-[10px] font-bold text-brand/60 uppercase tracking-widest mb-1 relative z-10">Avg Open Rate</p>
                            <div class="flex items-end gap-2 mb-4 relative z-10">
                                <h3 class="text-5xl font-black text-brand tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $globalOpenRate ?? 0 }}%</h3>
                            </div>
                            <div class="w-full bg-brand/10 rounded-sm h-1.5 overflow-hidden relative z-10">
                                <div class="bg-brand h-full rounded-sm" style="width: {{ $globalOpenRate ?? 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Click Rate --}}
                        <div class="lg:col-span-1 bg-brand/5 border border-brand/20 rounded-sm p-5 hover:border-brand/40 transition-all flex flex-col justify-center relative overflow-hidden group">
                            <div class="absolute right-0 top-0 w-20 h-20 bg-white/50 rounded-bl-full -mr-4 -mt-4 transition-transform duration-500 group-hover:scale-110"></div>
                            <p class="text-[10px] font-bold text-brand/60 uppercase tracking-widest mb-1 relative z-10">Avg Click Rate</p>
                            <div class="flex items-end gap-2 mb-4 relative z-10">
                                <h3 class="text-5xl font-black text-brand tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $globalClickRate ?? 0 }}%</h3>
                            </div>
                            <div class="w-full bg-brand/10 rounded-sm h-1.5 overflow-hidden relative z-10">
                                <div class="bg-brand h-full rounded-sm" style="width: {{ $globalClickRate ?? 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Delivery Status Card --}}
                        <div class="lg:col-span-2 bg-white border border-gray-100 rounded-sm flex items-stretch hover:border-brand/30 transition-colors divide-x-2 divide-gray-100">
                            <div class="flex-1 p-5">
                                <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest mb-2">Total Opens</p>
                                <h3 class="text-3xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($totalOpens ?? 0) }}</h3>
                            </div>
                            <div class="flex-1 p-5">
                                <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest mb-2">Total Clicks</p>
                                <h3 class="text-3xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($totalClicks ?? 0) }}</h3>
                            </div>
                            <div class="flex-1 p-5 bg-red-50/20">
                                <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-2">Complaints</p>
                                <h3 class="text-3xl font-black text-red-500" style="font-family:'Outfit',sans-serif;">{{ number_format($totalComplaints ?? 0) }}</h3>
                            </div>
                            <div class="flex-1 p-5 bg-amber-50/20">
                                <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest mb-2">Unsubscribed</p>
                                <h3 class="text-3xl font-black text-amber-600" style="font-family:'Outfit',sans-serif;">{{ number_format($totalUnsubscribed ?? 0) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- WhatsApp Intelligence --}}
                <div class="bg-emerald-50/50 p-4 border border-emerald-100 rounded-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="p-1.5 bg-emerald-100 rounded-sm">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <h2 class="text-xs font-black text-emerald-800 uppercase tracking-widest">WhatsApp Intelligence</h2>
                    </div>

                    @if(isset($waTotalSent) && $waTotalSent > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            {{-- WA Sent (Large Card) - Dark Emerald --}}
                            <div class="lg:col-span-2 lg:row-span-2 bg-gradient-to-b from-emerald-600 to-emerald-800 border border-emerald-700 rounded-sm p-6 relative flex flex-col justify-between overflow-hidden group">
                                <div class="absolute right-0 top-0 w-40 h-40 bg-white/10 rounded-bl-full -mr-10 -mt-10 transition-transform duration-700 group-hover:scale-110"></div>
                                <div class="absolute right-6 top-6 text-white/20 group-hover:text-white/30 transition-colors">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <p class="text-xs font-bold text-white/70 uppercase tracking-widest mb-2">WhatsApp Sent</p>
                                    <h3 class="text-6xl font-black text-white tracking-tight" style="font-family:'Outfit',sans-serif;">{{ number_format($waTotalSent) }}</h3>

                                    <div class="mt-4 flex gap-6">
                                        <div>
                                            <p class="text-[10px] font-bold text-emerald-200 uppercase tracking-widest">Delivered</p>
                                            <p class="text-lg font-bold text-white">{{ number_format($waDelivered ?? 0) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-emerald-200 uppercase tracking-widest">Failed</p>
                                            <p class="text-lg font-bold text-red-300">{{ number_format($waFailed ?? 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                                @php 
                                                                                        $fakeWA = [];
                                    for ($i = 0; $i < 20; $i++) {
                                        $fakeWA[] = ['sent' => rand(20, 100), 'failed' => rand(0, 10)];
                                    }

                                    $maxSparkWA = max(100, max(array_column($fakeWA, 'sent')));
                                    $countWA = count($fakeWA);
                                    $stepWA = 100 / max(1, $countWA - 1);
                                    $sentPathWA = "";
                                    $failedPathWA = "";
                                    $prevX = 0;
                                    $prevSentY = 40;
                                    $prevFailedY = 40;

                                    foreach ($fakeWA as $index => $day) {
                                        $x = $index * $stepWA;
                                        $sentY = 40 - (($day['sent'] / $maxSparkWA) * 35);
                                        $failedY = 40 - (($day['failed'] / $maxSparkWA) * 35);

                                        if ($index === 0) {
                                            $sentPathWA .= "M{$x},{$sentY} ";
                                            $failedPathWA .= "M{$x},{$failedY} ";
                                        } else {
                                            $cp1X = $prevX + ($stepWA / 2);
                                            $cp2X = $x - ($stepWA / 2);
                                            $sentPathWA .= "C{$cp1X},{$prevSentY} {$cp2X},{$sentY} {$x},{$sentY} ";
                                            $failedPathWA .= "C{$cp1X},{$prevFailedY} {$cp2X},{$failedY} {$x},{$failedY} ";
                                        }
                                        $prevX = $x;
                                        $prevSentY = $sentY;
                                        $prevFailedY = $failedY;
                                    }
                                @endphp
                                <div class="mt-8 h-20 w-full relative opacity-90 group-hover:opacity-100 transition-opacity z-10">
                                    <svg viewBox="0 0 100 40" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="waSentGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="white" stop-opacity="0.3"/>
                                                <stop offset="100%" stop-color="white" stop-opacity="0"/>
                                            </linearGradient>
                                            <linearGradient id="waFailedGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#fca5a5" stop-opacity="0.4"/>
                                                <stop offset="100%" stop-color="#fca5a5" stop-opacity="0"/>
                                            </linearGradient>
                                        </defs>

                                        <path d="{{ $failedPathWA }} L100,40 L0,40 Z" fill="url(#waFailedGradient)" />
                                        <path d="{{ $failedPathWA }}" fill="none" stroke="#fca5a5" stroke-width="1.5" stroke-linecap="round" />

                                        <path d="{{ $sentPathWA }} L100,40 L0,40 Z" fill="url(#waSentGradient)" />
                                        <path d="{{ $sentPathWA }}" fill="none" stroke="rgba(255,255,255,0.8)" stroke-width="1.5" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>

                            {{-- WA Read Rate --}}
                            <div class="lg:col-span-1 bg-white border border-emerald-200 rounded-sm p-5 hover:border-emerald-300 transition-all flex flex-col justify-center relative overflow-hidden group">
                                <div class="absolute right-0 top-0 w-20 h-20 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform duration-500 group-hover:scale-110"></div>
                                <p class="text-[10px] font-bold text-emerald-600/70 uppercase tracking-widest mb-1 relative z-10">Avg Read Rate</p>
                                <div class="flex items-end gap-2 mb-4 relative z-10">
                                    <h3 class="text-5xl font-black text-emerald-600 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $waReadRate ?? 0 }}%</h3>
                                </div>
                                <div class="w-full bg-emerald-100 rounded-sm h-1.5 overflow-hidden relative z-10">
                                    <div class="bg-emerald-500 h-full rounded-sm" style="width: {{ $waReadRate ?? 0 }}%"></div>
                                </div>
                            </div>

                            {{-- WA Reply Rate --}}
                            <div class="lg:col-span-1 bg-emerald-50 border border-emerald-200 rounded-sm p-5 hover:border-emerald-300 transition-all flex flex-col justify-center relative overflow-hidden group">
                                <div class="absolute right-0 top-0 w-20 h-20 bg-white/50 rounded-bl-full -mr-4 -mt-4 transition-transform duration-500 group-hover:scale-110"></div>
                                <p class="text-[10px] font-bold text-emerald-600/70 uppercase tracking-widest mb-1 relative z-10">Avg Reply Rate</p>
                                <div class="flex items-end gap-2 mb-4 relative z-10">
                                    <h3 class="text-5xl font-black text-emerald-600 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $waReplyRate ?? 0 }}%</h3>
                                </div>
                                <div class="w-full bg-emerald-200 rounded-sm h-1.5 overflow-hidden relative z-10">
                                    <div class="bg-emerald-600 h-full rounded-sm" style="width: {{ $waReplyRate ?? 0 }}%"></div>
                                </div>
                            </div>

                            {{-- Status Split Card --}}
                            <div class="lg:col-span-2 bg-white border border-gray-100 rounded-sm p-4 hover:border-emerald-200 transition-colors flex items-center justify-between">
                                <div class="flex-1 border-r border-gray-100 pr-4">
                                    <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest mb-1">Total Reads</p>
                                    <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($waTotalReads ?? 0) }}</h3>
                                </div>
                                <div class="flex-1 border-r border-gray-100 px-4">
                                    <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest mb-1">Total Replies</p>
                                    <h3 class="text-2xl font-black text-surface-900" style="font-family:'Outfit',sans-serif;">{{ number_format($waTotalReplies ?? 0) }}</h3>
                                </div>
                                <div class="flex-1 pl-4">
                                    <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest mb-1">Opt-outs</p>
                                    <h3 class="text-2xl font-black text-amber-500" style="font-family:'Outfit',sans-serif;">{{ number_format($waOptOuts ?? 0) }}</h3>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="border border-dashed border-gray-200 rounded-sm p-8 text-center bg-white/50 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative z-10">
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mx-auto mb-3 border border-emerald-100">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest mb-1">WhatsApp Service Inactive</h3>
                                <p class="text-xs text-surface-500 font-medium max-w-md mx-auto mb-4">Connect your WhatsApp Business API to unlock real-time messaging analytics and automated communication.</p>
                                <a href="#" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest rounded-sm transition-all hover:bg-emerald-600 hover:-translate-y-0.5">
                                    Connect WhatsApp
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        {{-- Advanced Analytics Section --}}
        <div class="space-y-6">
            
            {{-- Non-repeating Stats Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Sender Reputation --}}
                <div class="bg-white border border-gray-200 rounded-sm p-5 hover:border-surface-400 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Sender Reputation</p>
                            @if($emailReputation >= 90)
                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Excellent</span>
                            @elseif($emailReputation >= 75)
                                <span class="text-[9px] font-black text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Good</span>
                            @else
                                <span class="text-[9px] font-black text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Critical</span>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-1 mb-2">
                            <h3 class="text-5xl font-black text-surface-900 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $emailReputation }}</h3>
                            <span class="text-[10px] font-bold text-surface-400">/ 100</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                            @php 
                                $repColor = $emailReputation >= 90 ? 'bg-emerald-500' : ($emailReputation >= 75 ? 'bg-amber-500' : 'bg-red-500');
                            @endphp
                            <div class="{{ $repColor }} h-full rounded-sm" style="width: {{ $emailReputation }}%"></div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5">
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Complaints</span>
                            <span class="font-black text-surface-900">{{ number_format($totalComplaints) }} ({{ number_format($complaintRate, 2) }}%)</span>
                        </div>
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Bounce Penalty</span>
                            <span class="font-black text-surface-900">-{{ number_format($bounceRate * 2, 1) }} pts</span>
                        </div>
                    </div>
                </div>

                {{-- WA API Health --}}
                <div class="bg-white border border-gray-200 rounded-sm p-5 hover:border-surface-400 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">WA API Quality</p>
                            @if($waReputation >= 90)
                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Excellent</span>
                            @elseif($waReputation >= 75)
                                <span class="text-[9px] font-black text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Good</span>
                            @else
                                <span class="text-[9px] font-black text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Warning</span>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-1 mb-2">
                            <h3 class="text-5xl font-black text-surface-900 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $waReputation }}</h3>
                            <span class="text-[10px] font-bold text-surface-400">/ 100</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                            @php 
                                $waRepColor = $waReputation >= 90 ? 'bg-emerald-500' : ($waReputation >= 75 ? 'bg-amber-500' : 'bg-red-500');
                            @endphp
                            <div class="{{ $waRepColor }} h-full rounded-sm" style="width: {{ $waReputation }}%"></div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5">
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Dispatched</span>
                            <span class="font-black text-surface-900">{{ number_format($waTotalSent) }}</span>
                        </div>
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Failed / Undeliv.</span>
                            <span class="font-black text-red-600">{{ number_format($waFailed) }} ({{ number_format($waBounceRate, 2) }}%)</span>
                        </div>
                    </div>
                </div>

                {{-- Bounce Rate --}}
                <div class="bg-white border border-gray-200 rounded-sm p-5 hover:border-surface-400 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-[10px] font-bold text-red-600 uppercase tracking-widest">Email Bounce Rate</p>
                            @if($bounceRate < 2)
                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Optimal</span>
                            @elseif($bounceRate < 5)
                                <span class="text-[9px] font-black text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Elevated</span>
                            @else
                                <span class="text-[9px] font-black text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">High Risk</span>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-1 mb-2">
                            <h3 class="text-5xl font-black text-red-600 tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $bounceRate }}%</h3>
                        </div>
                        <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                            <div class="bg-red-500 h-full rounded-sm" style="width: {{ min(100, $bounceRate * 5) }}%"></div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5">
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Bounced</span>
                            <span class="font-black text-red-600">{{ number_format($totalBounced) }}</span>
                        </div>
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Total Attempted</span>
                            <span class="font-black text-surface-900">{{ number_format($totalSent) }}</span>
                        </div>
                    </div>
                </div>

                {{-- ISP Dispatch Success --}}
                @php 
                    $deliverySuccess = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 100;
                @endphp
                <div class="bg-white border border-gray-200 rounded-sm p-5 hover:border-surface-400 transition-all flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <p class="text-[10px] font-bold text-brand uppercase tracking-widest">ISP Dispatch Success</p>
                            @if($deliverySuccess >= 98)
                                <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Optimal</span>
                            @elseif($deliverySuccess >= 95)
                                <span class="text-[9px] font-black text-amber-600 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Fair</span>
                            @else
                                <span class="text-[9px] font-black text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded-sm uppercase tracking-wider">Suboptimal</span>
                            @endif
                        </div>
                        <div class="flex items-baseline gap-1 mb-2">
                            <h3 class="text-5xl font-black text-brand tracking-tight" style="font-family:'Outfit',sans-serif;">{{ $deliverySuccess }}%</h3>
                        </div>
                        <div class="w-full bg-gray-100 rounded-sm h-1.5 overflow-hidden">
                            <div class="bg-brand h-full rounded-sm" style="width: {{ $deliverySuccess }}%"></div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5">
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Delivered</span>
                            <span class="font-black text-brand">{{ number_format($totalDelivered) }}</span>
                        </div>
                        <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-surface-500">
                            <span>Bounces/Drops</span>
                            <span class="font-black text-surface-900">{{ number_format($totalSent - $totalDelivered) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Visual Charts & Distributions --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Performance Chart --}}
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-sm flex flex-col">
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
                                <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-surface-900 text-white text-[10px] py-2 px-3 rounded-sm opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50 transition-all border border-white/10">
                                    <p class="font-black border-b border-white/10 pb-1 mb-1">{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</p>
                                    <div class="flex justify-between gap-4"><span>Sent:</span> <span class="font-bold">{{ number_format($day['sent']) }}</span></div>
                                    <div class="flex justify-between gap-4"><span>Failed:</span> <span class="font-bold text-red-400">{{ number_format($day['failed']) }}</span></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-4 border-t border-gray-150 flex justify-between items-center text-[10px] font-black text-surface-400 uppercase tracking-widest bg-gray-50/50">
                        <span>{{ \Carbon\Carbon::parse($chartData[0]['date'])->format('M d') }}</span>
                        <span class="text-surface-300">Live Trend Synchronization</span>
                        <span>Today</span>
                    </div>
                </div>

                {{-- Hourly Heatmap --}}
                <div class="bg-white border border-gray-200 rounded-sm p-5 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight mb-4" style="font-family:'Outfit',sans-serif;">Peak Engagement Time</h3>
                    <div class="grid grid-cols-6 gap-1 h-32 items-end flex-1">
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

            {{-- ISP Performance & URLs Matrix --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- ISP Health Tracking --}}
                <div class="bg-white border border-gray-200 rounded-sm p-5">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight mb-4" style="font-family:'Outfit',sans-serif;">ISP Reputation Index</h3>
                    <div class="space-y-4">
                        @forelse($ispPerformance as $isp)
                            <div class="group">
                                <div class="flex justify-between items-end mb-1.5">
                                    <span class="text-[10px] font-black text-surface-800 uppercase tracking-widest">{{ $isp->domain }}</span>
                                    <span class="text-[10px] font-black {{ $isp->delivery_rate > 95 ? 'text-emerald-600' : 'text-brand' }}">{{ $isp->delivery_rate }}%</span>
                                </div>
                                <div class="w-full bg-surface-50 rounded-sm h-1.5 overflow-hidden">
                                    <div class="bg-surface-800 h-full group-hover:bg-brand transition-all rounded-sm" style="width: {{ $isp->delivery_rate }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center py-6">Calculating reputation...</p>
                        @endforelse
                    </div>
                </div>

                {{-- Link Engagement Matrix --}}
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-sm p-5 flex flex-col">
                    <div class="border-b border-gray-100 pb-3 mb-4 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-tight" style="font-family:'Outfit',sans-serif;">Intelligence URLs</h3>
                        <span class="text-[9px] font-black bg-surface-100 text-surface-500 px-2 py-1 rounded-sm uppercase tracking-widest">Top Clicks</span>
                    </div>
                    <div class="space-y-4 flex-1">
                        @forelse($topLinks as $link)
                            <div class="group">
                                <div class="flex justify-between items-center mb-1.5">
                                    <span class="text-[10px] font-bold text-surface-600 truncate max-w-[320px]" title="{{ $link->url }}">{{ str_replace(['http://', 'https://'], '', $link->url) }}</span>
                                    <span class="text-[10px] font-black text-brand">{{ number_format($link->clicks) }}</span>
                                </div>
                                <div class="w-full bg-surface-50 rounded-sm h-1 overflow-hidden">
                                    <div class="bg-brand h-full rounded-sm transition-all group-hover:scale-x-105 origin-left" style="width: {{ min(100, ($link->clicks / ($topLinks->max('clicks') ?: 1)) * 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 opacity-30">
                                <svg class="w-12 h-12 text-surface-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <p class="text-[10px] font-black uppercase tracking-widest">No links tracked yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Recent Campaigns / Deployments --}}
            <div class="bg-white border border-gray-200 rounded-sm">
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
                        <tbody class="divide-y divide-gray-150">
                            @forelse($recentCampaigns as $campaign)
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-5 py-4">
                                        <p class="text-xs font-black text-surface-900">{{ $campaign->name }}</p>
                                        <p class="text-[9px] text-surface-400 font-bold mt-0.5 uppercase">{{ $campaign->created_at->format('M d, Y') }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-sm border {{ ['draft' => 'bg-surface-50 text-surface-400 border-gray-250', 'sending' => 'bg-blue-50 text-blue-600 border-blue-100', 'completed' => 'bg-emerald-50 text-emerald-600 border-emerald-100', 'cancelled' => 'bg-red-50 text-red-600 border-red-100'][$campaign->status] ?? 'bg-gray-50' }}">
                                            {{ $campaign->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[10px] font-black text-surface-900">{{ $campaign->openRate() }}% Open</span>
                                            <div class="w-16 bg-gray-100 rounded-sm h-1 mt-1 overflow-hidden">
                                                <div class="bg-brand h-full rounded-sm" style="width: {{ $campaign->openRate() }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.campaigns.report', $campaign) }}" class="inline-flex p-1.5 text-surface-400 hover:text-brand transition-colors bg-surface-50 rounded-sm border border-gray-200 hover:border-brand/20">
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

        </div>
@endsection
