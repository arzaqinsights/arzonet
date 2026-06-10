@extends('layouts.app')
@section('title', 'Contact Profile: ' . $email->email)
@section('heading', 'Contact Profile')

@section('content')
<div class="space-y-8 animate-slide-up">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- ── Contact Identity & Tags ── --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-8 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary-500 to-indigo-500"></div>
                <div class="w-24 h-24 rounded-full bg-primary-100 flex items-center justify-center mx-auto mb-6 text-3xl font-black text-primary-700 shadow-inner">
                    {{ substr($email->name ?? $email->email, 0, 1) }}
                </div>
                <h2 class="text-2xl font-black text-surface-900">{{ $email->name ?? 'Unnamed Contact' }}</h2>
                <p class="text-surface-500 font-medium mb-6">{{ $email->email }}</p>
                
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    @php
                        $statusCls = match($email->subscription_status) {
                            'subscribed' => 'badge-success',
                            'unsubscribed' => 'badge-danger',
                            default => 'badge-neutral',
                        };
                    @endphp
                    <span class="badge {{ $statusCls }} px-4 py-1.5">{{ ucfirst($email->subscription_status) }}</span>
                    <span class="badge badge-info px-4 py-1.5">List: {{ $email->emailList->name ?? 'Deleted List' }}</span>
                </div>

                <div class="pt-6 border-t border-surface-100 text-left">
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3">Tags & Classification</p>
                    <form action="{{ route('admin.contacts.tags.update', $email->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="text" name="tags" value="{{ implode(', ', $email->tags ?? []) }}" 
                               class="form-input text-sm" placeholder="Add tags (comma separated)...">
                        <button class="btn btn-ghost btn-sm w-full border border-surface-200">Update Tags</button>
                    </form>
                </div>

                {{-- ── Subscription Topics ── --}}
                @if($topics->isNotEmpty())
                <div class="pt-6 border-t border-surface-100 text-left mt-6">
                    <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-3">Subscription Topics</p>
                    <form action="{{ route('admin.contacts.topics.update', $email->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                            @foreach($topics as $topic)
                                @php
                                    $isSubscribed = is_null($email->subscribed_topics) || in_array($topic->id, $email->subscribed_topics);
                                @endphp
                                <label class="flex items-start gap-2 cursor-pointer group">
                                    <input type="checkbox" name="topics[]" value="{{ $topic->id }}" 
                                           class="mt-1 rounded border-surface-300 text-primary-600 focus:ring-primary-500"
                                           {{ $isSubscribed ? 'checked' : '' }}>
                                    <div class="flex-1">
                                        <div class="text-sm font-bold text-surface-900 group-hover:text-primary-600 transition-colors">{{ $topic->name }}</div>
                                        @if($topic->description)
                                            <div class="text-[10px] text-surface-500 leading-tight mt-0.5">{{ $topic->description }}</div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <button class="btn btn-ghost btn-sm w-full border border-surface-200 mt-2">Update Topics</button>
                    </form>
                </div>
                @endif
            </div>

            {{-- ── Quick Stats ── --}}
            <div class="glass-card p-6 grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-[10px] font-black text-surface-400 uppercase">Sent</p>
                    <p class="text-xl font-black text-surface-900">{{ $stats['total_sent'] }}</p>
                </div>
                <div class="border-x border-surface-100">
                    <p class="text-[10px] font-black text-emerald-500 uppercase">Opens</p>
                    <p class="text-xl font-black text-emerald-600">{{ $stats['total_opens'] }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-indigo-500 uppercase">Clicks</p>
                    <p class="text-xl font-black text-indigo-600">{{ $stats['total_clicks'] }}</p>
                </div>
            </div>

            {{-- ── AI Lead Score Gauge ── --}}
            @php
                $leadScore = $email->engagement_score ?? 0;
                $scorePercent = $leadScore / 100;
                $circumference = 2 * 3.14159 * 45; // radius = 45
                $dashOffset = $circumference * (1 - $scorePercent);

                if ($leadScore > 80) {
                    $tempLabel = 'Hot';
                    $tempEmoji = '🔥';
                    $gradientFrom = '#ef4444';
                    $gradientTo = '#f97316';
                    $bgColor = 'bg-red-50';
                    $textColor = 'text-red-600';
                } elseif ($leadScore >= 40) {
                    $tempLabel = 'Warm';
                    $tempEmoji = '⚡';
                    $gradientFrom = '#f59e0b';
                    $gradientTo = '#eab308';
                    $bgColor = 'bg-amber-50';
                    $textColor = 'text-amber-600';
                } else {
                    $tempLabel = 'Cold';
                    $tempEmoji = '❄️';
                    $gradientFrom = '#3b82f6';
                    $gradientTo = '#06b6d4';
                    $bgColor = 'bg-blue-50';
                    $textColor = 'text-blue-600';
                }
            @endphp
            <div class="glass-card p-6 {{ $bgColor }} relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r" style="background: linear-gradient(90deg, {{ $gradientFrom }}, {{ $gradientTo }})"></div>
                <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-4 text-center">AI Lead Score</p>
                <div class="flex justify-center">
                    <div class="relative w-32 h-32">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <defs>
                                <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:{{ $gradientFrom }}"/>
                                    <stop offset="100%" style="stop-color:{{ $gradientTo }}"/>
                                </linearGradient>
                            </defs>
                            <circle cx="50" cy="50" r="45" fill="none" stroke-width="6" class="stroke-surface-200"/>
                            <circle cx="50" cy="50" r="45" fill="none" stroke-width="6"
                                stroke="url(#scoreGradient)"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}"
                                stroke-dashoffset="{{ $dashOffset }}"
                                class="transition-all duration-1000"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black {{ $textColor }}">{{ $leadScore }}</span>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <span class="text-lg">{{ $tempEmoji }}</span>
                    <span class="text-sm font-black {{ $textColor }} ml-1">{{ $tempLabel }} Lead</span>
                </div>

                <div class="mt-6 pt-6 border-t border-surface-200/50 space-y-4 text-left">
                    {{-- Email Lead Score --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-surface-500 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Email Score
                            </span>
                            <span class="text-xs font-black text-primary-600">{{ $email->email_lead_score ?? 1 }}/10</span>
                        </div>
                        <div class="w-full bg-surface-200/50 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-primary-500 to-indigo-500 h-full rounded-full" style="width: {{ ($email->email_lead_score ?? 1) * 10 }}%"></div>
                        </div>
                    </div>

                    {{-- WhatsApp Lead Score --}}
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs font-bold text-surface-500 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.966a9.78 9.78 0 0 0-6.979-2.88C6.19.06 1.764 4.43 1.761 9.86c-.001 1.716.452 3.39 1.31 4.887l-1.02 3.722 3.822-1.002zM18.062 14.86c-.328-.164-1.94-.958-2.241-1.07-.301-.11-.52-.164-.738.164-.219.329-.848 1.07-1.039 1.29-.192.218-.384.246-.712.082-.328-.164-1.386-.51-2.64-1.627-.976-.87-1.635-1.947-1.826-2.275-.192-.329-.02-.507.144-.671.148-.148.329-.384.493-.575.164-.192.219-.329.329-.548.11-.219.055-.411-.027-.575-.082-.164-.738-1.78-.985-2.383-.242-.588-.487-.508-.669-.517-.173-.008-.372-.01-.571-.01-.2 0-.527.075-.802.373-.276.299-1.052 1.028-1.052 2.507 0 1.48 1.078 2.906 1.229 3.109.151.203 2.122 3.24 5.14 4.542.718.31 1.278.495 1.714.633.721.23 1.378.198 1.9.12.582-.088 1.94-.794 2.214-1.522.274-.728.274-1.353.192-1.483-.083-.13-.301-.203-.63-.367z"/></svg>
                                WhatsApp Score
                            </span>
                            <span class="text-xs font-black text-emerald-600">{{ $email->whatsapp_lead_score ?? 1 }}/10</span>
                        </div>
                        <div class="w-full bg-surface-200/50 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full" style="width: {{ ($email->whatsapp_lead_score ?? 1) * 10 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Activity Timeline & Notes ── --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- Notes Section --}}
            <div class="glass-card flex flex-col h-[400px]">
                <div class="p-6 border-b border-surface-100 bg-surface-50/50 flex items-center justify-between">
                    <h3 class="font-black text-surface-900 uppercase tracking-widest text-xs">Internal Team Notes</h3>
                    <span class="text-[10px] font-bold text-surface-400">{{ $email->notes->count() }} Updates</span>
                </div>
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    @forelse($email->notes as $note)
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-lg bg-surface-100 flex items-center justify-center text-[10px] font-bold text-surface-500 shrink-0">
                            {{ substr($note->user->name, 0, 2) }}
                        </div>
                        <div class="flex-1">
                            <div class="bg-surface-50 rounded-2xl rounded-tl-none p-4 border border-surface-100">
                                <p class="text-sm text-surface-700 leading-relaxed">{{ $note->content }}</p>
                            </div>
                            <p class="text-[9px] font-bold text-surface-400 mt-2 uppercase">
                                {{ $note->user->name }} • {{ $note->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="h-full flex flex-col items-center justify-center text-center opacity-50">
                        <svg class="w-12 h-12 text-surface-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        <p class="text-sm font-medium">No notes on this contact yet.</p>
                    </div>
                    @endforelse
                </div>
                <div class="p-4 bg-surface-50 border-t border-surface-100">
                    <form action="{{ route('admin.contacts.notes.store', $email->id) }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="text" name="content" class="form-input flex-1 !bg-white" placeholder="Leave a note for the team...">
                        <button class="btn btn-primary px-6">Post</button>
                    </form>
                </div>
            </div>

            {{-- Activity Timeline --}}
            <div class="glass-card">
                <div class="p-6 border-b border-surface-100 bg-surface-50/50">
                    <h3 class="font-black text-surface-900 uppercase tracking-widest text-xs">Activity Timeline</h3>
                </div>
                <div class="p-8">
                    <div class="space-y-8 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-100">
                        @forelse($email->activities as $activity)
                        <div class="relative pl-10">
                            @php
                                $icon = match($activity->type) {
                                    'opened' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                                    'clicked' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                                    'unsubscribed' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
                                    default => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                                };
                                $color = match($activity->type) {
                                    'opened' => 'text-emerald-500 bg-emerald-50',
                                    'clicked' => 'text-indigo-500 bg-indigo-50',
                                    'unsubscribed' => 'text-red-500 bg-red-50',
                                    default => 'text-surface-500 bg-surface-50',
                                };
                            @endphp
                            <div class="absolute left-0 top-0 w-6 h-6 rounded-full {{ $color }} flex items-center justify-center border-4 border-white shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-surface-900">
                                    Email {{ ucfirst($activity->type) }}
                                    @if($activity->campaign)
                                        <span class="text-surface-400 font-medium">via Campaign</span>
                                        <span class="text-primary-600 font-black">{{ $activity->campaign->name }}</span>
                                    @endif
                                </p>
                                @if($activity->url)
                                    <p class="text-xs text-indigo-500 font-medium break-all mt-1">Clicked: {{ $activity->url }}</p>
                                @endif
                                <p class="text-[10px] font-bold text-surface-400 uppercase mt-1">{{ $activity->created_at->format('M d, Y • h:i A') }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-10 opacity-50">
                            <p class="text-sm font-medium italic text-surface-400">No activity logged yet.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
