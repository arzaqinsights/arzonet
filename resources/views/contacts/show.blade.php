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
                    <form action="{{ route('contacts.tags.update', $email->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="text" name="tags" value="{{ implode(', ', $email->tags ?? []) }}" 
                               class="form-input text-sm" placeholder="Add tags (comma separated)...">
                        <button class="btn btn-ghost btn-sm w-full border border-surface-200">Update Tags</button>
                    </form>
                </div>
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
                    <form action="{{ route('contacts.notes.store', $email->id) }}" method="POST" class="flex gap-2">
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
